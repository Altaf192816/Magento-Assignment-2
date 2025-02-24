<?php

namespace NewMagentoAssignment\Wallet\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory as WalletCollectionFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use NewMagentoAssignment\Wallet\Helper\ConfigHelper;

class Wallet extends Template
{
    protected $_walletCollectionFactory;
    protected $_customerSession;
    protected $_formKey;
    protected $_urlBuilder;
    protected $logger;
    protected $configHelper;

    /**
     * Constructor
     * @param Context $context
     * @param WalletCollectionFactory $walletCollectionFactory
     * @param CustomerSession $customerSession
     * @param FormKey $formKey
     * @param UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        Context                 $context,
        WalletCollectionFactory $walletCollectionFactory,
        CustomerSession         $customerSession,
        FormKey                 $formKey,
        UrlInterface            $urlBuilder,
        LoggerInterface         $logger,
        ConfigHelper            $configHelper,
        array                   $data = [],
    )
    {
        $this->_walletCollectionFactory = $walletCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        $this->_urlBuilder = $urlBuilder;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get wallet balance of logged-in customer
     * @return float
     */
    public function getWalletBalance()
    {
        $customerId = $this->_customerSession->getCustomerId();

        if (!$customerId) {
            return 0;
        }
        $walletCollection = $this->_walletCollectionFactory->create();
        $walletCollection->addFieldToFilter('customer_id', $customerId);
        $walletCollection->addFieldToFilter('transaction_status', 'completed');

        // Calculate balance
        $walletCollection->getSelect()
            ->columns(['balance' => new \Zend_Db_Expr(
                "SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END)"
            )]);

        $walletData = $walletCollection->getFirstItem();

        // Log the retrieved balance
        $balance = $walletData->getData('balance') ?? 0;
        return $balance;
    }


    /**
     * Get minimum amount allowed to add to the wallet
     * @return float
     */
    public function getMinAmount()
    {
        return $this->configHelper->getConfigValue("custompayment", "min_balance_limit");
    }

    /**
     * Get maximum amount allowed to add to the wallet
     * @return float
     */
    public function getMaxAmount()
    {
        return $this->configHelper->getConfigValue("custompayment", "max_balance_limit");
    }

    /**
     * Check if OTP is enabled for wallet transactions
     * @return bool
     */
    public function isOtpEnabled(): bool
    {
        return $this->configHelper->getConfigValue("custompayment", "otp", null, true);
    }

    /**
     * Get URL for adding money to the wallet
     * @return string
     */
    public function getAddMoneyUrl()
    {
        return $this->_urlBuilder->getUrl('wallet/index/addmoney', ['_secure' => true]);
    }

    /**
     * Get URL for requesting OTP
     * @return string
     */
    public function getOtpRequestUrl()
    {
        return $this->_urlBuilder->getUrl('wallet/index/requestotp', ['_secure' => true]);
    }

    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }
}

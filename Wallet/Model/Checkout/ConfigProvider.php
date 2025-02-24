<?php

namespace NewMagentoAssignment\Wallet\Model\Checkout;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;
use NewMagentoAssignment\Wallet\Helper\ConfigHelper;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory;

class ConfigProvider implements ConfigProviderInterface
{
    protected $configHelper;
    protected $collection;
    protected $customerSession;

    public function __construct(
        ConfigHelper      $configHelper,
        CustomerSession   $customerSession,
        CollectionFactory $postCollectionFactory,
    )
    {
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->collection = $postCollectionFactory->create();
    }

    /**
     * Get wallet balance of logged-in customer
     * @return float
     */
    public function getWalletBalance()
    {
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            return 0;
        }
        $walletCollection = $this->collection;
        $walletCollection->addFieldToFilter('customer_id', $customerId);
        $walletCollection->addFieldToFilter('transaction_status', 'completed');

        $walletCollection->getSelect()
            ->columns(['balance' => new \Zend_Db_Expr(
                "SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END)"
            )]);

        $walletData = $walletCollection->getFirstItem();

        $balance = $walletData->getData('balance') ?? 0;
        return $balance;
    }


    public function getConfig()
    {
        return [
            'payment' => [
                'custompayment' => [
                    'active' => $this->configHelper->getConfigValue("custompayment", "active", null, true),
                    'title' => $this->configHelper->getConfigValue("custompayment", "title"),
                    "walletBalance" => $this->getWalletBalance()
                ]
            ]
        ];
    }
}

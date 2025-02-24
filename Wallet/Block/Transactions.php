<?php

namespace NewMagentoAssignment\Wallet\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory as WalletCollectionFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class Transactions extends Template
{
    protected $_walletCollectionFactory;
    protected $_customerSession;
    protected $_formKey;
    protected $_urlBuilder;
    protected $logger;
    protected $_transactionsCollection;

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
        array                   $data = [],
    )
    {
        $this->_walletCollectionFactory = $walletCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_formKey = $formKey;
        $this->_urlBuilder = $urlBuilder;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Get paginated wallet transactions for the logged-in customer
     */
    public function getCustomerTransactions()
    {
        if ($this->_transactionsCollection === null) {
            $customerId = $this->_customerSession->getCustomerId();
            if (!$customerId) {
                return [];
            }

            $page = (int)($this->getRequest()->getParam('p') ?? 1);
            $pageSize = (int)($this->getRequest()->getParam('limit') ?? 5);

            $walletCollection = $this->_walletCollectionFactory->create();
            $walletCollection->addFieldToFilter('customer_id', $customerId);

            $walletCollection->setCurPage($page);
            $walletCollection->setPageSize($pageSize);
            $walletCollection->load();

            $this->_transactionsCollection = $walletCollection;
        }

        return $this->_transactionsCollection;
    }

    /**
     * Get the pager block
     */
    public function getPager()
    {
        $collection = $this->getCustomerTransactions();
        if (!$collection->getSize()) {
            return null;
        }

        $pager = $this->getLayout()->createBlock(
            \Magento\Theme\Block\Html\Pager::class,
            'wallet_transactions_pager'
        );
        $pager->setAvailableLimit([5 => 5, 10 => 10, 20 => 20])
            ->setShowPerPage(true)
            ->setCollection($collection);
        $this->setChild('wallet_transactions_pager', $pager);

        return $pager;
    }
}

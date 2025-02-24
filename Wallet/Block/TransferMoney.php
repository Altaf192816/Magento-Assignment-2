<?php

namespace NewMagentoAssignment\Wallet\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory as WalletCollectionFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

class TransferMoney extends Template
{
    protected $_walletCollectionFactory;
    protected $_customerSession;
    protected $_formKey;
    protected $_urlBuilder;
    protected $logger;

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
     * Get wallet balance of logged-in customer
     * @return float
     */
    /**
     * Get Wallet Balance for Current Customer
     */
    public function getWalletBalance()
    {
        $customerId = $this->_customerSession->getCustomerId();

        if (!$customerId) {
            return 0; // Return 0 if no customer is logged in
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
        $this->logger->info('Wallet Balance:', ['customer_id' => $customerId, 'balance' => $balance]);

        return $balance;
    }

    public function getFormKey()
    {
        return $this->_formKey->getFormKey();
    }

    /**
     * Get All Customers for Recipient Dropdown (excluding current user)
     * @return array
     */
    public function getAllCustomers()
    {
        $customerId = $this->_customerSession->getCustomerId();

        // Use Wallet Collection to get distinct customer IDs
        $walletCollection = $this->_walletCollectionFactory->create();
        $walletCollection->getSelect()->distinct(true)
            ->from(['w' => $walletCollection->getMainTable()], ['customer_id'])
            ->where('w.customer_id != ?', $customerId);

        $customerIds = $walletCollection->getColumnValues('customer_id');

        if (empty($customerIds)) {
            return [];
        }

        // Fetch customer details from Customer Collection
        $customerCollection = $this->_customerSession->getCustomer()->getCollection();
        $customerCollection->addAttributeToSelect(['entity_id', 'firstname', 'lastname']);
        $customerCollection->addFieldToFilter('entity_id', ['in' => $customerIds]);

        $customers = [];
        foreach ($customerCollection as $customer) {
            $customers[] = [
                'id' => $customer->getId(),
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            ];
        }

        return $customers;
    }

    /**
     * Get Transfer URL
     * @return string
     */
    public function getTransferUrl()
    {
        return $this->getUrl('wallet/index/transferamount'); // Change to your controller route
    }

}

<?php

namespace NewMagentoAssignment\Wallet\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use NewMagentoAssignment\Wallet\Model\WalletFactory;

class Order extends Action
{
    protected $transactionFactory;
    protected $messageManager;
    protected $customerSession;

    public function __construct(
        Context                         $context,
        WalletFactory                   $transactionFactory,
        ManagerInterface                $messageManager,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        parent::__construct($context);
        $this->transactionFactory = $transactionFactory;
        $this->messageManager = $messageManager;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $customerId = $this->customerSession->getCustomerId();

        // Get the customer_id and amount from the request
        $amount = $this->getRequest()->getParam('amount');

        // Validate the inputs
        if (!$customerId || !$amount) {
            $this->messageManager->addErrorMessage(__('Missing required parameters.'));
            return $this->_redirect('checkout/#payment'); // Redirect back if validation fails
        }

        // Create a new transaction
        try {
            $transaction = $this->transactionFactory->create();
            $transaction->setCustomerId($customerId)
                ->setAmount($amount)
                ->setTransactionType('debit')
                ->setTransactionStatus('completed')
                ->setCreatedAt(date('Y-m-d H:i:s'))
                ->setRemarks('Order of ' . $amount . ' for ' . $customerId)
                ->save();

            $this->messageManager->addSuccessMessage(__('Ordered successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error creating transaction: ' . $e->getMessage()));
        }

        // Redirect the user to a confirmation page or order page
        return $this->_redirect('checkout/#payment');
    }
}

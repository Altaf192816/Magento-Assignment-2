<?php

namespace NewMagentoAssignment\Wallet\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface;
use NewMagentoAssignment\Wallet\Model\WalletFactory;

class Deductmoney extends Action
{
    protected $resultJsonFactory;
    protected $customerSession;
    protected $transactionFactory;

    protected $messageManager;
    protected $escaper;

    public function __construct(
        Context          $context,
        JsonFactory      $resultJsonFactory,
        Session          $customerSession,
        WalletFactory    $transactionFactory,
        ManagerInterface $messageManager,
        Escaper          $escaper,
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->transactionFactory = $transactionFactory;
        $this->escaper = $escaper;
        $this->messageManager = $messageManager;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $request = $this->getRequest();
        $amount = (float)$request->getParam('amount');
        $customerId = (int)$request->getParam('id');

        if ($amount <= 0) {
            return $result->setData(['success' => false, 'message' => 'Invalid amount.']);
        }

        try {
            $transaction = $this->transactionFactory->create();
            $transaction->setData([
                'customer_id' => $customerId,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'transaction_status' => 'completed',
                'remarks' => 'Money Deduct to wallet',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $transaction->save();

            $this->messageManager->addSuccessMessage(__('%1 is Deduct from wallet successfully.', $amount));
            return $this->resultRedirectFactory->create()->setPath('customer/index/edit/id/' . $customerId);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Failed to Deduct money in wallet.!'));
        }
    }
}

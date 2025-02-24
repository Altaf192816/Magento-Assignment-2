<?php

namespace NewMagentoAssignment\Wallet\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface;
use NewMagentoAssignment\Wallet\Model\WalletFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class Transfermoney extends Action
{
    protected $customerSession;
    protected $walletFactory;
    protected $messageManager;
    protected $formKeyValidator;
    protected $resource;
    protected $logger;

    public function __construct(
        Context            $context,
        CustomerSession    $customerSession,
        WalletFactory      $walletFactory,
        ManagerInterface   $messageManager,
        Validator          $formKeyValidator,
        ResourceConnection $resource,
        LoggerInterface    $logger,
    )
    {
        $this->customerSession = $customerSession;
        $this->walletFactory = $walletFactory;
        $this->messageManager = $messageManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->resource = $resource;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $request = $this->getRequest();
        $amount = (float)$request->getParam('amount');
        $senderId = (int)$request->getParam('id');
        $recipientId = (int)$request->getParam('recipient_id');

        if ($recipientId === $senderId) {
            $this->messageManager->addErrorMessage(__('You cannot transfer money to yourself.'));
            return $this->resultRedirectFactory->create()->setPath('customer/index/edit/id/' . $senderId);
        }

        if ($amount <= 0) {
            $this->messageManager->addErrorMessage(__('Please enter a valid transfer amount.'));
            return $this->resultRedirectFactory->create()->setPath('customer/index/edit/id/' . $senderId);
        }

        // Get Sender's Wallet Balance from Transactions
        $connection = $this->resource->getConnection();
        $walletTable = $this->resource->getTableName('transactions');

        $balanceQuery = $connection->select()
            ->from($walletTable, [new \Zend_Db_Expr('SUM(CASE WHEN transaction_type = "credit" THEN amount ELSE -amount END) AS balance')])
            ->where('customer_id = ?', $senderId);

        $senderBalance = $connection->fetchOne($balanceQuery) ?? 0;

        if ($amount > $senderBalance) {
            $this->messageManager->addErrorMessage(__('Insufficient wallet balance.'));
            return $this->resultRedirectFactory->create()->setPath('customer/index/edit/id/' . $senderId);
        }

        try {
            $currentTimestamp = (new \DateTime())->format('Y-m-d H:i:s');

            // Create Sender Transaction Debit
            $senderTransaction = $this->walletFactory->create();
            $senderTransaction->setData([
                'customer_id' => $senderId,
                'transaction_type' => 'debit',
                'amount' => $amount,
                'transaction_status' => 'completed',
                'remarks' => 'Transfer to customer ID: ' . $recipientId,
                'created_at' => $currentTimestamp,
            ]);
            $senderTransaction->save();

            // Create Recipient Transaction
            $recipientTransaction = $this->walletFactory->create();
            $recipientTransaction->setData([
                'customer_id' => $recipientId,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'transaction_status' => 'completed',
                'remarks' => 'Received from customer ID: ' . $senderId,
                'created_at' => $currentTimestamp,
            ]);
            $recipientTransaction->save();

            $this->messageManager->addSuccessMessage(__('Money transferred successfully.'));
            return $this->resultRedirectFactory->create()->setPath('customer/index/edit/id/' . $senderId);
        } catch (\Exception $e) {
            $this->logger->error('Wallet Transfer Error:', ['exception' => $e]);
            $this->messageManager->addErrorMessage(__('Something went wrong during the transfer.'));
        }

        return $this->resultRedirectFactory->create()->setPath('wallet/index/transfermoney');
    }
}

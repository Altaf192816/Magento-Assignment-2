<?php

namespace NewMagentoAssignment\Wallet\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use NewMagentoAssignment\Wallet\Model\WalletFactory;
use Magento\Payment\Model\InfoInterface;
use NewMagentoAssignment\Wallet\Helper\ConfigHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class RefundToWallet implements ObserverInterface
{
    const XML_PATH_EMAIL_RECIPIENT_NAME = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';
    protected $logger;
    protected $walletTransactionFactory;
    protected $configHelper;
    protected $transportBuilder;
    protected $storeManager;
    protected $_scopeConfig;

    public function __construct(
        LoggerInterface       $logger,
        WalletFactory         $walletTransactionFactory,
        ConfigHelper          $configHelper,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface  $scopeConfig,
    )
    {
        $this->logger = $logger;
        $this->walletTransactionFactory = $walletTransactionFactory;
        $this->configHelper = $configHelper;
        $this->transportBuilder = $transportBuilder;
        $this->_scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function execute(Observer $observer)
    {
        $creditmemo = $observer->getEvent()->getData('creditmemo');

        $isRefundActive = $this->configHelper->getConfigValue("refund_setting", "enable_refund", null, true);
        if (!$isRefundActive) return;

        if (!$creditmemo) {
            $this->logger->error("Creditmemo data not found in observer.");
            return;
        }

        $order = $creditmemo->getOrder();
        $payment = $order->getPayment();
        $refundAmount = $creditmemo->getGrandTotal();

        try {
            $this->refund($payment, $refundAmount);
        } catch (LocalizedException $e) {
            $this->logger->error("Refund Error: " . $e->getMessage());
        }
    }

    public function refund(InfoInterface $payment, $amount)
    {
        $this->logger->debug('Refund method triggered for amount: ' . $amount);

        $order = $payment->getOrder();
        $customerId = $order->getCustomerId();
        $customerEmail = $order->getCustomerEmail();
        $customerName = $order->getCustomerFirstname();
        $orderIncrementId = $order->getIncrementId();

        if (!$customerId) {
            throw new LocalizedException(__('Customer ID is missing.'));
        }

        try {
            // Process refund transaction
            $walletTransaction = $this->walletTransactionFactory->create();
            $walletTransaction->setData([
                'customer_id' => $customerId,
                'order_id' => $orderIncrementId,
                'amount' => $amount,
                'remarks' => 'Refund amount of orderId ' . $orderIncrementId,
                'transaction_type' => 'credit',
                'transaction_status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $walletTransaction->save();

            // Set refund transaction ID
            $payment->setTransactionId($orderIncrementId . '-refund');

            // Close refund transaction
            $payment->setIsTransactionClosed(true);
            $order->save();

            // Send refund email
            $this->sendRefundEmail($customerEmail, $customerName, $amount, $orderIncrementId);

        } catch (\Exception $e) {
            $this->logger->error('Error processing refund: ' . $e->getMessage());
        }
    }

    public function sendRefundEmail($customerEmail, $customerName, $refundAmount, $orderId)
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();

            $templateVars = [
                'customer_name' => $customerName,
                'refund_amount' => number_format($refundAmount, 2),
                'order_id' => $orderId,
            ];

            $sender = [
                'name' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('order_refund_email')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
            $this->logger->info("Refund email sent successfully to {$customerEmail}");

        } catch (\Exception $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage());
        }
    }
}

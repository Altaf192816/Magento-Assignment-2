<?php

namespace NewMagentoAssignment\Wallet\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use NewMagentoAssignment\Wallet\Model\WalletFactory;
use NewMagentoAssignment\Wallet\Helper\ConfigHelper;
use Psr\Log\LoggerInterface;

class Addmoney extends Action
{
    const XML_PATH_EMAIL_RECIPIENT_NAME = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';
    protected $resultJsonFactory;
    protected $customerSession;
    protected $transactionFactory;

    protected $_inlineTranslation;
    protected $storeManager;
    protected $_transportBuilder;
    protected $messageManager;
    protected $escaper;
    protected $_scopeConfig;
    protected $session;
    protected $logger;

    protected $configHelper;

    public function __construct(
        Context                 $context,
        JsonFactory             $resultJsonFactory,
        Session                 $customerSession,
        WalletFactory           $transactionFactory,
        ManagerInterface        $messageManager,
        StateInterface          $inlineTranslation,
        TransportBuilder        $transportBuilder,
        StoreManagerInterface   $storeManager,
        Escaper                 $escaper,
        ScopeConfigInterface    $scopeConfig,
        SessionManagerInterface $session,
        LoggerInterface         $logger,
        ConfigHelper            $configHelper,
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->transactionFactory = $transactionFactory;
        $this->storeManager = $storeManager;
        $this->escaper = $escaper;
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->messageManager = $messageManager;
        $this->_scopeConfig = $scopeConfig;
        $this->session = $session;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
    }

    public function execute()
    {
        $isOtpEnabled = $this->configHelper->getConfigValue("custompayment", "otp", null, true);

        if ($isOtpEnabled) {
            return $this->executeWithOtp();
        } else {
            return $this->executeWithoutOtp();
        }
    }

    private function executeWithOtp()
    {
        try {
            // OTP verification logic
            $inputOtp = $this->escaper->escapeHtml($this->getRequest()->getParam('otp'));
            $storedOtp = $this->session->getWalletOtp();
            $otpTime = $this->session->getWalletOtpTime();

            if (!$storedOtp || !$otpTime) {
                $this->messageManager->addErrorMessage(__('OTP not found'));
                return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
            }

            if ((time() - $otpTime) > 300) {
                $this->messageManager->addErrorMessage(__('OTP expired'));
                return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
            }

            if ($storedOtp != $inputOtp) {
                $this->messageManager->addErrorMessage(__('Invalid OTP'));
                return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
            }

            $this->session->unsWalletOtp();
            $this->session->unsWalletOtpTime();

            return $this->processWalletTransaction();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred while processing OTP.'));
            return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
        }
    }

    private function executeWithoutOtp()
    {
        return $this->processWalletTransaction();
    }

    private function processWalletTransaction()
    {
        try {
            if (!$this->customerSession->isLoggedIn()) {
                $this->messageManager->addErrorMessage(__('Please log in to add money.'));
                return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
            }

            $customer = $this->customerSession->getCustomer();
            $customerId = $this->customerSession->getCustomerId();
            $amount = (float)$this->escaper->escapeHtml($this->getRequest()->getParam('amount'));

            if ($amount <= 0 && $this->isWithinLimits($amount)) {
                $this->messageManager->addErrorMessage(__('Invalid amount.'));
                return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
            }

            $transaction = $this->transactionFactory->create();
            $transaction->setData([
                'customer_id' => $customerId,
                'transaction_type' => 'credit',
                'amount' => $amount,
                'transaction_status' => 'completed',
                'remarks' => 'Money added to wallet by himself',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $transaction->save();

            $this->sendEmail($customer, $transaction);
            $this->messageManager->addSuccessMessage(__('%1 is added to your wallet successfully.', $amount));
            return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Failed to add money in wallet.'));
            return $this->resultRedirectFactory->create()->setPath('wallet/index/mywallet');
        }
    }

    public function isWithinLimits(float $amount)
    {
        $minLimit = (float)$this->configHelper->getConfigValue('custompayment', 'min_balance_limit');
        $maxLimit = (float)$this->configHelper->getConfigValue('custompayment', 'max_balance_limit');

        return $amount >= $minLimit && $amount <= $maxLimit;
    }

    private function sendEmail($customer, $transaction)
    {
        try {
            $this->_inlineTranslation->suspend();

            $store = $this->storeManager->getStore();
            $sender = [
                'name' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ];

            $transport = $this->_transportBuilder
                ->setTemplateIdentifier('add_money_email_template')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars([
                    'customer_name' => $customer->getFirstname(),
                    'transaction_type' => ucfirst($transaction->getTransactionType()),
                    'amount' => $transaction->getAmount(),
                    'transaction_status' => ucfirst($transaction->getTransactionStatus()),
                ])
                ->setFromByScope($sender)
                ->addTo($customer->getEmail(), $customer->getFirstname())
                ->getTransport();

            $transport->sendMessage();
            $this->_inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->error('Email Sending Error: ' . $e->getMessage());
        }
    }
}

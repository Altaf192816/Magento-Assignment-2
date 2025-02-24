<?php

namespace NewMagentoAssignment\Wallet\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Escaper;

class Requestotp extends Action
{
    const XML_PATH_EMAIL_RECIPIENT_NAME = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';

    protected $session;
    protected $transportBuilder;
    protected $resultJsonFactory;
    protected $customerSession;
    protected $storeManager;
    protected $logger;

    protected $_scopeConfig;
    protected $escaper;

    public function __construct(
        Context                 $context,
        SessionManagerInterface $session,
        CustomerSession         $customerSession,
        JsonFactory             $resultJsonFactory,
        ScopeConfigInterface    $scopeConfig,
        TransportBuilder        $transportBuilder,
        StoreManagerInterface   $storeManager,
        LoggerInterface         $logger,
        Escaper                 $escaper
    )
    {
        parent::__construct($context);
        $this->session = $session;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->_scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->escaper = $escaper;
    }

    public function execute()
    {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP and timestamp in session
        $this->session->setWalletOtp($otp);
        $this->session->setWalletOtpTime(time());

        //email to user
        $this->sendOtpEmail($otp);

        // Return a JSON response
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([
            'success' => true,
            'message' => 'OTP has been sent successfully'
        ]);
    }

    public function sendOtpEmail($otp)
    {
        if (!$this->customerSession->isLoggedIn()) {
            $this->logger->debug("customer session is not logged in");
            return false;
        }

        $customer = $this->customerSession->getCustomer();
        $customerEmail = $customer->getEmail();
        $customerName = $customer->getName();

        $sender = [
            'name' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'email' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        ];

        try {
            $store = $this->storeManager->getStore();
            $templateVars = [
                'otp' => $otp,
                'customer_name' => $customerName,
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('add_money_otp_email_template')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $this->messageManager->addSuccessMessage(__('Otp is sent successfully.'));
            $transport->sendMessage();
            return true;
        } catch (\Exception $e) {
            $this->logger->debug("failed to send mail" . $e->getMessage());
            $this->messageManager->addErrorMessage(__('There was an error sending your OTP.'));
            return false;
        }
    }
}

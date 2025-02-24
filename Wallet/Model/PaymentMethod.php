<?php

namespace NewMagentoAssignment\Wallet\Model;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use NewMagentoAssignment\Wallet\Model\WalletFactory;
use Psr\Log\LoggerInterface;
use NewMagentoAssignment\Wallet\Helper\ConfigHelper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\CustomerFactory;

class PaymentMethod extends AbstractMethod
{
    const XML_PATH_EMAIL_RECIPIENT_NAME = 'trans_email/ident_support/name';
    const XML_PATH_EMAIL_RECIPIENT_EMAIL = 'trans_email/ident_support/email';
    protected $_code = 'custompayment';
    protected $_isGateway = true;
    protected $_canRefundInvoicePartial = true;
    protected $_stripeApi = false;
    protected $_countryFactory;
    protected $_minAmount = null;
    protected $_maxAmount = null;
    protected $_supportedCurrencyCodes = ['USD'];
    protected $_debugReplacePrivateDataKeys = ['number', 'exp_month', 'exp_year', 'cvc'];
    protected $_urlBuilder;
    protected $_walletTransactionFactory;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $configHelper;
    protected $transportBuilder;
    protected $storeManager;
    protected $customerFactory;

    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory       $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory            $customAttributeFactory,
        \Magento\Payment\Helper\Data                            $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface      $scopeConfig,
        \Magento\Payment\Model\Method\Logger                    $logger,
        UrlInterface                                            $urlBuilder,
        ConfigHelper                                            $configHelper,
        TransportBuilder                                        $transportBuilder,
        StoreManagerInterface                                   $storeManager,
        CustomerFactory                                         $customerFactory,
        WalletFactory                                           $walletTransactionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_walletTransactionFactory = $walletTransactionFactory;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->transportBuilder = $transportBuilder;
    }

    /**
     * Authorize payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canAuthorize()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The authorize action is not available.'));
        }

        $order = $payment->getOrder();
        $payment->setTransactionId($order->getIncrementId() . '-auth');
        $payment->setIsTransactionClosed(false);


        $order->addStatusHistoryComment(__('Authorized wallet payment of %1.', $amount));
        $order->save();

        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        if (!$this->canCapture()) {
            throw new LocalizedException(__('The capture action is not available.'));
        }

        // Get order and customer info
        $order = $payment->getOrder();
        $customerId = $order->getCustomerId();
        $orderIncrementId = $order->getIncrementId();

        if (!$customerId) {
            throw new LocalizedException(__('Customer ID is missing.'));
        }

        // Reward
        $isRewardActive = $this->configHelper->getConfigValue("reward_settings", "enable_rewards", null, true);
        if ($isRewardActive) {
            $rewardType = $this->configHelper->getConfigValue("reward_settings", "reward_type");
            $rewardValue = $this->configHelper->getConfigValue("reward_settings", "reward_value");

            $cashback = $rewardType == "percentage" ? $amount * ((float)$rewardValue / 100) : (float)$rewardValue;

            $walletTransaction = $this->_walletTransactionFactory->create();
            $walletTransaction->setData([
                'customer_id' => $customerId,
                'amount' => $cashback,
                'transaction_type' => 'credit',
                'transaction_status' => 'completed',
                'remarks' => "Cashback of $cashback is received from order #$orderIncrementId",
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->sendEmail($customerId, $cashback, $orderIncrementId);
            $walletTransaction->save();
        }

        // Create wallet transaction
        try {
            $walletTransaction = $this->_walletTransactionFactory->create();
            $walletTransaction->setData([
                'customer_id' => $customerId,
                'order_id' => $orderIncrementId,
                'amount' => $amount,
                'remarks' => 'Order #' . $orderIncrementId,
                'transaction_type' => 'debit',
                'transaction_status' => 'completed',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $walletTransaction->save();
            $order->save();
            return $this;

        } catch (\Exception $e) {
            throw new LocalizedException(__('Error capturing payment: %1', $e->getMessage()));
        }
    }

    public function sendEmail($customerId, $rewardAmount, $orderIncrementId)
    {
        try {
            $customer = $this->customerFactory->create()->load($customerId);
            $customerEmail = $customer->getEmail();
            $customerName = $customer->getFirstname() . ' ' . $customer->getLastname();

            $storeId = $this->storeManager->getStore()->getId();
            $templateVars = [
                'customer_name' => $customerName,
                'reward_amount' => number_format($rewardAmount, 2),
                'order_id' => $orderIncrementId
            ];

            $sender = [
                'name' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'email' => $this->_scopeConfig->getValue(self::XML_PATH_EMAIL_RECIPIENT_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ];

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('cashback_reward_email')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $storeId
                ])
                ->setTemplateVars($templateVars)
                ->setFrom($sender)
                ->addTo($customerEmail, $customerName)
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }
    }
}

<?php

namespace NewMagentoAssignment\Wallet\Cron;

use Psr\Log\LoggerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory;

class SendWalletReport
{
    protected $logger;
    protected $transportBuilder;
    protected $storeManager;
    protected $scopeConfig;
    protected $walletCollectionFactory;

    public function __construct(
        LoggerInterface       $logger,
        TransportBuilder      $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface  $scopeConfig,
        CollectionFactory     $walletCollectionFactory
    )
    {
        $this->logger = $logger;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->walletCollectionFactory = $walletCollectionFactory;
    }

    public function execute()
    {
        try {
            $walletCollection = $this->walletCollectionFactory->create();

            $walletCollection->addFieldToFilter('created_at', [
                'gteq' => date('Y-m-01', strtotime('last month'))
            ])->addFieldToFilter('created_at', [
                'lteq' => date('Y-m-t', strtotime('last month'))
            ]);

            if ($walletCollection->getSize() == 0) {
                $this->logger->info("No wallet transactions found for last month.");
                return;
            }

            $reportContent = "Monthly Wallet Report\n\n";
            foreach ($walletCollection as $transaction) {
                $reportContent .= sprintf(
                    "Customer ID: %s | Amount: %s | Type: %s | Date: %s\n",
                    $transaction->getCustomerId(),
                    $transaction->getAmount(),
                    $transaction->getTransactionType(),
                    $transaction->getCreatedAt()
                );
            }

            $adminEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $adminName = $this->scopeConfig->getValue('trans_email/ident_general/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            if (!$adminEmail) {
                $this->logger->error("Admin email not configured. Cannot send the wallet report.");
                return;
            }

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('wallet_report_email_template')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
                    'store' => $this->storeManager->getStore()->getId(),
                ])
                ->setTemplateVars(['report' => nl2br($reportContent)])
                ->setFrom(['email' => $adminEmail, 'name' => $adminName])
                ->addTo($adminEmail)
                ->getTransport();

            $transport->sendMessage();

            $this->logger->info("Monthly wallet report sent to admin: $adminEmail");

        } catch (\Exception $e) {
            $this->logger->error("Error sending wallet report: " . $e->getMessage());
        }
    }
}

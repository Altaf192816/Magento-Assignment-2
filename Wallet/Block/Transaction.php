<?php

namespace NewMagentoAssignment\Wallet\Block;

use Magento\Framework\View\Element\Template;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory as WalletCollectionFactory;

class Transaction extends Template
{
    protected $transactionFactory;

    public function __construct(
        Template\Context        $context,
        WalletCollectionFactory $transactionFactory,
        array                   $data = []
    )
    {
        $this->transactionFactory = $transactionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get Transaction Details
     */
    public function getTransaction()
    {
        $transactionId = $this->getRequest()->getParam('id');
        if ($transactionId) {
            $transactionCollection = $this->transactionFactory->create();
            $transactionCollection->addFieldToFilter('id', $transactionId);
            return $transactionCollection->getFirstItem();
        }
        return null;
    }
}

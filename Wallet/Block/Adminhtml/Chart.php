<?php

namespace NewMagentoAssignment\Wallet\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory;

class Chart extends Template
{
    protected $collectionFactory;

    public function __construct(
        Template\Context  $context,
        CollectionFactory $collectionFactory,
        array             $data = []
    )
    {
        parent::__construct($context, $data);
        $this->collectionFactory = $collectionFactory;
    }

    public function getTransactionData($startDate = null, $endDate = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('transaction_status', 'completed');

        if ($startDate) {
            $collection->addFieldToFilter('created_at', ['gteq' => $startDate . ' 00:00:00']);
        }
        if ($endDate) {
            $collection->addFieldToFilter('created_at', ['lteq' => $endDate . ' 23:59:59']);
        }

        // Group by date and sum amounts
        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'date' => new \Zend_Db_Expr("DATE(created_at)"),
                'total_amount' => new \Zend_Db_Expr("SUM(amount)")
            ])
            ->group(new \Zend_Db_Expr("DATE(created_at)"))
            ->order(new \Zend_Db_Expr("DATE(created_at) ASC"));

        return $collection->getData();
    }

}

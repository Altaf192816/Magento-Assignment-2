<?php

namespace NewMagentoAssignment\Wallet\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory;

class Data extends AbstractHelper
{
    protected $resource;
    protected $collectionFactory;

    public function __construct(
        Context            $context,
        ResourceConnection $resource,
        CollectionFactory $collectionFactory
    )
    {
        parent::__construct($context);
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
    }

    public function getTransactionData()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('transaction_status', 'success');

        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'date' => new \Zend_Db_Expr("DATE(created_at)"),
                'total_amount' => new \Zend_Db_Expr("SUM(amount)")
            ])
            ->group(new \Zend_Db_Expr("DATE(created_at)"))
            ->order("created_at ASC");

        return $collection->getData();
    }
}

<?php

namespace NewMagentoAssignment\Wallet\Model\Ui\Component\DataProvider;

use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use psr\Log\LoggerInterface;

class WalletDataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $loadedData;
    private $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $postCollectionFactory,
        LoggerInterface $logger,
        array $meta = [],
        array $data = [],
    )
    {
        $this->collection = $postCollectionFactory->create();
        $this->logger = $logger;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        $collection = $this->collection;

        // Modify select to include group and calculated columns explicitly
        $collection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns([
                'customer_id',
                'total_credit' => new \Zend_Db_Expr('SUM(CASE WHEN transaction_type = "credit" AND transaction_status = "completed" THEN amount ELSE 0 END)'),
                'total_debit' => new \Zend_Db_Expr('SUM(CASE WHEN transaction_type = "debit" AND transaction_status = "completed" THEN amount ELSE 0 END)'),
                'wallet_money' => new \Zend_Db_Expr('
                     SUM(CASE WHEN transaction_type = "credit" AND transaction_status = "completed" THEN amount ELSE 0 END) -
                     SUM(CASE WHEN transaction_type = "debit" AND transaction_status = "completed" THEN amount ELSE 0 END)'
                )
            ])
            ->group('customer_id');

        $data = [];

        foreach ($collection as $item) {
            $data[] = [
                'customer_id' => $item->getCustomerId(),
                'total_credit' => $item->getData('total_credit'),
                'total_debit' => $item->getData('total_debit'),
                'wallet_money' => $item->getData('wallet_money'),
            ];
        }

        if (empty($data)) {
            return ['totalRecords' => 0, 'items' => []];
        }

        return ['totalRecords' => count($data), 'items' => $data];
    }

}

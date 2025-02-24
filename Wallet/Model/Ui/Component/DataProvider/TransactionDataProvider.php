<?php

namespace NewMagentoAssignment\Wallet\Model\Ui\Component\DataProvider;

use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;

class TransactionDataProvider extends AbstractDataProvider
{
    protected $collection;
    private $logger;
    private $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $postCollectionFactory
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $postCollectionFactory,
        RequestInterface $request,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    )
    {
        $this->collection = $postCollectionFactory->create();
        $this->request = $request;
        $this->logger = $logger;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        $filters = $this->request->getParam('filters');

        // Check if filters contain 'customer_id'
        if (isset($filters['customer_id'])) {
            $customerId = $filters['customer_id'];
        } else {
            $customerId = $this->request->getParam('customer_id');
        }

        $this->logger->debug("Customer ID: " . json_encode($customerId));

        if (empty($customerId)) {
            return ['totalRecords' => 0, 'items' => []];
        }

        // Apply filter
        $this->collection->addFieldToFilter('customer_id', $customerId);
        $data = $this->collection->toArray();

        return [
            'totalRecords' => $this->collection->getSize(),
            'items' => $data['items']
        ];
    }
}

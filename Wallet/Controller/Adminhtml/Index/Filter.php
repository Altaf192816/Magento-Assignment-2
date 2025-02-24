<?php

namespace NewMagentoAssignment\Wallet\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use NewMagentoAssignment\Wallet\Block\Adminhtml\Chart;

class Filter extends Action
{
    protected $resultJsonFactory;
    protected $chartBlock;

    public function __construct(
        Context     $context,
        JsonFactory $resultJsonFactory,
        Chart       $chartBlock
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->chartBlock = $chartBlock;
    }

    public function execute()
    {
        $startDate = $this->getRequest()->getParam('start_date');
        $endDate = $this->getRequest()->getParam('end_date');
        $data = $this->chartBlock->getTransactionData($startDate, $endDate);

        return $this->resultJsonFactory->create()->setData($data);
    }
}

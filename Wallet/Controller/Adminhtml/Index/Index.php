<?php

namespace NewMagentoAssignment\Wallet\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected PageFactory $resultPageFactory;

    public function __construct(
        Context     $context,
        PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('NewMagentoAssignment_Wallet::wallet');
        $resultPage->getConfig()->getTitle()->prepend(__('Customers Wallet Information'));

        return $resultPage;
    }
}

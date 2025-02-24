<?php
namespace NewMagentoAssignment\Wallet\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Backend\Block\Widget\Form\Generic;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet\CollectionFactory as WalletCollectionFactory;
/**
 * Customer account form block
 */
class Tab extends Generic implements TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    private $logger;
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    protected $walletFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Store\Model\System\Store $systemStore,
        CustomerSession         $customerSession,
        WalletCollectionFactory $walletFactory,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_systemStore = $systemStore;
        $this->walletFactory = $walletFactory;
        $this->_customerSession = $customerSession;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    public function getTabLabel()
    {
        return __('Wallet Tab');
    }

    public function getTabTitle()
    {
        return __('Wallet Tab');
    }

    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    public function getTabClass()
    {
        return '';
    }

    public function getTabUrl()
    {
        return '';
    }

    public function isAjaxLoaded()
    {
        return false;
    }


    public function getCustomerWalletBalance()
    {
        $customerId = $this->getCustomerId();

        if(!$customerId){
            return 0;
        }
        $walletCollection = $this->walletFactory->create();
        $walletCollection->addFieldToFilter('customer_id', $customerId);
        $walletCollection->addFieldToFilter('transaction_status', 'completed');

        // Calculate balance
        $walletCollection->getSelect()
            ->columns(['balance' => new \Zend_Db_Expr(
                "SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END)"
            )]);

        $walletData = $walletCollection->getFirstItem();

        $balance = $walletData->getData('balance') ?? 0;

        return $balance;
    }

    /**
     * Get All Customers for Recipient Dropdown (excluding current user)
     * @return array
     */
    public function getAllCustomers()
    {
        $customerId = $this->getCustomerId();

        // Use Wallet Collection to get distinct customer IDs
        $walletCollection = $this->walletFactory->create();
        $walletCollection->getSelect()->distinct(true)
            ->from(['w' => $walletCollection->getMainTable()], ['customer_id'])
            ->where('w.customer_id != ?', $customerId);

        $customerIds = $walletCollection->getColumnValues('customer_id');

        if (empty($customerIds)) {
            return [];
        }

        // Fetch customer details from Customer Collection
        $customerCollection = $this->_customerSession->getCustomer()->getCollection();
        $customerCollection->addAttributeToSelect(['entity_id', 'firstname', 'lastname']);
        $customerCollection->addFieldToFilter('entity_id', ['in' => $customerIds]);

        $customers = [];
        foreach ($customerCollection as $customer) {
            $customers[] = [
                'id' => $customer->getId(),
                'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
            ];
        }

        return $customers;
    }

    protected function _toHtml()
    {
        if ($this->canShowTab()) {
            $this->assign('walletBalance', $this->getCustomerWalletBalance());
            return $this->fetchView($this->getTemplateFile('NewMagentoAssignment_Wallet::customer/wallet.phtml'));
        }
        return '';
    }

    public function getAddMoneyUrl()
    {
        return $this->_urlBuilder->getUrl('wallet/index/addmoney', ['_secure' => true]);
    }

    public function getDeductMoneyUrl()
    {
        return $this->_urlBuilder->getUrl('wallet/index/deductmoney', ['_secure' => true]);
    }

    public function getTransferMoneyUrl()
    {
        return $this->_urlBuilder->getUrl('wallet/index/transfermoney', ['_secure' => true]);
    }
}

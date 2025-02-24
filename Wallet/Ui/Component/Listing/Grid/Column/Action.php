<?php

namespace NewMagentoAssignment\Wallet\Ui\Component\Listing\Grid\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Action extends Column
{
    protected $_urlBuilder;

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        array              $components = [],
        array              $data = [],
    )
    {
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['customer_id'])) {
                    $item[$name]['transactions'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            'wallet/index/transactions',
                            ['customer_id' => $item['customer_id']]
                        ),
                        'label' => __('Transactions'),
                        'hidden' => false,
                    ];
                }
            }
        }

        return $dataSource;
    }
}

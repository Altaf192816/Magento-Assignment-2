<?php

namespace NewMagentoAssignment\Wallet\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class RewardType implements ArrayInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => 'percentage', 'label' => __('Percentage')],
            ['value' => 'fixed', 'label' => __('Fixed Amount')],
        ];
    }
}

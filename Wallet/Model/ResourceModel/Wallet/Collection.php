<?php

namespace NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use NewMagentoAssignment\Wallet\Model\ResourceModel\Wallet as WalletResource;
use NewMagentoAssignment\Wallet\Model\Wallet as Wallet;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Wallet::class, WalletResource::class);
    }
}

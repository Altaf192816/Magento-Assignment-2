<?php

namespace NewMagentoAssignment\Wallet\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class ConfigHelper extends AbstractHelper
{
    const string XML_PATH_WALLET = 'wallet_configuration';

    /**
     * Get configuration value
     *
     * @param string $groupId
     * @param string $fieldId
     * @param string|null $scopeCode
     * @param bool $isFlag
     * @return mixed
     */
    public function getConfigValue(string $groupId, string $fieldId, string $scopeCode = null, bool $isFlag = false)
    {
        $path = self::XML_PATH_WALLET . '/' . $groupId . '/' . $fieldId;

        return $isFlag
            ? $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $scopeCode)
            : $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $scopeCode);
    }
}

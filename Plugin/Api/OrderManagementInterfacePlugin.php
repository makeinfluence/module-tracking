<?php

namespace Wexo\MakeInfluence\Plugin\Api;

use Magento\Framework\App\ScopeInterface;

class OrderManagementInterfacePlugin
{
    public ScopeInterface $scopeConfig;

    public function __construct(
        ScopeInterface $scopeConfig
    ) {

        $this->scopeConfig = $scopeConfig;
    }
    public function afterPlace(
        \Magento\Sales\Api\OrderManagementInterface $subject,
        $result) {
        return $result;
    }
}

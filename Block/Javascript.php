<?php namespace MakeInfluence\Tracking\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Javascript extends \Magento\Framework\View\Element\Template
{
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        Template\Context     $context,
        ScopeConfigInterface $scopeConfig,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    public function addJavascript()
    {
        return $this->scopeConfig->getValue(
                'makeinfluence/general/add_js',
                ScopeInterface::SCOPE_STORE
            ) === '1';
    }

    public function getBusinessId()
    {
        $value = $this->scopeConfig->getValue(
            'makeinfluence/general/business_id',
            ScopeInterface::SCOPE_STORE
        );
        if ($value === null) {
            return '{{ INSERT BUSINESS-ID HERE }}';
        }
        return $value;
    }
}
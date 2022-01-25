<?php

namespace Wexo\MakeInfluence\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;

class Manual extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Template path
     *
     * @var string
     */
    protected $_template = 'system/config/manual.phtml';

    /**
     * @var string|int
     */
    protected $identifier;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Context $context,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ){
        parent::__construct($context, $data, $secureRenderer);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Render fieldset html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $this->identifier = $element->getId();
        $columns = $this->getRequest()->getParam('website') || $this->getRequest()->getParam('store') ? 5 : 4;
        return $this->_decorateRowHtml(
            $element,
            "<td colspan='{$columns}'>" . $this->toHtml() . '</td>'
        );
    }

    public function getBusinessId()
    {
        $value = $this->scopeConfig->getValue(
            'makeinfluence/general/business_id',
            ScopeInterface::SCOPE_STORE
        );
        if($value === null){
            return '{{ INSERT BUSINESS-ID HERE }}';
        }
        return $value;
    }
}
<?php namespace Wexo\MakeInfluence\Block\Checkout\Success;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

class Tracking extends \Magento\Framework\View\Element\Template
{
    private Session $checkoutSession;
    private ScopeConfigInterface $scopeConfig;
    private \Magento\Framework\App\RequestInterface $request;

    const MAKEINFLUENCE_TRACKING_URL = 'https://system.makeinfluence.com/track-conversion';
    public CookieManagerInterface $cookieManager;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session              $checkoutSession,
        RequestInterface     $request,
        CookieManagerInterface $cookieManager,
        Template\Context     $context,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->cookieManager = $cookieManager;
    }

    /**
     * @return array
     */
    public function prepareTrackingData()
    {
        $date = new \DateTime();
        $order = $this->getOrder();
        $businessId = $this->getBusinessId();
        $ip = $this->request->getServerValue('REMOTE_ADDR');
        $userAgent = $this->request->getServerValue('HTTP_USER_AGENT');
        $httpReferer = $this->request->getServerValue('HTTP_REFERER');
        $miid = $this->cookieManager->getCookie('_miid') ?? '';

        return [
            'business_id' => $businessId,
            'unique_id' => $this->getOrderId(),
            'cookie_id' => $miid,
            'value' => $this->getOrderValue(),
            'promotion_code' => $order->getCouponCode(),
            'created_at' => $date->format('Y-m-d H:i:s'),
            'currency' => $order->getOrderCurrencyCode(),
            'ip' => $ip,
            'user_agent' => $userAgent,
            'http_referer' => $httpReferer
        ];
    }

    public function isEnabled()
    {
        return $this->scopeConfig->getValue(
            'makeinfluence/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public $order = null;
    public function getOrder()
    {
        if($this->order === null)
            $this->order = $this->checkoutSession->getLastRealOrder();
        return $this->order;
    }

    public function getOrderId()
    {
        $order = $this->getOrder();
        return $order->getIncrementId();
    }

    public function getOrderValue()
    {
        $order = $this->getOrder();
        return number_format($order->getBaseSubtotalInclTax(), 2, '.', '');
    }

    public function getCouponCode()
    {
        $order = $this->getOrder();
        return $order->getCouponCode();
    }

    public function getBusinessId()
    {
        return $this->scopeConfig->getValue(
            'makeinfluence/general/business_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function submit($data)
    {
        $client = new \GuzzleHttp\Client();
        try {
            $response = $client->request('POST', self::MAKEINFLUENCE_TRACKING_URL, [
                'json' => $data
            ]);
            dump($response->getBody()->getContents());
        } catch (GuzzleException $exception) {
            dump($exception);
        }
    }
}

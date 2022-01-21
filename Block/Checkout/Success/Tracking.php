<?php namespace Wexo\MakeInfluence\Block\Checkout\Success;

use GuzzleHttp\Exception\GuzzleException;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Tracking extends \Magento\Framework\View\Element\Template
{
    private Session $checkoutSession;
    private ScopeConfigInterface $scopeConfig;
    private \Magento\Framework\App\RequestInterface $request;

    const MAKEINFLUENCE_TRACKING_URL = 'https://system.makeinfluence.com/track-conversion';
    public CookieManagerInterface $cookieManager;
    private LoggerInterface $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Session              $checkoutSession,
        RequestInterface     $request,
        CookieManagerInterface $cookieManager,
        LoggerInterface $logger,
        Template\Context     $context,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->cookieManager = $cookieManager;
        $this->logger = $logger;
    }

    /**
     * Responsible to preparing data needed to track affiliate orders
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

    /**
     * Verifies module is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->getValue(
            'makeinfluence/general/enabled',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    /**
     * @var \Magento\Sales\Model\Order|null
     */
    public $order = null;

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if($this->order === null)
            $this->order = $this->checkoutSession->getLastRealOrder();
        return $this->order;
    }

    /**
     * @return string|null
     */
    public function getOrderId()
    {
        $order = $this->getOrder();
        return $order->getIncrementId();
    }

    /**
     * @return string
     */
    public function getOrderValue()
    {
        $order = $this->getOrder();
        return number_format($order->getBaseSubtotalInclTax(), 2, '.', '');
    }

    /**
     * @return float|string|null
     */
    public function getCouponCode()
    {
        $order = $this->getOrder();
        return $order->getCouponCode();
    }

    /**
     * returns BusinessId set via Stores => Configuration => MakeInfluence => Configuration
     * @return string|null
     */
    public function getBusinessId()
    {
        return $this->scopeConfig->getValue(
            'makeinfluence/general/business_id',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Verifies if backend tracking is enabled via Stores => Configuration => MakeInfluence => Configuration
     * @return bool
     */
    public function isS2StrackingEnabled()
    {
        return $this->scopeConfig->getValue(
            'makeinfluence/general/s2s_tracking',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    /**
     * Verifies if frontend tracking is enabled via Stores => Configuration => MakeInfluence => Configuration
     * @return bool
     */
    public function isFrontendTrackingEnabled()
    {
        return $this->scopeConfig->getValue(
            'makeinfluence/general/frontend_tracking',
            ScopeInterface::SCOPE_STORE
        ) === '1';
    }

    /**
     * Based on data from \Wexo\MakeInfluence\Block\Checkout\Success\Tracking::prepareTrackingData
     * Sends a request to Make Influence with tracking information
     * @param $data
     * @return void
     */
    public function submit($data)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('POST', self::MAKEINFLUENCE_TRACKING_URL, [
                'json' => $data
            ]);
        } catch (GuzzleException $exception) {
            $this->logger->error('[\Wexo\MakeInfluence\Block\Checkout\Success\Tracking::submit] Exception' . $exception->getMessage());
        }
    }
}

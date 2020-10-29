<?php

namespace Humm\HummPaymentGateway\Block\Advert;

use Magento\Catalog\Block as CatalogBlock;
use Magento\Paypal\Helper\Shortcut\ValidatorInterface;
use Humm\HummPaymentGateway\Gateway\Config\Config;

/**
 * Class AbstractAdvert
 * @package Humm\HummPaymentGateway\Block\Advert
 * @author roger.bi@flexigroup.com.au
 */
abstract class AbstractAdvert extends \Magento\Framework\View\Element\Template
{
    /**
     * @var boolean
     */
    protected $_render = false;

    /**
     * @var Humm\HummPaymentGateway\Gateway\Config\Config;
     */

    protected $_config;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var string
     */
    protected $_alias = '';

    /**
     * @var array
     */
    protected $_configConstants = [
        'banner' => [
            'product' => Config::ADVERTS_PRODUCTPAGE_BANNER_ACTIVE,
            'cart' => Config::ADVERTS_CARTPAGE_BANNER_ACTIVE,
            'home' => Config::ADVERTS_HOMEPAGE_BANNER_ACTIVE,
            'homeUrl' => Config::ADVERTS_HOMEPAGEURL_BANNER_ACTIVE,

        ],
        'widget' => [
            'product' => Config::ADVERTS_PRODUCTPAGE_WIDGET_ACTIVE,
            'cart' => Config::ADVERTS_CARTPAGE_WIDGET_ACTIVE,
        ],
        'selector'=>[
            'product' => Config::ADVERTS_PRODUCTPAGE_WIDGET_ACTIVE,
            'cart' => Config::ADVERTS_CARTPAGE_WIDGET_ACTIVE,
        ]
    ];

    /**
     * AbstractAdvert constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param Config $config
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        Config $config,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_config = $config;
        $this->_registry = $registry;

    }

    /**
     * @return array
     */

    public function _getProductPrice()
    {

        $littleBig = $this->_config->getLittleBig();
        $blockProduct = $this->_getCurrentProduct();
        $blockPrice = $blockProduct->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        return [$blockPrice, $blockPrice > $littleBig];
    }

    /**
     * @return mixed
     */
    public function _getCurrentProduct()
    {
        return $this->_registry->registry('product');
    }

    /**
     * @param $widget
     * @param $page
     * @return bool
     */
    protected function _configShow($widget, $page)
    {

        $configPath = $this->_getConfigPath($widget, $page);
        return $this->_config->getConfigData($configPath);
    }

    /**
     * Returns the config path
     *
     * @return bool
     */
    protected function _getConfigPath($widget, $page)
    {
        if ($widget && $page)
            return isset($this->_configConstants[$widget][$page]) ? $this->_configConstants[$widget][$page] : null;
        else
            return null;
    }

    /**
     * @return mixed
     */
    public function _getCustomUrl()
    {
        return $this->_config->getCustomerUrl();

    }

    /**
     * @return mixed
     */
    public function _getCartTotal()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $objectManager->get('\Magento\Checkout\Model\Cart');

        $subTotal = $cart->getQuote()->getSubtotal();
        $grandTotal = $cart->getQuote()->getGrandTotal();

        return $grandTotal;
    }

    /**
     * @return string
     */

    public function _getCountry()
    {
        return $this->_config->getSpecificCountry();
    }

    /**
     * @return mixed
     */

    public function _getThreshold()
    {
        return $this->_config->getLittleBig();
    }


    /**
     * @return mixed
     */
    public function _getProductSeletor()
    {
        return $this->_config->getProductSelector();
    }

    /**
     * @return mixed
     */
    public function _getCartSeletor()
    {
        return $this->_config->getCartSelector();
    }

}

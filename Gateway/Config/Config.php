<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Humm\HummPaymentGateway\Gateway\Config;

/**
 * Roger.bi@flexigroup.com.au
 * Class Config.
 * Values returned from Magento\Payment\Gateway\Config\Config.getValue()
 * are taken by default from ScopeInterface::SCOPE_STORE
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const CODE = 'humm_gateway';
    const CONF_PREFIX = 'humm_conf' . DIRECTORY_SEPARATOR;
    const PLUGIN_VERSION = 'humm_plugin_version_placeholder';
    const KEY_ACTIVE = 'active';
    const KEY_MERCHANT_NUMBER = 'merchant_number';
    const KEY_API_KEY = 'api_key';
    const KEY_GATEWAY_URL = 'gateway_url';
    const KEY_DEBUG = 'debug';
    const KEY_SPECIFIC_COUNTRY = 'specificcountry';
    const KEY_HUMM_APPROVED_ORDER_STATUS = 'humm_approved_order_status';
    const KEY_EMAIL_CUSTOMER = 'email_customer';
    const KEY_AUTOMATIC_INVOICE = 'automatic_invoice';
    const KEY_IS_TESTING = 'is_testing';
    const KEY_LAUNCH_TIME = 'launch_time';
    const KEY_LAUNCH_TIME_UPDATED = 'launch_time_updated';
    const KEY_MIN_ORDER_TOTAL = 'min_order_total';
    const KEY_HUMM_LOGGER = 'humm_logger';
    const KEY_FORCE_HUMM = 'force_humm';

    const KEY_LITTLE_BIG = 'little_big';


    const ADVERTS_HOMEPAGE_HOMEPAGEURL = 'humm_advert/homepage/homepageurl';
    const ADVERTS_HOMEPAGE_BANNER_ACTIVE = 'humm_advert/homepage/banner';
    const ADVERTS_HOMEPAGEURL_BANNER_ACTIVE = 'humm_advert/homepage/homepageurl';
    const ADVERTS_PRODUCTPAGE_BANNER_ACTIVE = 'humm_advert/productpage/banner';
    const ADVERTS_PRODUCTPAGE_WIDGET_ACTIVE = 'humm_advert/productpage/widget';
    const ADVERTS_CARTPAGE_BANNER_ACTIVE = 'humm_advert/cartpage/banner';
    const ADVERTS_CARTPAGE_WIDGET_ACTIVE = 'humm_advert/cartpage/widget';

    /**
     * Get Merchant Number
     *
     * @return string
     */
    public function getMerchantNumber()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_MERCHANT_NUMBER);
    }

    /**
     * Get Launch Time Updated
     *
     * @return string
     */
    public function getLaunchTimeUpdated()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_LAUNCH_TIME_UPDATED);
    }

    /**
     * Get Logo
     *
     * @return string
     */
    public function getLogo()
    {
        return ($this->getTitle() == 'Humm') ? 'humm_logo.png' : 'oxipay_logo.png';
    }

    /**
     * Get Title
     *
     * @return string
     */
    public function getTitle()
    {
//        $is_after = ((time() - $this->getLaunchTime() >= 0) || $this->getForceHumm());
//
//        if ($this->getSpecificCountry() == 'NZ') {
//            $title = $is_after ? 'Humm' : 'Oxipay';
//        } else if ($this->getSpecificCountry() == 'AU') {
//            $title = 'Humm';
//        }

        return 'Humm';
    }

    /**
     * Get Launch Time
     *
     * @return string
     */
    public function getLaunchTime()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_LAUNCH_TIME);
    }

    /**
     *
     */
    public function getForceHumm()
    {
        return$this->getValue(self::CONF_PREFIX .self::KEY_FORCE_HUMM);
    }

    /**
     * Get specific country
     *
     * @return string
     */
    public function getSpecificCountry()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_SPECIFIC_COUNTRY);
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription()
    {
        return ($this->getTitle() == 'Humm') ? 'Pay in slices. No interest ever.' : 'Pay the easier way';
    }

    /**
     * Get Gateway URL
     *
     * @return string
     */
    public function getGatewayUrl()
    {
        $checkoutUrl = $this->getValue(self::CONF_PREFIX . self::KEY_GATEWAY_URL);
        if (isset($checkoutUrl) and strtolower(substr($checkoutUrl, 0, 4)) == 'http') {
            return $checkoutUrl;
        } else {
            $country_domain = $this->getSpecificCountry() == 'NZ' ? '.co.nz' : '.com.au';  // .com.au is the default value
            $title = $this->getTitle();

            $domainsTest = array(
                'Humm' => 'integration-cart.shophumm',
                'Oxipay' => 'securesandbox.oxipay'
            );
            $domains = array(
                'Humm' => 'cart.shophumm',
                'Oxipay' => 'secure.oxipay'
            );

            return 'https://' . ($this->isTesting() ? $domainsTest[$title] : $domains[$title]) . $country_domain . '/Checkout?platform=Default';
        }
    }

    /**
     * Get if doing test transactions (request send to sandbox gateway)
     *
     * @return boolean
     */
    public function isTesting()
    {
        return (bool)$this->getValue(self::CONF_PREFIX . self::KEY_IS_TESTING);
    }

    /**
     * get the humm refund gateway Url
     * @return string
     */
    public function getRefundUrl()
    {
        $country_domain = $this->getSpecificCountry() == 'NZ' ? '.co.nz' : '.com.au';  // .com.au is the default value
        $title = $this->getTitle();

        $domainsTest = array(
            'Humm' => 'integration-buyerapi.shophumm',
            'Oxipay' => 'portalssandbox.oxipay'
        );
        $domains = array(
            'Humm' => 'buyerapi.shophumm',
            'Oxipay' => 'portals.oxipay'
        );
        return 'https://' . ($this->isTesting() ? $domainsTest[$title] : $domains[$title]) . $country_domain . '/api/ExternalRefund/v1/processrefund';
    }

    /**
     * Get API Key
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_API_KEY);
    }

    /**
     * Get Humm Approved Order Status
     *
     * @return string
     */
    public function getHummApprovedOrderStatus()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_HUMM_APPROVED_ORDER_STATUS);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isEmailCustomer()
    {
        return (bool)$this->getValue(self::CONF_PREFIX . self::KEY_EMAIL_CUSTOMER);
    }

    /**
     * Check if customer is to be notified
     * @return boolean
     */
    public function isAutomaticInvoice()
    {
        return (bool)$this->getValue(self::CONF_PREFIX . self::KEY_AUTOMATIC_INVOICE);
    }

    /**
     * Get Payment configuration status
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getValue(self::KEY_ACTIVE);
    }

    /**
     * Get the version number of this plugin itself
     *
     * @return string
     */
    public function getVersion()
    {
        return self::PLUGIN_VERSION;
    }

    /**
     * @return mixed
     */
    public function getMinTotal()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_MIN_ORDER_TOTAL);
    }

    /**
     * @return mixed
     */
    public function getLittleBig()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_LITTLE_BIG);
    }

    /**
     * @param $configPath
     * @return bool
     */
    public function getConfigdata($configPath)
    {
        return $this->getValue($configPath);
    }

    /**
     * @return mixed
     */

    public function getCustomerUrl()
    {
        return $this->getValue(self::ADVERTS_HOMEPAGEURL_BANNER_ACTIVE);

    }

    /**
     * @return mixed
     */

    public function getDebug()
    {
        return $this->getValue(self::CONF_PREFIX . self::KEY_HUMM_LOGGER);
    }
}



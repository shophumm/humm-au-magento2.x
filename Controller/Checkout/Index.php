<?php

namespace Humm\HummPaymentGateway\Controller\Checkout;

use Magento\Sales\Model\Order;
use Humm\HummPaymentGateway\Helper\HummLogger;

/**
 * roger.bi@flexigroup.com.au
 * @package Humm\HummPaymentGateway\Controller\Checkout
 */
class Index extends AbstractAction
{

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        try {
            $order = $this->getOrder();
            if ($order->getState() !== Order::STATE_PENDING_PAYMENT) {
                $this->getHummLogger()->log('Order in state: ' . $order->getState());
            }
            $data = $this->getPayload($order);
            $payload = array(
                'action' => $this->getGatewayConfig()->getGatewayUrl(),
                'fields' => $data
            );
        } catch (Exception $ex) {
            $this->getHummLogger()->log('An exception was encountered in humm/checkout/index: ' . $ex->getMessage());
            $this->getHummLogger()->log($ex->getTraceAsString());
            $this->getMessageManager()->addErrorMessage(__('Unable to start humm Checkout.'));
        }
        $result = $this->_resultJsonFactory->create();
        $this->getHummLogger()->log("Transaction Start   Payload--" . json_encode($payload));
        return $result->setData($payload);
    }

    /**
     * @param $order
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getPayload($order)
    {
        if ($order == null) {
            $this->getHummLogger()->log('Unable to get order from last lodged order id. Possibly related to a failed database call');
            $this->_redirect('checkout/onepage/error', array('_secure' => false));
        }
        $billingAddress = $order->getBillingAddress();
        $billingAddressParts = preg_split('/\r\n|\r|\n/', $billingAddress->getData('street'));
        $shippingAddress = $order->getShippingAddress();
        if (!empty($shippingAddress)) {
            if ($shippingAddress->getData('street')) {
                $shippingAddressParts = preg_split('/\r\n|\r|\n/', $shippingAddress->getData('street'));
            } else {
                $shippingAddressParts = null;
            }
        } else {
            $shippingAddress = null;
        }

        $orderId = $order->getRealOrderId();
        $magento_version = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ProductMetadataInterface')->getVersion();
        $plugin_version = $this->getGatewayConfig()->getVersion();


        $data = array(
            'x_currency' => $order->getOrderCurrencyCode(),
            'x_url_callback' => $this->getDataHelper()->getCompleteUrl(),
//          'x_url_callback' =>  'http://abd63b9fed0e.ngrok.io/humm/checkout/success',
            'x_url_complete' => $this->getDataHelper()->getCompleteUrl(),
            'x_url_cancel' => $this->getDataHelper()->getCancelledUrl($orderId),
            'x_shop_name' => $this->getDataHelper()->getStoreCode(),
            'x_account_id' => $this->getGatewayConfig()->getMerchantNumber(),
            'x_reference' => $orderId,
            'x_invoice' => $orderId,
            'x_amount' => $order->getTotalDue(),
            'x_customer_first_name' => $order->getCustomerFirstname(),
            'x_customer_last_name' => $order->getCustomerLastname(),
            'x_customer_email' => $order->getData('customer_email'),
            'x_customer_phone' => $billingAddress->getData('telephone'),
            'x_customer_billing_address1' => $billingAddressParts[0],
            'x_customer_billing_address2' => count($billingAddressParts) > 1 ? $billingAddressParts[1] : '',
            'x_customer_billing_city' => $billingAddress->getData('city'),
            'x_customer_billing_state' => $billingAddress->getData('region'),
            'x_customer_billing_zip' => $billingAddress->getData('postcode'),
            'x_customer_shipping_address1' => (empty($shippingAddressParts)) ? "" : $shippingAddressParts[0],
            'x_customer_shipping_address2' => (empty($shippingAddressParts)) ? "" : (count($shippingAddressParts) > 1 ? $shippingAddressParts[1] : ''),
            'x_customer_shipping_city' => (empty($shippingAddress)) ? "" : $shippingAddress->getData('city'),
            'x_customer_shipping_state' => (empty($shippingAddress)) ? "" : $shippingAddress->getData('region'),
            'x_customer_shipping_zip' => (empty($shippingAddress)) ? "" : $shippingAddress->getData('postcode'),
            'version_info' => 'Humm_' . $plugin_version . '_on_magento' . substr($magento_version, 0, 3),
            'x_test' => 'false',
            'x_transaction_timeout' => (intval($this->getGatewayConfig()->getConfigdata('humm_conf/api_timeout')) < 1440) ? intval($this->getGatewayConfig()->getConfigdata('humm_conf/api_timeout')) : 1440,
        );

        foreach ($data as $key => $value) {
            $data[$key] = preg_replace('/\r\n|\r|\n/', ' ', $value);
        }
        $apiKeyEnc = $this->getGatewayConfig()->getApiKey();
        $apiKey = $this->_encrypted->processValue($apiKeyEnc);
        $signature = $this->getCryptoHelper()->generateSignature($data, $apiKey);
        $data['x_signature'] = $signature;
        $payment = $order->getPayment()
            ->setAdditionalInformation(array($orderId => "Pending before redirect"));;
        $order->save();
        return $data;
    }
}

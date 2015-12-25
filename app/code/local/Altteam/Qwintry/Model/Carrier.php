<?php

class Altteam_Qwintry_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    protected $_code = 'altteam_qwintry';

    public function getFormBlock()
    {
        return 'altteam_qwintry/pickup';
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    public function getPickupPoints()
    {
        $country = Mage::getSingleton('checkout/type_onepage')->getQuote()->getShippingAddress()->getCountry();
        return Mage::helper('altteam_qwintry')->getPickupPoints($country);
    }

    public function getCountryData()
    {
        $country = Mage::getSingleton('checkout/type_onepage')->getQuote()->getShippingAddress()->getCountry();
        return Mage::helper('altteam_qwintry')->getCountryData($country);
    }

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        $result = Mage::getModel('shipping/rate_result');

        $result->append($this->_getPickupRate($request));
        $result->append($this->_getCourierRate($request));

        return $result;
    }

    public function getAllowedMethods()
    {
        return array(
            'courier' => 'Courier',
            'pickup' => 'Pickup',
        );
    }

    protected function _getPickupRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $package_cost = $request->getPackageValueWithDiscount();

        $shipping_settings = Mage::helper('altteam_qwintry')->getShippingSettings();

        $pounds = $request->getPackageWeight();

        $currencies = Mage::getModel('directory/currency')
            ->getConfigAllowCurrencies();

        $currency = $request->getPackageCurrency();

        if (in_array('USD', $currencies)) {
            Mage::helper('directory')->currencyConvert($package_cost, $currency->getCurrencyCode(), 'USD');
        } elseif ($currency->getCurrencyCode() == 'EUR') {
            $package_cost = $package_cost * 1.097;
        } elseif ($currency->getCurrencyCode() == 'RMB') {
            $package_cost = $package_cost * 1.157;
        }

        $data = array(
            'params' => array(
                'insurance' => false,
                'retail_pricing' => false,
                'weight' => $pounds > 0.1 ? $pounds : (empty($shipping_settings['default_weight']) ? 4 : $shipping_settings['default_weight']),
                'items_value' => $package_cost,
                'delivery_pickup' => $this->_getPickupPoint()
            )
        );

        $response = Mage::helper('altteam_qwintry')->sendApiRequest('cost', $data);

        if (!$response || empty($response->success) || !$response->success) {
            return false;
        }

        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('pickup');
        $rate->setMethodTitle('Pickup');
        $rate->setPrice($response->result->total);
        $rate->setCost(0);

        return $rate;

    }

    protected function _getCourierRate(Mage_Shipping_Model_Rate_Request $request)
    {
        $package_cost = $request->getPackageValueWithDiscount();

        $shipping_settings = Mage::helper('altteam_qwintry')->getShippingSettings();

        $pounds = $request->getPackageWeight();

        $currencies = Mage::getModel('directory/currency')
            ->getConfigAllowCurrencies();

        $currency = $request->getPackageCurrency();

        if (in_array('USD', $currencies)) {
            Mage::helper('directory')->currencyConvert($package_cost, $currency->getCurrencyCode(), 'USD');
        } elseif ($currency->getCurrencyCode() == 'EUR') {
            $package_cost = $package_cost * 1.097;
        } elseif ($currency->getCurrencyCode() == 'RMB') {
            $package_cost = $package_cost * 1.157;
        }


        $data = array(
            'params' => array(
                'method' => 'qwair',
                'hub_code' => empty($shipping_settings['hub']) ? 'DE1' : $shipping_settings['hub'],
                'insurance' => false,
                'retail_pricing' => false,
                'weight' => $pounds > 0.1 ? $pounds : (empty($shipping_settings['default_weight']) ? 4 : $shipping_settings['default_weight']),
                'items_value' => $package_cost,
                'addr_country' => $request->getDestCountryId(),
                'addr_zip' => $request->getDestPostcode(),
                'addr_line1' => $request->getDestStreet(),
                'addr_line2' => '',
                'addr_city' => $request->getDestCity(),
                'addr_state' => $request->getDestRegionCode()
            )
        );

        $response = Mage::helper('altteam_qwintry')->sendApiRequest('cost', $data);

        if (!$response || empty($response->success) || !$response->success) {
            return false;
        }

        $rate = Mage::getModel('shipping/rate_result_method');

        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('courier');
        $rate->setMethodTitle('Courier');
        $rate->setPrice($response->result->total);
        $rate->setCost(0);

        return $rate;
    }

    protected function _getPickupPoint($country)
    {
        $quote = Mage::getSingleton('checkout/type_onepage')->getQuote();
        if ($quote->getId()) {
            $quote_id = $quote->getId();
            $pickup = Mage::getSingleton('checkout/session')->getPickup();
            if (isset($pickup[$quote_id])) {
                return $pickup[$quote_id]['store'];
            }
        }
        $pickup_points = $this->getPickupPoints();
        return $pickup_points[0]['code'];
    }
}
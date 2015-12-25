<?php

class Altteam_Qwintry_Helper_Data extends
    Mage_Core_Helper_Abstract
{
    const QWINTRY_SITE_URL = 'logistics.qwintry.com';
    const QWINTRY_DIR_INVOICES = '/qwintry/invoices/';
    const QWINTRY_DIR_LABELS = '/qwintry/labels/';

    const QWINTRY_API_KEY = 'carriers/altteam_qwintry/api_key';
    const QWINTRY_MODE = 'carriers/altteam_qwintry/mode';
    const QWINTRY_HUB = 'carriers/altteam_qwintry/hub';
    const QWINTRY_WEIGHT = 'carriers/altteam_qwintry/weight';


    public function getShippingSettings()
    {
        return array(
            'mode' => Mage::getStoreConfig(self::QWINTRY_MODE),
            'hub' => Mage::getStoreConfig(self::QWINTRY_HUB),
            'default_weight' => Mage::getStoreConfig(self::QWINTRY_WEIGHT)
        );
    }

    public function getHubs()
    {
        $result = $this->sendApiRequest('hubs-list', array());

        if (!$result && !$result->success && empty($result->results)) return false;
        foreach ($result->results as $hub) {
            $hubs[] = array(
                'value' => (string)$hub->code,
                'label' => (string)$hub->name
            );
        }
        return empty($hubs) ? false : $hubs;
    }

    public function getPickupPoints($country)
    {
        $result = $this->sendApiRequest('locations-list', array());
        if (!$result && !$result->success && empty($result->result)) return false;
        foreach ($result->result as $city_name => $city) {
            if (empty($city->pickup_points) || $city->country != $country) continue;
            $k = 1;
            foreach ($city->pickup_points as $code => $point) {
                $points[] = array(
                    'code' => (string)$code,
                    'name' => (string)$city_name . ' №' . $k . '. ' . (string)$point->addr
                );
                $k++;
            }

        }
        return empty($points) ? false : $points;
    }

    public function getAddressByPickupPoint($pickup_point)
    {
        $result = $this->sendApiRequest('locations-list', array());
        if (!$result && !$result->success && empty($result->result)) return false;
        foreach ($result->result as $city_name => $city) {
            if (empty($city->pickup_points)) continue;
            foreach ($city->pickup_points as $code => $point) {
                if ($code == $pickup_point) return (string)$city_name . '. ' . (string)$point->addr;
            }

        }
        return false;
    }

    public function getCountryData($country)
    {
        $result = $this->sendApiRequest("countries-list?country=" . $country, array());
        if (!$result && !$result->success && empty($result->result) && empty($result->result->{$country})) return false;
        foreach ($result->result->{$country} as $key => $row) {
            if (empty($row) || !is_string($row)) continue;

            $data[] = array(
                'header' => __('qwintry_' . $key),
                'content' => $row,
                'bold' => in_array($key, array('lazy_workflow'))
            );
        }
        return empty($data) ? false : $data;
    }

    public function sendApiRequest($function, $data, $method = 'get')
    {

        $api_key = Mage::getStoreConfig(self::QWINTRY_API_KEY);

        if (empty($api_key)) return false;

        $url = 'http://' . self::QWINTRY_SITE_URL . '/api/' . $function;
        $data_string = http_build_query($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) return false;

        return json_decode($response);
    }

    function createShipment($qwintry_data = array())
    {

        $order = Mage::getModel('sales/order')->loadByIncrementId($qwintry_data['order_id']);

        $shipping_settings = $this->getShippingSettings();

        $dimensions = array(
            'box_length' => 10,
            'box_width' => 10,
            'box_height' => 10
        );

        $shippingId = $order->getShippingAddress()->getId();

        $address = Mage::getModel('sales/order_address')->load($shippingId);

        $orderedItems = $order->getAllVisibleItems();

        if ($order->hasInvoices()) {
            $invoice = $order->getInvoiceCollection()->getFirstItem();
            $pdf = Mage::getModel('sales/order_pdf_invoice')->getPdf(array($invoice));
        }

        $pounds = $order->getWeight();

        if (!empty($qwintry_data['box_length']) && !empty($qwintry_data['box_width']) && !empty($qwintry_data['box_height']))
            $dimensions = $qwintry_data;

        if (!empty($qwintry_data['box_weight'])) $pounds = $qwintry_data['box_weight'];

        $data = array(
            'Shipment' => array(
                'first_name' => $address->getFirstname(),
                'last_name' => $address->getLastname(),
                'phone' => $address->getTelephone(),
                'email' => $address->getEmail(),
                'customer_notes' => '',
                'weight' => $pounds > 0.1 ? $pounds : (empty($shipping_settings['default_weight']) ? 4 : $shipping_settings['default_weight']),
                'dimensions' => $dimensions['box_length'] . 'x' . $dimensions['box_width'] . 'x' . $dimensions['box_height'],
                'insurance' => false,
                'external_id' => $qwintry_data['order_id'],
                'hub_code' => empty($shipping_settings['hub']) ? 'DE1' : $shipping_settings['hub']
            )
        );

        if (isset($pdf)) {
            $data['Shipment']['invoices'] = array(
                0 => array(
                    'base64_data' => base64_encode($pdf->render()),
                    'base64_extension' => 'pdf',
                ),
            );
        }
        $street = $address->getStreet();
        $region = $address->getRegion();
        $data['Shipment']['addr_line1'] = $street[0];
        $data['Shipment']['addr_line2'] = '';
        $data['Shipment']['addr_zip'] = $address->getPostcode();
        $data['Shipment']['addr_state'] = empty($region) ? '' : $region;
        $data['Shipment']['addr_city'] = $address->getCity();
        $data['Shipment']['addr_country'] = $address->getCountryId();

        $pickupObject = $order->getPickupObject();
        if ($pickupObject && $order->getShippingMethod() == 'altteam_qwintry_pickup') {
            $data['Shipment']['delivery_type'] = 'pickup';
            $data['Shipment']['delivery_pickup'] = $pickupObject->getStore();
        } else {
            $data['Shipment']['delivery_type'] = 'courier';
        }

        if ($shipping_settings['mode'] == 'test') {
            $data['Shipment']['test'] = true;
        }


        foreach ($orderedItems as $item) {
            $item_weight = $item->getWeight();
            $data['items'][] = array(
                'descr' => $item->getName(),
                'descr_ru' => $item->getName(),
                'count' => floatval($item->getQtyOrdered()),
                'line_value' => floatval($this->getPrice($item->getPrice(), 'USD')),
                'line_weight' => floatval(empty($item_weight) ? 0.1 : $item_weight)
            );
        }

        $result = $this->sendApiRequest('package-create', $data);

        if (!$result || empty($result->success) || !$result->success || empty($result->result->tracking)) {
            if (empty($result->errorMessage)) return false;
            return array('[error]' => (string)$result->errorMessage);
        }

        try {
            $shipment = new Mage_Sales_Model_Order_Shipment_Api();
            $shipmentId = $shipment->create($qwintry_data['order_id']);
            $shipment->addTrack($shipmentId, 'altteam_qwintry', 'Tracking ID', $result->result->tracking);
        } catch (Exception $e) {
        }

        if ($this->saveLabel($qwintry_data['order_id'] . '.pdf', $result->result->tracking) !== false) {
            return true;
        }

        return false;
    }

    function getPrice($price, $currency)
    {
        $currencies = Mage::getModel('directory/currency')
            ->getConfigAllowCurrencies();

        if (in_array('USD', $currencies)) {
            Mage::helper('directory')->currencyConvert($price, $currency, 'USD');
        } elseif ($currency == 'EUR') {
            $price = $price * 1.097;
        } elseif ($currency == 'RMB') {
            $price = $price * 1.157;
        }
        return $price;
    }

    function saveLabel($filename, $tracking)
    {

        $api_key = Mage::getStoreConfig(self::QWINTRY_API_KEY);

        if (empty($api_key)) return false;

        $url = 'http://' . self::QWINTRY_SITE_URL . '/api/package-label?tracking=' . $tracking;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $response = curl_exec($ch);

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        if ($content_type == 'application/pdf' && $http_status == 200) {
            return file_put_contents(Mage::getBaseDir('media') . self::QWINTRY_DIR_LABELS . $filename, $response);
        } else {
            return false;
        }
    }

    function checkLabel($order_id)
    {
        return file_exists(Mage::getBaseDir('media') . self::QWINTRY_DIR_LABELS . $order_id . '.pdf');
    }
}
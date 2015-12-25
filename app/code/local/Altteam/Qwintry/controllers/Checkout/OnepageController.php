<?php

require_once 'Mage/Checkout/controllers/OnepageController.php';

class Altteam_Qwintry_Checkout_OnepageController extends Mage_Checkout_OnepageController
{
    public function updateStoreAction()
    {
        if ($this->_expireAjax()) {
            return;
        }
        if ($this->getRequest()->isPost()) {

            $data = $this->getRequest()->getPost('shipping_method', '');
            $result = $this->getOnepage()->saveShippingMethod($data);

            if (!$result) {
                $quote = $this->getOnepage()->getQuote();
                $quote->getShippingAddress()->setCollectShippingRates(true);
                Mage::dispatchEvent(
                    'checkout_controller_onepage_save_shipping_method',
                    array(
                        'request' => $this->getRequest(),
                        'quote' => $quote));
                if ($quote->getId()) {
                    $quote_id = $quote->getId();
                    $pickup = Mage::getSingleton('checkout/session')->getPickup();
                    if (isset($pickup[$quote_id])) {
                        $data = $pickup[$quote_id];
                        $quote->setPickupData($data);
                    }
                }
                $quote->collectTotals();
                $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));

                $result['goto_section'] = 'shipping_method';
                $result['update_section'] = array(
                    'name' => 'shipping-method',
                    'html' => $this->_getShippingMethodsHtml()
                );
            }

            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }
}

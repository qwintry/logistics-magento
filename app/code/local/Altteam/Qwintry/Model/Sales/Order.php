<?php

class Altteam_Qwintry_Model_Sales_Order extends Mage_Sales_Model_Order
{
    public function getShippingDescription()
    {
        $desc = parent::getShippingDescription();
        $pickupObject = $this->getPickupObject();
        if ($pickupObject && $this->getShippingMethod() == 'altteam_qwintry_pickup') {
            $desc .= ' ' . Mage::helper('altteam_qwintry')->getAddressByPickupPoint($pickupObject->getStore());
        }
        return $desc;
    }
}
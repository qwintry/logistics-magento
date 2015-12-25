<?php

class Altteam_Qwintry_Block_Pickup extends Mage_Checkout_Block_Onepage_Shipping_Method_Available
{
    public function __construct()
    {
        $this->setTemplate('qwintry/pickup.phtml');
    }
}
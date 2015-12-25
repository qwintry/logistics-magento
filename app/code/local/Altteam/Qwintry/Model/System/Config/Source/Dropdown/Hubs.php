<?php

class Altteam_Qwintry_Model_System_Config_Source_Dropdown_Hubs
{
    public function toOptionArray()
    {
        return Mage::helper('altteam_qwintry')->getHubs();
    }
}
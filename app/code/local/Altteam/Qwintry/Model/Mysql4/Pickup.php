<?php

class Altteam_Qwintry_Model_Mysql4_Pickup extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('altteam_qwintry/pickup', 'id');
    }
}
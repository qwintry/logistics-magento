<?php

class Altteam_Qwintry_Model_System_Config_Source_Dropdown_Modes
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'test',
                'label' => 'Test',
            ),
            array(
                'value' => 'live',
                'label' => 'Live',
            ),
        );
    }
}
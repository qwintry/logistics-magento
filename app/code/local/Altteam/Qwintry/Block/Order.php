<?php

class Altteam_Qwintry_Block_Order extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function __construct()
    {
        $this->setTemplate('qwintry/order.phtml');
    }

    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id' => 'submit_qwintry_button',
                'label' => Mage::helper('sales')->__('Create shipment in Qwintry Air'),
                'class' => 'save'
            ));
        $this->setChild('submit_button', $button);

        return parent::_prepareLayout();
    }

    public function getTabLabel()
    {
        return $this->__('Qwintry Air');
    }

    public function getTabTitle()
    {
        return $this->__('Qwintry Air');
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        if (strpos($this->getOrder()->getShippingMethod(), 'altteam_qwintry') !== false) {
            return false;
        }
        return true;
    }

    public function getOrder()
    {
        return Mage::registry('current_order');
    }

    public function getSubmitUrl()
    {
        return $this->getUrl('*/*/qwintry');
    }
}
<?php

require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';

class Altteam_Qwintry_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{
    protected $_publicActions = array('view', 'index', 'qwintry');

    public function qwintryAction()
    {
        $qwintry_data = $this->getRequest()->getParam('qwintry_data');
        if (Mage::helper('altteam_qwintry')->createShipment($qwintry_data)) {
            echo "<a href=\"" . Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL) . 'media/qwintry/labels/' . $qwintry_data['order_id'] . '.pdf' . "\" >Download label (PDF)</a>";
        }
        $this->renderLayout();
    }
}

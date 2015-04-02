<?php
class FlexShopper_Payment_Helper_Data extends Mage_Core_helper_Abstract
{
    public function deleteallCartItems() {
        $cartHelper = Mage::helper('checkout/cart');
        $items = $cartHelper->getCart()->getItems();
        foreach ($items as $item) {
            $itemId = $item->getItemId();
            $cartHelper->getCart()->removeItem($itemId)->save();
        }
    }

    function getErrorMessage($errorCode)
    {
        switch ($errorCode) {
            case "order_mismatch" : $result = "Mismatch in transaction ID.";
                break;
            default : $result = "Error Unknown";
        }

        return $result;    	
    }

    public function getProductAttributeText($product, $attributeCode)
    {
        try {
            $a = $product->getResource()
                ->getAttribute($attributeCode)
                ->getSource()
                ->getOptionText($product->getData($attributeCode));
            $a = trim($a);
        } catch (Exception $e) {
            $a = null;
        }
        return !empty($a) ? $a : null;
    }

    public function getManufacturer($product)
    {
        $default = Mage::getStoreConfig('payment/flexpayment/default_manufacturer');
        $attributes = array_filter(explode(',', Mage::getStoreConfig('payment/flexpayment/manufacturer_attributes')));

        foreach ($attributes as $a){
            $manufacturer = $this->getProductAttributeText($product, $a);
            if (!empty($manufacturer)){
                return $manufacturer;
            }
            if (!empty($default)){
                return $default;
            }
            return '';
        }
    }
}
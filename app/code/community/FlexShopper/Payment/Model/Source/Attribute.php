<?php

class FlexShopper_Payment_Model_Source_Attribute
{
    public function toOptionArray()
    {
        $attributes = Mage::getModel('catalog/product')->getAttributes();
        $attributeArray = array();

        foreach ($attributes as $a) {
            foreach ($a->getEntityType()->getAttributeCodes() as $attributeName) {
                $attributeArray[] = array(
                    'label' => $attributeName,
                    'value' => $attributeName
                );
            }
            break;
        }
        return $attributeArray;
    }

    public function toArray()
    {
        $attributes = Mage::getModel('catalog/product')->getAttributes();
        $attributeArray = array();

        foreach ($attributes as $a) {
            foreach ($a->getEntityType()->getAttributeCodes() as $attributeName) {
                $attributeArray[$attributeName] = $attributeName;
            }
            break;
        }
        return $attributeArray;
    }
}
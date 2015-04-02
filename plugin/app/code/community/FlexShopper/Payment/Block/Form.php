<?php

class FlexShopper_Payment_Block_Form extends Mage_Payment_Block_Form
{
   protected function _construct()
    {
        $logo = Mage::getConfig()->getBlockClassName('core/template');
        $logo = new $logo;
        $logo->setTemplate('flexshopper/payment/logo.phtml')
            ->setFlexHref('http://www.flexshopper.com/')
            ->setFlexLogoSrc('http://cdn.flexshopper.com/media/wysiwyg/plugin/fs_badge_pay.png');
        $this->setTemplate('flexshopper/payment/form.phtml')
            ->setRedirectMessage(
                //Mage::helper('flexpayment')->__('You will be redirected to the FlexShopper website.')
            )
            ->setMethodTitle('')
            ->setMethodLabelAfterHtml($logo->toHtml())
        ;
        return parent::_construct();
    }
}

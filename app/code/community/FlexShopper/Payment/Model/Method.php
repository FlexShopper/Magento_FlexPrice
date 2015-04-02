<?php
class FlexShopper_Payment_Model_Method extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'flexpayment';

    protected $_isInitializeNeeded      = false;
    protected $_canUseInternal          = false;
    protected $_canUseForMultishipping  = true;
    protected $_canVoid                 = true;
    protected $_canUseCheckout          = true;
    protected $_canFetchTransactionInfo = true;
    protected $_isGateway               = true;


    protected $_formBlockType 			= "flexpayment/form";

    public function getOrderPlaceRedirectUrl() {
    	$quote = Mage::getSingleton('checkout/session')->getQuote();
    	$quote->setIsActive(1);
    	$quote->save();
        return Mage::getUrl('flexshopper/payment/redirect', array('_secure' => false));
    }

    public function getTitle()
    {
        return 'FlexShopper';
    }
}
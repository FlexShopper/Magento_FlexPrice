<?php
class FlexShopper_Payment_Block_Widget extends Mage_Catalog_Block_Product_View
{
	public function _construct()
	{
		parent::_construct();
	}

	public function showWidget()
	{
		return Mage::getStoreConfig('payment/flexpayment/show_flexshopper_widget');
	}
}
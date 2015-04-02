<?php
class FlexShopper_Payment_Model_Observer
{
	public function removeTaxes($observer)
	{
        $includeTax = false; //Mage::getStoreConfig('payment/flexpayment/include_tax');
        /* @var $quote Mage_Sales_Model_Quote */
        $quote = $observer->getEvent()->getQuote();
        $payment = $quote->getPayment();

        if(!$includeTax){
            if($payment){
                $method = $payment->getMethod();
                if($method == 'flexpayment'){
                    foreach ($quote->getAllItems() as $item) {
                        $item->getProduct()->setTaxClassId(0);
                    }                
                }
            }
        }

        return $this;
	}

    public function refundOrder($observer)
    {
        $creditMemo = $observer->getEvent()->getCreditmemo();
        $totalRefunded      = (float)$creditMemo->getOrder()->getBaseTotalRefunded();
        $amount             = (float)$creditMemo->getBaseGrandTotal();
        $subtotalRefunded   = (float)$creditMemo->getOrder()->getBaseSubtotalRefunded();
        $subtotalInvoiced   = (float)$creditMemo->getOrder()->getBaseSubtotalInvoiced();

        $payment = $creditMemo->getOrder()->getPayment();

        if($payment){
            $method = $payment->getMethod();
            if($method == 'flexpayment'){
                $shippingRefunded = (float)$creditMemo->getOrder()->getData('shipping_refunded');
                $shippingAllowRefund = false;

                if($amount == $shippingRefunded){
                    $type = 'SHIPPING';
                    $shippingAllowRefund = true;
                }

                if($amount <= $subtotalInvoiced){
                    $type = 'STOCK';
                }

                if($type == 'STOCK' && $shippingAllowRefund){
                    $type = 'ALL';
                }

                $orderId = $creditMemo->getOrder()->getId();
                $refund = Mage::getModel('flexpayment/api')->refundFlexshopperAmount($orderId, $amount, $type);
            }
        }

        return $this;
    }

    public function isFlexshopperActive($observer)
    {   
        $method     = $observer->getEvent()->getMethodInstance();
        $result     = $observer->getEvent()->getResult();
         
        $items      = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();

        if($method->getCode() == 'flexpayment' ){
            foreach ($items as $item) {
                $type = $item->getProductType();

                if($type == 'virtual' || $type == 'downloadable'){
                    $result->isAvailable = false;
                    break;
                }
            }
        }

        return $this;    
    }

    public function cancelTransaction($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getId();

        $payment = $order->getPayment();

        if($payment){
            $method = $payment->getMethod();
            if($method == 'flexpayment'){
                $cancelFlexShopper = Mage::getModel('flexpayment/api')->cancelFlexshopperTransaction($orderId);
            }
        }

        return $this;       
    }
    

    public function checkLimit($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $grandTotal = (float)$quote->getBaseGrandTotal();

        $payment = $quote->getPayment();

        if($payment){
            $method = $payment->getMethod();
            if($method == 'flexpayment'){
                if($grandTotal < 49.95){
                    Mage::throwException(Mage::helper('flexpayment')->__('Your total is less than the minimum amount required by Flexshopper.'));
                }
            }
        }

        return $this;
    }
}
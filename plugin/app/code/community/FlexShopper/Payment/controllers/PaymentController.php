<?php

class FlexShopper_Payment_PaymentController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
		return;
    }

	// The redirect action is triggered when someone places an order
    public function redirectAction() {

		$is_active 		= Mage::getStoreConfig('payment/flexpayment/active');
        $test_mode 		= Mage::getStoreConfig('payment/flexpayment/sandbox_mode');
        $retailer_id 	= Mage::getStoreConfig('payment/flexpayment/retailer_id');
        $retailer_token = Mage::getStoreConfig('payment/flexpayment/retailer_token');
        $action_gateway = Mage::helper('flexpayment/api')->getGatewayUrl();

        //Loading current layout
        $this->loadLayout();
        //Creating a new block
        $block = $this->getLayout()->createBlock(
			'Mage_Core_Block_Template', 'flexshopper_payment_block_redirect', array('template' => 'flexshopper/payment/redirect.phtml')
        )
        ->setData('retailer_id', $retailer_id)
        ->setData('retailer_token', $retailer_token)
        ->setData('action_gateway', $action_gateway);

        $this->getLayout()->getBlock('content')->append($block);

        //Now showing it with rendering of layout
        $this->renderLayout();
    }

    public function responsefailAction()
    {
		$order_id_from_session	= Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order 					= Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
		$order_id_from_session 	= $order->getId();

		$order_id_from_flex 	= $this->getRequest()->getParam('order');

		$error = false;
		$response_type = 'accept';
		$order_id_from_flex_converted 	= Mage::helper('flexpayment/api')->getMagentoOrder($order_id_from_flex);

		if(trim($order_id_from_session) != trim($order_id_from_flex_converted)){
			$response_status_message = $this->__(Mage::helper('flexpayment')->getErrorMessage('order_mismatch'));
			$response_type = 'decline';
		}

		$data = Mage::getModel('flexpayment/api')->getTransactionStatus($order_id_from_flex);
		if(is_array($data)){
			$response_status_message = $data['message'];
			$response_type = 'cancel';
		}

		Mage::log('Order ID:' . $order_id_from_session . ' - ' . $data['system'], null, 'flexpayment.log');		

        switch($response_type):
			case 'cancel':
		    	$quote = Mage::getSingleton('checkout/session')->getQuote();
		    	$quote->setIsActive(0);
		    	$quote->save();
				// There is a problem in the response we got
				$this->cancelAction();

				Mage::getSingleton('checkout/session')->setErrorMessage($response_status_message);
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
				return;
			break;
			case 'decline':
		    	$quote = Mage::getSingleton('checkout/session')->getQuote();
		    	$quote->setIsActive(0);
		    	$quote->save();
				// There is a problem in the response we got
				$this->cancelAction();

				Mage::getSingleton('checkout/session')->setErrorMessage($response_status_message);
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
				return;
			break;
			default:
				$response_message = $this->__('Response Unknown');
				Mage::getSingleton('checkout/session')->setErrorMessage($response_message);
				return;
			break;
		endswitch;
    }

    public function responseAction() {
		$order_id_from_session	= Mage::getSingleton('checkout/session')->getLastRealOrderId();
        $order 					= Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
        $order_id_from_session 	= $order->getId();
        	
		$order_id_from_flex 	= $this->getRequest()->getParam('order');
		$error = false;
		$response_type = 'accept';
		$order_id_from_flex_converted 	= Mage::helper('flexpayment/api')->getMagentoOrder($order_id_from_flex);
		if(trim($order_id_from_session) != trim($order_id_from_flex_converted)){
			$response_status_message = $this->__(Mage::helper('flexpayment')->getErrorMessage('order_mismatch'));
			$response_type = 'cancel';
		}

		$data = Mage::getModel('flexpayment/api')->getTransactionStatus($order_id_from_flex);
		if(is_array($data)){
			if($data['status'] == 'ok'){
				$response_status_message = $data['message'];
				$response_type = 'accept';
			} else {
				$response_status_message = $data['message'];
				$response_type = 'cancel';
			}
		}

        switch($response_type):
			case 'accept':
			/** trying to create invoice * */
			try {
				if (!$order->canInvoice()):
					$response_status_message = $this->__('Error: cannot create an invoice !');
					Mage::getSingleton('checkout/session')->setErrorMessage($response_status_message);
					Mage::log('Order ID:' . $order_id_from_flex_converted . ' - ' . $response_status_message, null, 'flexpayment.log');	
					return false;
				else:
					$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
					if (!$invoice->getTotalQty()):
						$response_status_message = $this->__('Error: cannot create an invoice without products !');
						Mage::getSingleton('checkout/session')->setErrorMessage($response_status_message);
						Mage::log('Order ID:' . $order_id_from_flex_converted . ' - ' . $response_status_message, null, 'flexpayment.log');
						return false;
					endif;
					$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
					$invoice->register();
					$transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
					$transactionSave->save();

			    	$quote = Mage::getSingleton('checkout/session')->getQuote();
			    	$quote->setIsActive(0);
			    	$quote->save();

					$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'FlexShopper has accepted the payment.');
				endif;
                } catch (Mage_Core_Exception $e) {
					//Mage::throwException(Mage::helper('core')->__('cannot create an invoice !'));
				}

				//$order->sendNewOrderEmail();
				$order->setEmailSent(true);
				$order->save();

				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure' => true));
				return;
			break;
			case 'cancel':
				// There is a problem in the response we got
				$this->cancelAction();
				// $response_status_message = Mage::helper('payfort/data')->getResponseCodeDescription($response_status);
				Mage::getSingleton('checkout/session')->setErrorMessage($response_status_message);
				Mage::log('Order ID:' . $order_id_from_flex_converted . ' - ' . $response_status_message, null, 'flexpayment.log');
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure' => true));
				return;
			break;
			default:
				$response_message = $this->__('Response Unknown');
				Mage::getSingleton('checkout/session')->setErrorMessage($response_message);
				return;
			break;
		endswitch;

    }

    // The cancel action is triggered when an order is to be cancelled
    public function cancelAction() {   	
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if ($order->getId()) {
                // Flag the order as 'cancelled' and save it
                $order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'FlexShopper has declined the payment.')->save();
            }
        }
    }

    public function successAction() {
        /**/
    }

 //    public function renderResponse($response_message) {
	// 	$this->loadLayout();
	// 	//Creating a new block
	// 	$block = $this->getLayout()->createBlock(
	// 		'Mage_Core_Block_Template', 'flexshopper_payment_block_response', array('template' => 'flexshopper/payment/response.phtml')
	// 	)
	// 	->setData('response_message', $response_message);

	// 	$this->getLayout()->getBlock('content')->append($block);

	// 	//Now showing it with rendering of layout
	// 	$this->renderLayout();
	// }
}
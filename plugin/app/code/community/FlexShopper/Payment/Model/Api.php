<?php
class FlexShopper_Payment_Model_Api extends Mage_Core_Model_Abstract
{
	public function getTransactionStatus($orderId)
	{
		$oneReq 	= Mage::helper('flexpayment/api');
		$endPoint 	= 'result';
		$body 		= array('TransactionID' => $orderId);
		$result 	= $oneReq->setData($body)->setEndpoint($endPoint)->getFlex();

		if(isset($result['status'])){
			$friendlyMessage 	= $oneReq->getResponseCodeDescription($result['status']);

			$systemMessage = '';
			foreach ($result as $key => $value) {
				if($key != 'items'){
					if($key == 'status'){
						$systemMessage .= "\n Status: " . $value; 
					}

					if($key == 'reference_id'){
						$systemMessage .= "\n Reference ID: " . $value; 
					}

					if($key == 'agreement_amount'){
						$systemMessage .= "\n Agreement Amount: " . $value; 
					}

					if($key == 'total_weekly_payment'){
						$systemMessage .= "\n Total Weekly Payment: " . $value; 
					}
				}
			}

			$systemMessage .=  "Description: " . $friendlyMessage;

			try{
				$orderId 	= Mage::helper('flexpayment/api')->getMagentoOrder($orderId);
				$order = Mage::getModel('sales/order')->load($orderId);
				$order->addStatusToHistory($order->getStatus(), $systemMessage, false);
				$order->save();
				Mage::log('Order ID: ' .$order->getId(). ' placed : ' . $friendlyMessage, null, 'flexpayment.log');
			} catch(Exception $e){
				Mage::log('Error adding comment to order ' . $orderId . ' (' .$systemMessage. ')', null, 'flexpayment.log');
			}

			return array('status' => $result['status'], 'message' => $friendlyMessage, 'system' => $systemMessage);
		}
	}

	public function setTrackingNumbers()
	{
		$time_filter = array(
                'from'  => date('Y-m-d H:i:s', Mage::getSingleton('core/date')->gmtDate(time()-15*60)),
                'to'    => date('Y-m-d H:i:s', Mage::getSingleton('core/date')->gmtDate(time())),
			);

		$orders = Mage::getModel('sales/order')->getCollection()
					->join(array('payment'=>'sales/order_payment'),'main_table.entity_id=parent_id','method')
					->addFieldToFilter('created_at', $time_filter)
					->addFieldToFilter('status', array('neq' => 'canceled'))
					->addFieldToFilter('method', array('eq' => 'flexpayment'))->getAllIds();

		$shipmentCollection = Mage::getResourceModel('sales/order_shipment_track_collection')
								->addFieldToSelect("*")
								->addFieldToFilter('order_id', array('in' => $orders));

		try{
			foreach ($shipmentCollection as $shipment) {
				$oneReq 	= Mage::helper('flexpayment/api');
				$endPoint 	= 'set-tracking-number';
				$body 		= array(
								'TransactionID' 	=> Mage::helper('flexpayment/api')->getFlexOrder($shipment->getOrderId()),
								'courier'			=> $shipment->getCarrierCode(),
								'tracking_number' 	=> $shipment->getTrackNumber()
							);
				$result 	= $oneReq->setData($body)->setEndpoint($endPoint)->getFlex();

				if($result['status'] == 'error'){
					Mage::log('Order ID: ' .$shipment->getOrderId(). ' Error sending tracking number: ' . $shipment->getTrackNumber() . ' - ' . $result['message'], null, 'flexpayment.log');		
				} else {
					Mage::log('Order ID: ' .$shipment->getOrderId(). ' Tracking number sent: ' . $shipment->getTrackNumber(), null, 'flexpayment.log');		
				}
			}
		} catch(Exception $e){
			Mage::log('Error sending tracking numbers ' . $e->getMessage(), null, 'flexpayment.log');
		}
	}

	public function cancelFlexshopperTransaction($orderId)
	{
		$orderId 	= Mage::helper('flexpayment/api')->getFlexOrder($orderId);
		$oneReq 	= Mage::helper('flexpayment/api');
		$endPoint 	= 'order-item-cancellation';
		$body 		= array(
						'TransactionID' 	=> $orderId
					);
		$result 	= $oneReq->setData($body)->setEndpoint($endPoint)->getFlex();

		if($result['status'] == 'error'){
			Mage::log('Order ID: ' .Mage::helper('flexpayment/api')->getMagentoOrder($orderId). ' error while being canceled. ' . $result['message'], null, 'flexpayment.log'); 
	        Mage::getSingleton('checkout/session')->setErrorMessage(Mage::helper('flexpayment')->__('Flexshopper was not able to cancel the order, please contact the payment provider.'));
		} else {
			Mage::log('Order ID: ' .Mage::helper('flexpayment/api')->getMagentoOrder($orderId). ' has been canceled. ' . $result['message'], null, 'flexpayment.log');
		}
	}

	public function refundFlexshopperAmount($orderId, $totalRefunded, $type)
	{
		if($type == 'ALL'){
			$type = 'STOCK';
		}

		$mageOrderId = $orderId;
		$orderId 	= Mage::helper('flexpayment/api')->getFlexOrder($orderId); 
        $oneReq     = Mage::helper('flexpayment/api');
        $endPoint   = 'refund'; // the return item needs more research to be used 'order-item-return';
        $body       = array(
                        'TransactionID'     => $orderId,
                        'Amount'          	=> $totalRefunded,
                        'Type'				=> $type
                    );

        $result     = $oneReq->setData($body)->setEndpoint($endPoint)->getFlex();

        Mage::log('Order ID:' . $mageOrderId . ' - Refund amount: ' . $totalRefunded . ', Message from API: ' . $result['message'], null, 'flexpayment.log');
        if($result['status'] == 'error'){
            Mage::getSingleton('checkout/session')->setErrorMessage(Mage::helper('flexpayment')->__('Flexshopper was not able to refund the amount, please contact the payment provider.'));
        } else {
        	Mage::getSingleton('checkout/session')->setSuccessMessage(Mage::helper('flexpayment')->__('Flexshopper refunded the amount of .'));
        }		
	}
}

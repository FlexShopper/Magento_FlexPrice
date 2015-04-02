<?php
class FlexShopper_Payment_Helper_Api extends Mage_Core_Helper_Abstract
{
	public $data 		 	= '';
	public $url 			= '';

	public function getFlex()
	{
		return $this->call();
	}

    public function getFlexOrder($orderId)
    {
        return '500000' . $orderId;
    }

    public function getMagentoOrder($flexOrderId)
    {
        $exploded = explode("500000", $flexOrderId);
        return trim($exploded[1]);
    }

	public function setData($data)
	{
		if(is_array($data)){
			$this->data = $data;
		}

		return $this;
	}

    public function getGatewayUrl()
    {
        $test_mode      = Mage::getStoreConfig('payment/flexpayment/sandbox_mode');

        if (!$test_mode) {
            $url_base = 'http://sdk.flexshopper.com';
        } else {
            $url_base = 'http://sdk.sandbox.flexshopper.com';
        }

        return $url_base;
    }

	public function setEndpoint($end_point = '')
	{
        $url_base = $this->getGatewayUrl();

		if(isset($end_point) && !empty($end_point)){
			$this->url = $url_base . '/' . $end_point;
		} else {
			$this->url = $url_base;
		}

		return $this;
	}

    protected function call()
    {
    	if(empty($this->url)){
    		return false;
    	}

        if ($curl = curl_init()) {
            $result = false;
            $retailer_token = Mage::getStoreConfig('payment/flexpayment/retailer_token');
            if(empty($retailer_token)){
                return false;
            }

            if(!empty($this->data)){
            	$data_string_encoded = Mage::helper('core')->jsonEncode($this->data);
            }

            $header = array('Content-Type: text/json', 'RetailerToken: ' . $retailer_token, 'Accept: application/json');

            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

            if(!empty($data_string_encoded))
            	curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string_encoded);
			
			curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

            try {
                $response = curl_exec($curl);
                $result   = $this->parseResult($response, $curl);
            } catch (Exception $e) {
                Mage::logException($e);
                die();
            }

            return $result;
        }

        return false;      
    }	

    protected function parseResult($response, &$curl)
    {
        try {
            $info = curl_getinfo($curl);
            // $code = $info['http_code'];
            // switch ($code) {
            //     case 401:
            //         throw new Exception('The API Key was missing or invalid');
            //         break;
            //     case 500:
            //         throw new Exception('An unhandled exception occured on the server');
            //         break;
            // }

            $data = Mage::helper('core')->jsonDecode($response);
            return $data;
        } catch (Exception $e) {
            $this->_errorCode    = $e->getCode();
            $this->_errorMessage = $e->getMessage();
        }
        curl_close($curl);
        return false;
    }

    /**
     * Translates the response code into a more meaningful description.
     * Response code descriptions are taken directly from the Payfort documentation.
     */
    public function getResponseCodeDescription($responseCode) {
        switch ($responseCode) {
            case "ok" : $result             = "Transaction was completed successfully.";
                break;
            case "declined" : $result       = "Customer application has been denied.";
                break;
            case "canceled" : $result       = "Customer decided to cancel the transaction.";
                break;
            case "approved" : $result       = "Customer application completed, but transaction is canceled.";
                break;
            case "over_limit" : $result     = "Customer spending limit is not high enough to purchase the items.";
                break;
            case "error" : $result          = "Error in request";
                break;
            default : $result = "Response Unknown";
        }

        return $result;
    }
}
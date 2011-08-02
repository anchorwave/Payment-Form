<?php

/*
Name: Virtual Merchant
*/

class VirtualMerchant extends PaymentProcessor
{
	private $url = "https://www.myvirtualmerchant.com/VirtualMerchant/process.do";

	private $defaults = array(
			'ssl_show_form'=>"false",
			'ssl_transaction_type'=>"ccsale",
			'ssl_cvv2cvc2_indicator'=>"1",
			'ssl_result_format'=>"ASCII",
		);
	
	protected $required = array(
			'ssl_transaction_type',
			'ssl_merchant_id',
			'ssl_pin',
			'ssl_user_id',
			'ssl_amount',
			'ssl_card_number',
			'ssl_exp_date'
		);
		
	private $resultcodes = array(
			'0'=>"approved"
		);

	public function __construct()
	{
		// Set functional defaults
		if(!((func_num_args() == 1 && is_array(func_get_arg(0))) || (func_num_args() == 3)) && $this->DEBUG)
		{
			$this->errorHandler("Merchant ID, User ID and PIN should be passed",__FUNCTION__);
			return;
		}
		if(is_array(func_get_arg(0)))
		{
			$argarr = func_get_arg(0);
			$this->request['ssl_merchant_id'] = trim($argarr[0]);
			$this->request['ssl_user_id'] = trim($argarr[1]);
			$this->request['ssl_pin'] = trim($argarr[2]);
		}
		else
		{
			$this->request['ssl_merchant_id'] = func_get_arg(0);
			$this->request['ssl_user_id'] = func_get_arg(1);
			$this->request['ssl_pin'] = func_get_arg(2);
		}

		// Insert preset defaults
		foreach($this->defaults as $defaults_key=>$defaults_value)
			$this->request[$defaults_key]=$defaults_value;
		
		return;
	}
	
	public function setTestRequest()
	{
		$this->request['ssl_test_mode'] = "true";
	}
	
	public function sendRequest()
	{
		$this->checkRequired();
		
		if(count($this->errorinfo)>0)
		{
			if($this->debug)
			{
				$this->errorHandler("Can not proceed with errors present",__FUNCTION__);
				error_log(implode("\n",$this->errorinfo));
				throw new Exception("Can not proceed with errors present in fuction '".__FUNCTION__."()'");
			}
			else
			{
				
			}
			return;
		}
		
		$request_pairs = array();
		foreach($this->request as $request_key=>$request_value)
		{
			$request_pairs[] = $request_key.'='.urlencode($request_value);
		}
		$this->rawrequest = implode('&',$request_pairs);
		
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->rawrequest);
		$this->rawresponse = curl_exec($ch);
		$info=curl_getinfo($ch);
		
		if ($this->rawresponse === false || $info['http_code'] != 200)
		{
			$this->errorHandler("No cURL data returned for ".$this->url." [http_code = ". $info['http_code']. "]",__FUNCTION__);
			if (curl_error($ch))
				$this->errorHandler(curl_error($ch),__FUNCTION__);
		}
		else
		{
			$splitrawresponse = explode("\n",$this->rawresponse);
			if(is_array($splitrawresponse))
			{
				foreach($splitrawresponse as $splitrawresponseitem)
				{
					list($splitrawresponseitemkey,$splitrawresponseitemvalue) = explode("=",$splitrawresponseitem);
					$this->response[strtolower($splitrawresponseitemkey)] = $splitrawresponseitemvalue;
				}
				if(array_key_exists('errorcode',$this->response))
				{
					$this->errorHandler($this->response['errormessage'],__FUNCTION__);
				}
				elseif(array_key_exists('ssl_result',$this->response))
				{
					switch($this->response['ssl_result'])
					{
						case 0:
							$this->success = true;
							break;
						default:
							$this->declined = true;
							break;
					}
				}
				else
				{
					$this->errorHandler("Unknown keys in response from processor",__FUNCTION__);
				}
			}
			else
			{
				$this->errorHandler("Unable to parse response from processor",__FUNCTION__);
			}
		}
		if($this->debug)
		{
			$this->debuginfo['Curl Post'] = $this->rawrequest."\n".print_r($this->request,true);
			$this->debuginfo['Curl Response'] = $this->rawresponse."\n".print_r($this->response,true);
			$this->debuginfo['Curl Info'] = print_r($info,true);
		}
		curl_close($ch);

		return count($this->errorinfo)==0 ? true : false;
	}
	
	public function getResult()
	{
		if(is_array($this->response) && array_key_exists('ssl_result',$this->response))
			return array_key_exists($this->response['ssl_result'],$resultcodes) ? $resultcodes[$this->response['ssl_result']] : 'declined';
		else
			return null;
	}
	
	public function getReason()
	{
		if(is_array($this->response))
		{
			if(array_key_exists('ssl_result_message',$this->response))
				return $this->response['ssl_result_message'];
			else if(array_key_exists('errormessage',$this->response))
				return $this->response['errormessage'];
			else
				return null;
		}
		else
			return null;
	}
	
	public function getTransactionID()
	{
		return is_array($this->response) && array_key_exists('ssl_txn_id',$this->response) ? $this->response['ssl_txn_id'] : null;
	}
	
	public function getApprovalCode()
	{
		return is_array($this->response) && array_key_exists('ssl_approval_code',$this->response) ? $this->response['ssl_approval_code'] : null;
	}
	
	public function setCardType()
	{
	}
	
	public function setCardNumber()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		if(!is_numeric($value) || strlen($value) < 13 || strlen($value) > 16)
			$this->errorHandler("Invalid data type for argument 1",__FUNCTION__);
		else
			$this->request['ssl_card_number'] = $value;
			
		return;
	}
	
	public function setExpiration()
	{
		if(func_num_args() != 2)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$month=func_get_arg(0);
		if(!is_numeric($month))
		{
			$format = strlen($month) == 3 ? '%b' : '%B';
			$time = strptime($month,$format);
			$month = sprintf('%02d',$time['tm_mon']+1);
		}
		$year=func_get_arg(1);
		$this->request['ssl_exp_date'] = $month.substr($year,-2);
		
		return;
	}
	
	public function setCardCode()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		if(!is_numeric($value) || strlen($value) < 3 || strlen($value) > 4)
			$this->errorHandler("Invalid data type for argument 1",__FUNCTION__);
		else
			$this->request['ssl_cvv2cvc2'] = $value;
			
		return;
	}
	
	public function setAmount()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		if(!is_numeric($value) || strlen($value)>13)
			$this->errorHandler("Invalid data type for argument 1",__FUNCTION__);
		else
			$this->request['ssl_amount'] = $value;
		
		return;
	}
	
	public function setInvoiceNumber()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_invoice_number'] = substr($value,0,25);
		
		return;
	}
	
	public function setDescription()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_description'] = substr($value,0,255);
		
		return;
	}
	
	public function setLineItem()
	{
	}
	
	public function setTax()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		if(!is_numeric($value) || strlen($value)>10)
			$this->errorHandler("Invalid data type for argument 1",__FUNCTION__);
		else
			$this->request['ssl_salestax'] = $value;
		
		return;
	}
	
	public function setFreight()
	{
	}
	
	public function setCustomerID()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_customer_code'] = substr($value,0,17);
		
		return;
	}
	
	public function setCustomerName()
	{
		if(func_num_args() != 2)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$firstname = func_get_arg(0);
		$lastname = func_get_arg(1);
		$this->request['ssl_first_name'] = substr($firstname,0,20);
		$this->request['ssl_last_name'] = substr($lastname,0,30);
		
		return;
	}
	
	public function setCustomerCompany()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_company'] = substr($value,0,50);
		
		return;
	}
	
	public function setCustomerAddress()
	{
		if(func_num_args() < 1 || func_num_args() > 2)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$address1 = func_get_arg(0);
		$address2 = func_get_arg(1);
		$this->request['ssl_avs_address'] = substr($address1,0,20);
		if(trim($address2) != '')
			$this->request['ssl_address2'] = substr($address2,0,30);
		
		return;
	}
	
	public function setCustomerCity()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_city'] = substr($value,0,30);
		
		return;
	}
	
	public function setCustomerState()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$state = $this->convertState($value);
		if(!$state)
			$this->errorHandler("Invalid value for state",__FUNCTION__);
		else
			$this->request['ssl_state'] = $state;
		
		return;
	}
	
	public function setCustomerZip()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		if(!is_numeric($value) && !(strlen($value)==5 || strlen($value)==9))
			$this->errorHandler("Invalid value for zip",__FUNCTION__);
		else
			$this->request['ssl_avs_zip'] = substr($value,0,9);
		
		return;
	}
	
	public function setCustomerCountry()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_country'] = substr($value,0,50);
		
		return;
	}
	
	public function setCustomerEmail()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_email'] = substr($value,0,100);
		
		return;
	}
	
	public function setCustomerPhone()
	{
		if(func_num_args() < 1 || func_num_args() > 3)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		if(func_num_args() == 1)
		{
			$value = func_get_arg(0);
			if(is_array($value))
				$value=implode('',$value);
		}
		else if(func_num_args() == 3)
		{
			$value = func_get_arg(0).func_get_arg(1).func_get_arg(2);
		}
		
		if(!is_numeric($value))
			$this->errorHandler("Invalid value for phone",__FUNCTION__);
		else
			$this->request['ssl_phone'] = substr($value,0,20);

		return;
	}
	
	public function setCustomerIP()
	{
	}
	
	public function setShippingName()
	{
		if(func_num_args() != 2)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$firstname = func_get_arg(0);
		$lastname = func_get_arg(1);
		$this->request['ssl_ship_to_first_name'] = substr($firstname,0,20);
		$this->request['ssl_ship_to_last_name'] = substr($lastname,0,30);
		
		return;
	}
	
	public function setShippingCompany()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_ship_to_company'] = substr($value,0,50);
		
		return;
	}
	
	public function setShippingAddress()
	{
		if(func_num_args() < 1 || func_num_args() > 2)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$address1 = func_get_arg(0);
		$address2 = func_get_arg(1);
		$this->request['ssl_ship_to_address1'] = substr($address1,0,30);
		if(trim($address2) != '')
			$this->request['ssl_ship_to_address2'] = substr($address2,0,30);
		
		return;
	}
	
	public function setShippingCity()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_ship_to_city'] = substr($value,0,30);
		
		return;
	}
	
	public function setShippingState()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$state = $this->convertState($value);
		if(!$state)
			$this->errorHandler("Invalid value for state",__FUNCTION__);
		else
			$this->request['ssl_ship_to_state'] = $state;
		
		return;
	}
	
	public function setShippingZip()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		if(!is_numeric($value) && !(strlen($value)==5 || strlen($value)==9))
			$this->errorHandler("Invalid value for zip",__FUNCTION__);
		else
			$this->request['ssl_ship_to_zip'] = substr($value,0,9);
		
		return;
	}
	
	public function setShippingCountry()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['ssl_ship_to_country'] = substr($value,0,50);
		
		return;
	}
	
	public function setShippingPhone()
	{
		if(func_num_args() < 1 || func_num_args() > 3)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		if(func_num_args() == 1)
		{
			$value = func_get_arg(0);
			if(is_array($value))
				$value=implode('',$value);
		}
		else if(func_num_args() == 3)
		{
			$value = func_get_arg(0).func_get_arg(1).func_get_arg(2);
		}
		
		if(!is_numeric($value))
			$this->errorHandler("Invalid value for phone",__FUNCTION__);
		else
			$this->request['ssl_ship_to_phone'] = substr($value,0,20);

		return;
	}
	

}

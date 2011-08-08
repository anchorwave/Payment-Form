<?php
/*
Name: Authorize.Net
*/

class AuthorizeNetPaymentProcessor extends PaymentProcessor
{
	private $url = "https://secure.authorize.net/gateway/transact.dll";
	private $dev_url = "https://test.authorize.net/gateway/transact.dll";

	private $defaults = array(
			'x_delim_data'=>"TRUE",
			'x_delim_char'=>"|",
			'x_version'=>"3.1",
			'x_url'=>"FALSE",
			'x_relay_response'=>"FALSE",
			'x_duplicate_window' => "120",
			'x_type' => "AUTH_CAPTURE",
			'x_method' => "CC",
			'x_email_customer' => "FALSE",
			'x_recurring_billing' => "FALSE",
		);
	
	protected $required = array(
			'x_type',
			'x_tran_key',
			'x_login',
			'x_amount',
			'x_card_num',
			'x_exp_date'
		);
		
	private $resultcodes = array(
			'1'=>"Approved",
			'2'=>"Declined",
			'3'=>"Error",
			'4'=>"Held for Review"
		);

	public function __construct()
	{
		if ( PaymentFormOptions::attr( 'developer_mode' ) == 1 ) {
			$this->url = $this->dev_url;
		}
	
		// Set functional defaults
		if(!((func_num_args() == 1 && is_array(func_get_arg(0))) || (func_num_args() == 2)))
		{
			$this->errorHandler("Login ID and Transaction Key should be passed.",__FUNCTION__);
			return;
		}
		if(is_array(func_get_arg(0)))
		{
			$argarr = func_get_arg(0);
			$this->request['x_login'] = $argarr[0];
			$this->request['x_tran_key'] = $argarr[1];
		}
		else
		{
			$this->request['x_login'] = func_get_arg(0);
			$this->request['x_tran_key'] = func_get_arg(1);
		}

		// Insert preset defaults
		foreach($this->defaults as $defaults_key=>$defaults_value)
			$this->request[$defaults_key]=$defaults_value;
		
		return;
	}
	
	public function setTestRequest()
	{
		$this->request['x_test_request'] = "TRUE";
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
			if(is_array($value))
				foreach($request_value as $request_subvalue)
					$request_pairs[] = $request_key.'='.urlencode($request_subvalue);
			else
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
			$this->response = explode($this->request['x_delim_char'],$this->rawresponse);
			if(is_array($this->response))
			{
				if(is_numeric($this->response[0]))
				{				
					switch($this->response[0])
					{
						case 1:
							$this->success = true;
							break;
						case 4:
							$this->review = true;
							break;
						case 3:
							$this->errorHandler($this->response[3],__FUNCTION__);
							break;
						case 2:
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
		if(is_array($this->response))
			return array_key_exists($this->response[0],$resultcodes) ? $resultcodes[$this->response[0]] : 'declined';
		else
			return null;
	}
	
	public function getReason()
	{
		if(is_array($this->response))
			return $this->response[3];
		else
			return null;
	}
	
	public function getTransactionID()
	{
		return is_array($this->response) ? $this->response[6] : null;
	}
	
	public function getApprovalCode()
	{
		return is_array($this->response) ? $this->response[4] : null;
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
			$this->request['x_card_num'] = $value;
			
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
		$this->request['x_exp_date'] = $month.$year;
		
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
			$this->request['x_card_code'] = $value;
			
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
			$this->request['x_amount'] = sprintf("%.2f",(float)$value);
		
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
		$this->request['x_invoice_num'] = substr($value,0,20);
		
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
		$this->request['x_description'] = substr($value,0,255);
		
		return;
	}
	
	public function setLineItem()
	{
		if(func_num_args() < 3)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		$itemName = func_get_arg(0);
		$itemQuantity = func_get_arg(1);
		$itemPrice = func_get_arg(2);
		$itemDescription = @func_get_arg(3);
		$itemTaxable = @func_get_arg(4) ? "TRUE" : "FALSE";
		if(!is_numeric($itemQuantity))
		{
			$this->errorHandler("Invalid data type for argument 2",__FUNCTION__);
			return;
		}
		if(!is_numeric($itemPrice))
		{
			$this->errorHandler("Invalid data type for argument 3",__FUNCTION__);
			return;
		}
		if(count($this->request['x_line_item']) < 30)
		{
			$value = (count($this->request['x_line_item'])+1) . '<|>' . substr($itemName,0,31) . '<|>' . substr($itemDescription,0,255) . '<|>' . sprintf("%.2f",(float)$itemQuantity) . '<|>' . sprintf("%.2f",(float)$itemPrice) . '<|>' . $itemTaxable;
			$this->request['x_line_item'][] = $value;
		}
		return;
	}
	
	public function setTax()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['x_tax'] = sprintf("%.2f",(float)$value);
		
		return;
	}
	
	public function setFreight()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['x_freight'] = sprintf("%.2f",(float)$value);
		
		return;
	}
	
	public function setCustomerID()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['x_cust_id'] = substr($value,0,20);
		
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
		$this->request['x_first_name'] = substr($firstname,0,50);
		$this->request['x_last_name'] = substr($lastname,0,50);
		
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
		$this->request['x_company'] = substr($value,0,50);
		
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
		if(trim($address2) != '')
			$address1.=', '.$address2;
		$this->request['x_address'] = substr($address1,0,60);
		
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
		$this->request['x_city'] = substr($value,0,40);
		
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
			$this->request['x_state'] = $state;
		
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
			$this->request['x_zip'] = substr($value,0,20);
		
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
		$this->request['x_country'] = substr($value,0,2);
		
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
		$this->request['x_email'] = substr($value,0,255);
		
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
		
		for($i=0;$i<strlen($value);$i++)
			if(is_numeric($value[$i])) $phoneval.= $value[$i];
		
		if(!is_numeric($phoneval))
			$this->errorHandler("Invalid value for phone",__FUNCTION__);
		else
			$this->request['x_phone'] = substr($phoneval,0,25);

		return;
	}
	
	public function setCustomerIP()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		$value = func_get_arg(0);
		if($this->validateIP($value))
			$this->request['x_customer_ip'] = substr($value,0,15);
		else
			$this->errorHandler("Invalid value for IP",__FUNCTION__);
		return;
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
		$this->request['x_ship_to_first_name'] = substr($firstname,0,50);
		$this->request['x_ship_to_last_name'] = substr($lastname,0,50);
		
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
		$this->request['x_ship_to_company'] = substr($value,0,50);
		
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
		if(trim($address2) != '')
			$address1.=', '.$address2;
		$this->request['x_ship_to_address'] = substr($address1,0,60);
		
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
		$this->request['x_ship_to_city'] = substr($value,0,40);
		
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
			$this->request['x_ship_to_state'] = $state;
		
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
			$this->request['x_ship_to_zip'] = substr($value,0,20);
		
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
		$this->request['x_ship_to_country'] = substr($value,0,2);
		
		return;
	}
	
	public function setShippingPhone()
	{
	}


}

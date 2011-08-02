<?php
/*
Name: PayPal Pro
*/

class PaypalProPaymentProcessor extends PaymentProcessor
{
	private $url = "https://api-3t.paypal.com/nvp";
	private $testurl = "https://api-3t.sandbox.paypal.com/nvp";

	private $defaults = array(
			'METHOD'=>"DoDirectPayment",
			'PAYMENTACTION'=>"Sale",
			'VERSION'=>"61.0",
			'COUNTRYCODE'=>"US"
		);
	
	protected $required = array(
			'METHOD',
			'IPADDRESS',
			'CREDITCARDTYPE',
			'ACCT',
			'EXPDATE',
			'CVV2',
			'STREET',
			'CITY',
			'STATE',
			'COUNTRYCODE',
			'ZIP',
			'AMT'
		);

	public function __construct()
	{
		// Set functional defaults
		if(!((func_num_args() == 1 && is_array(func_get_arg(0))) || (func_num_args() == 3)) && $this->DEBUG)
		{
			$this->errorHandler("Username, Password and Signature should be passed",__FUNCTION__);
			return;
		}
		if(is_array(func_get_arg(0)))
		{
			$argarr = func_get_arg(0);
			$this->request['USER'] = trim($argarr[0]);
			$this->request['PWD'] = trim($argarr[1]);
			$this->request['SIGNATURE'] = trim($argarr[2]);
		}
		else
		{
			$this->request['USER'] = func_get_arg(0);
			$this->request['PWD'] = func_get_arg(1);
			$this->request['SIGNATURE'] = func_get_arg(2);
		}

		// Insert preset defaults
		foreach($this->defaults as $defaults_key=>$defaults_value)
			$this->request[$defaults_key]=$defaults_value;
		
		return;
	}
	
	public function setTestRequest()
	{
		$this->url = $this->testurl;
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
			$request_pairs[] = urlencode($request_key).'='.urlencode($request_value);
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
			$splitrawresponse = explode("&",$this->rawresponse);
			if(is_array($splitrawresponse))
			{
				foreach($splitrawresponse as $splitrawresponseitem)
				{
					list($splitrawresponseitemkey,$splitrawresponseitemvalue) = explode("=",$splitrawresponseitem);
					$this->response[strtoupper(urldecode($splitrawresponseitemkey))] = urldecode($splitrawresponseitemvalue);
				}
				if(array_key_exists('ACK',$this->response) && strpos(strtolower($this->response['ACK']),'success') === false)
				{
					$this->errorHandler($this->response['L_SHORTMESSAGE0'].': '.$this->response['L_LONGMESSAGE0'],__FUNCTION__);
					if(array_key_exists('AMT',$this->response))
					{
						$this->declined = true;
					}
				}
				elseif(array_key_exists('TRANSACTIONID',$this->response))
				{
					$this->success = true;
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
		if(is_array($this->response) && array_key_exists('AMT',$this->response))
			return array_key_exists('TRANSACTIONID',$this->response) ? 'approved' : 'declined';
		else
			return null;
	}
	
	public function getReason()
	{
		if(is_array($this->response))
		{
			if(array_key_exists('L_LONGMESSAGE0',$this->response))
				return $this->response['L_LONGMESSAGE0'];
			else
				return null;
		}
		else
			return null;
	}
	
	public function getTransactionID()
	{
		return is_array($this->response) && array_key_exists('TRANSACTIONID',$this->response) ? $this->response['TRANSACTIONID'] : null;
	}
	
	public function getApprovalCode()
	{
		return is_array($this->response) && array_key_exists('TRANSACTIONID',$this->response) ? $this->response['TRANSACTIONID'] : null;
	}
	
	public function setCardType()
	{
		if(func_num_args() != 1)
		{
			$this->errorHandler("Invalid number of arguments",__FUNCTION__);
			return;
		}
		
		$value = func_get_arg(0);
		$this->request['CREDITCARDTYPE'] = $value;
			
		return;
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
			$this->request['ACCT'] = $value;
			
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
		$this->request['EXPDATE'] = $month.$year;
		
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
			$this->request['CVV2'] = $value;
			
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
			$this->request['AMT'] = sprintf("%.2f",(float)$value);
		
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
		$this->request['INVNUM'] = substr($value,0,127);
		
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
		$this->request['DESC'] = substr($value,0,127);
		
		return;
	}
	
	public function setLineItem()
	{
	}
	
	public function setTax()
	{
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
		$this->request['PAYERID'] = substr($value,0,13);
		
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
		$this->request['FIRSTNAME'] = substr($firstname,0,25);
		$this->request['LASTNAME'] = substr($lastname,0,25);
		
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
		$this->request['BUSINESS'] = substr($value,0,127);
		
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
		$this->request['STREET'] = substr($address1,0,100);
		if(trim($address2) != '')
			$this->request['STREET2'] = substr($address2,0,100);
		
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
		$this->request['CITY'] = substr($value,0,40);
		
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
			$this->request['STATE'] = $state;
		
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
			$this->request['ZIP'] = substr($value,0,9);
		
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
		$this->request['COUNTRYCODE'] = substr($value,0,2);
		
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
		$this->request['EMAIL'] = substr($value,0,127);
		
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
			$this->request['PHONENUM'] = substr($value,0,20);

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
			$this->request['IPADDRESS'] = substr($value,0,15);
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
		$this->request['SHIPTONAME'] = substr($lastname.', '.$firstname,0,30);
		
		return;
	}
	
	public function setShippingCompany()
	{
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
		$this->request['SHIPTOSTREET'] = substr($address1,0,100);
		if(trim($address2) != '')
			$this->request['SHIPTOSTREET2'] = substr($address2,0,100);
		
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
		$this->request['SHIPTOCITY'] = substr($value,0,40);
		
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
			$this->request['SHIPTOSTATE'] = $state;
		
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
			$this->request['SHIPTOZIP'] = substr($value,0,9);
		
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
		$this->request['SHIPTOCOUNTRY'] = substr($value,0,2);
		
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
			$this->request['SHIPTOPHONENUM'] = substr($value,0,20);

		return;
	}
	

}

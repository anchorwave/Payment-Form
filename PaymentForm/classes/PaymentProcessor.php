<?php

abstract class PaymentProcessor
{
	
/**
	* url
	* the url to send live transactions
	*/
	private $url;

/**
	* debug
	* bool flag to turn on additional logging
	*/
	protected $debug = false;

/**
	* debuginfo
	* array to hold debugging messages
	*/
	protected $debuginfo = array();

/**
	* error array
	* array to hold error messages
	*/
	protected $errorinfo=array();

/**
	* testmode
	* bool flag to turn on testmode
	*/
	protected $testmode = false;

/**
	* success
	* bool flag to set upon successful transaction
	*/
	protected $success = false;

/**
	* declined
	* bool flag to set upon declined transaction
	*/
	protected $declined = false;

/**
	* review
	* bool flag to set upon "held for review" transaction
	*/
	protected $review = false;

/**
	* response
	* array to hold key/value pair results
	*/
	protected $response=array();

/**
	* rawrequest
	* variable to hold the complete request message
	*/
	protected $rawrequest;

/**
	* rawresponse
	* variable to hold the complete response message
	*/
	protected $rawresponse;

/**
	* request
	* array to hold key/value pairs to be sent in the request
	*/
	protected $request = array();

/**
	* states
	* array holding state abbreviation to text values
	*/
	protected $states = array(
					'AL'=>'ALABAMA',
					'AK'=>'ALASKA',
					'AZ'=>'ARIZONA',
					'AR'=>'ARKANSAS',
					'CA'=>'CALIFORNIA',
					'CO'=>'COLORADO',
					'CT'=>'CONNECTICUT',
					'DE'=>'DELAWARE',
					'DC'=>'DISTRICT OF COLUMBIA',
					'FL'=>'FLORIDA',
					'GA'=>'GEORGIA',
					'HA'=>'HAWAII',
					'ID'=>'IDAHO',
					'IL'=>'ILLINOIS',
					'IN'=>'INDIANA',
					'IA'=>'IOWA',
					'KS'=>'KANSAS',
					'KY'=>'KENTUCKY',
					'LA'=>'LOUISIANA',
					'ME'=>'MAINE',
					'MD'=>'MARYLAND',
					'MA'=>'MASSACHUSETTS',
					'MI'=>'MICHIGAN',
					'MN'=>'MINNESOTA',
					'MS'=>'MISSISSIPPI',
					'MO'=>'MISSOURI',
					'MT'=>'MONTANA',
					'NE'=>'NEBRASKA',
					'NV'=>'NEVADA',
					'NH'=>'NEW HAMPSHIRE',
					'NJ'=>'NEW JERSEY',
					'NM'=>'NEW MEXICO',
					'NY'=>'NEW YORK',
					'NC'=>'NORTH CAROLINA',
					'ND'=>'NORTH DAKOTA',
					'OH'=>'OHIO',
					'OK'=>'OKLAHOMA',
					'OR'=>'OREGON',
					'PA'=>'PENNSYLVANIA',
					'RI'=>'RHODE ISLAND',
					'SC'=>'SOUTH CAROLINA',
					'SD'=>'SOUTH DAKOTA',
					'TN'=>'TENNESSEE',
					'TX'=>'TEXAS',
					'UT'=>'UTAH',
					'VT'=>'VERMONT',
					'VA'=>'VIRGINIA',
					'WA'=>'WASHINGTON',
					'WV'=>'WEST VIRGINIA',
					'WI'=>'WISCONSIN',
					'WY'=>'WYOMING'
					);


	/**
		* __construct()
		*	Initializes class. Should be used to set authentication values
		* and define default values specific to the processor.
		@var Optional Authentication Values
		*/
		abstract public function __construct();


	/**
		* __destruct()
		* Cleanup. Should be used to close persistant connections, file
		* descriptors, and remove variable data containing sensitive 
		* cardholder information.
		*/
		public function __destruct()
		{
			if($this->debug)
				error_log(implode("\n",$this->debuginfo));
			$this->cleanSensitiveData();
		}


/*************************************************************************************************************
 * Begin Processing & Response Functions                                                                     *
 *************************************************************************************************************/
		
	
	/**
		* sendRequest()
		* Send request to payment processor
		*/
		abstract public function sendRequest();	


	/**
		* getResult()
		* Get result of the last transaction
		*/
		abstract public function getResult();


	/**
		* getReason()
		* Get human-readable result of the last transaction
		*/
		abstract public function getReason();


	/**
		* getTransactionID()
		* Get the Transaction Key of the last transaction
		*/
		abstract public function getTransactionID();


	/**
		* getApprovalCode()
		* Get the Approval Code of the last transaction
		*/
		abstract public function getApprovalCode();
		
		
/*************************************************************************************************************
 * End Processing & Response Functions                                                                       *
 *************************************************************************************************************/




/*************************************************************************************************************
 * Begin Credit Card Information Fields                                                                      *
 *************************************************************************************************************/


	/**
		* setCardType()
		* Set the Credit Card Type to be charged
		*/
		abstract public function setCardType();


	/**
		* setCardNumber()
		* Set the Credit Card Number to be charged
		* Between 13 and 16 digits
		*/
		abstract public function setCardNumber();


	/**
		* setExpiration()
		* Set the Credit Card Expiration Date
		*/
		abstract public function setExpiration();


	/**
		* setCardCode()
		* Set the Credit Card CVV2 Code
		* The three or four digit number on the back of a credit card (on the front
		* for American Express).
		*/
		abstract public function setCardCode();


/*************************************************************************************************************
 * End Credit Card Information Fields                                                                        *
 *************************************************************************************************************/




/*************************************************************************************************************
 * Begin Order Information Fields                                                                            *
 *************************************************************************************************************/


	/**
		* setAmount()
		* Set the Total Amount to be charged
		* This should include tax, shipping, and any other charges
		*/
		abstract public function setAmount();


	/**
		* setInvoiceNumber()
		* The Invoice Number of the transaction
		*/
		abstract public function setInvoiceNumber();


	/**
		* setDescription()
		* The Description of the transaction
		*/
		abstract public function setDescription();


	/**
		* setLineItem()
		* Item Description, Quantity and Price Information
		* This function should be able to be called multiple times
		*/
		abstract public function setLineItem();


	/**
		* setTax()
		* The Tax Amount charged
		*/		
		abstract public function setTax();


	/**
		* setFreight()
		* The Freight Amount charged
		*/
		abstract public function setFreight();
		
/*************************************************************************************************************
 * End Order Information Fields                                                                              *
 *************************************************************************************************************/




/*************************************************************************************************************
 * Begin Customer Information Fields                                                                         *
 *************************************************************************************************************/


	/**
		* setCustomerID()
		* The unique identifier to represent the customer associated with the
		* transaction.
		*/		
		abstract public function setCustomerID();


	/**
		* setCustomerName()
		* The customer's Name
		*/
		abstract public function setCustomerName();


	/**
		* setCustomerCompany()
		* The customer's Company
		*/		
		abstract public function setCustomerCompany();


	/**
		* setCustomerAddress()
		* The customer's Address
		*/		
		abstract public function setCustomerAddress();


	/**
		* setCustomerCity()
		* The customer's City
		*/		
		abstract public function setCustomerCity();


	/**
		* setCustomerState()
		* The customer's State
		*/		
		abstract public function setCustomerState();


	/**
		* setCustomerZip()
		* The customer's Zip
		*/		
		abstract public function setCustomerZip();


	/**
		* setCustomerCountry()
		* The customer's Country
		*/		
		abstract public function setCustomerCountry();


	/**
		* setCustomerEmail()
		* The customer's Email Address
		*/		
		abstract public function setCustomerEmail();


	/**
		* setCustomerPhone()
		* The customer's Phone Number
		*/		
		abstract public function setCustomerPhone();


	/**
		* setCustomerIP()
		* The IP Address of the customer initiating the transaction.
		*/		
		abstract public function setCustomerIP();


/*************************************************************************************************************
 * End Customer Information Fields                                                                           *
 *************************************************************************************************************/




/*************************************************************************************************************
 * Begin Shipping Information Fields                                                                         *
 *************************************************************************************************************/


	/**
		* setShippingName()
		* The customer's shipping Name
		*/
		abstract public function setShippingName();


	/**
		* setShippingCompany()
		* The customer's shipping Company
		*/		
		abstract public function setShippingCompany();


	/**
		* setShippingAddress()
		* The customer's shipping Address
		*/
		abstract public function setShippingAddress();


	/**
		* setShippingCity()
		* The customer's shipping City
		*/		
		abstract public function setShippingCity();


	/**
		* setShippingState()
		* The customer's shipping State
		*/	
		abstract public function setShippingState();


	/**
		* setShippingZip()
		* The customer's shipping ZIP
		*/		
		abstract public function setShippingZip();


	/**
		* setShippingCountry()
		* The customer's shipping Country
		*/		
		abstract public function setShippingCountry();


	/**
		* setShippingPhone()
		* The customer's shipping Phone Number
		*/		
		abstract public function setShippingPhone();
		
		
/*************************************************************************************************************
 * End Shipping Information Fields                                                                           *
 *************************************************************************************************************/




/*************************************************************************************************************
 * Begin Helper Functions                                                                                    *
 *************************************************************************************************************/


	/**
		* setEnableDebug()
		* Enables debugging
		*/
		public function setEnableDebug()
		{
			$this->debug = true;
		}


	/**
		* isSuccess()
		* returns true if successful transaction occurred
		*/
		public function getIsSuccess()
		{
			return $this->success;
		}


	/**
		* isDecline()
		* returns true if transaction resulted in a decline
		*/
		public function getIsDecline()
		{
			return $this->declined;
		}


	/**
		* isReview()
		* returns true if transaction resulted in a review
		*/
		public function getIsReview()
		{
			return $this->review;
		}


	/**
		* getDebugInfo()
		* returns debuginfo array
		*/
		public function getDebugInfo()
		{
			return $this->debuginfo;
		}


	/**
		* getErrorInfo()
		* returns errorinfo array
		*/
		public function getErrorInfo()
		{
			return $this->errorinfo;
		}


	/**
		* getResponse()
		* returns parsed response array from processor
		*/
		public function getResponse()
		{
			return $this->response;
		}


	/**
		* getRawResponse()
		* returns raw response from processor
		*/
		public function getRawResponse()
		{
			return $this->rawresponse;
		}


	/**
		* getRequest()
		* returns request values array
		*/
		public function getRequest()
		{
			return $this->request;
		}


	/**
		* getRawRequest()
		* returns raw request to the processor
		*/
		public function getRawRequest()
		{
			return $this->rawrequest;
		}


	/**
		* cleanSensitiveData()
		* Empties the values within variables containing sensitive
		* cardholder information. Should be called by destuctor class
		*/
		protected function cleanSensitiveData()
		{
			unset($this->debuginfo);
			unset($this->errorinfo);
			unset($this->results);
			unset($this->request);
			unset($this->response);
			unset($this->values);
		}
	
	
	/**
		* checkRequired()
		* Checks for $required array and verifies fields named in 
		* array are set.
		*/
		protected function checkRequired()
		{
			if($this->required && is_array($this->required) && count($this->required)>0)
			{
				foreach($this->required as $required_key)
				{
					if(!(array_key_exists($required_key,$this->request) && trim($this->request[$required_key]) != ''))
					{
						$this->errorHandler("Required value ".$required_key." not set");
					}
				}
			}
			return;
		}


	/**
		* normalizeState()
		* Returns 2 letter state abbreviation if available
		*
		* @var string US State
		*/
		protected function convertState($state)
		{
			if(strlen($state) > 2)
			{
				$statearraykey = array_search(strtoupper($state),$this->states);
				return (($statearraykey && strlen($statearraykey) == 2) ? $statearraykey : null);
			}
			elseif(strlen($state) == 2)
			{
				return (array_key_exists(strtoupper($state),$this->states) ? strtoupper($state) : null);
			}
			else
			{
				return null;
			}
		}


	/**
		* validateIP()
		*
		* @var string IP
		*/
		protected function validateIP($ip)
		{
			if(($binIp = ip2long($ip)) === false)
				return false;
			if(long2ip($binIp) == $ip)
				return $ip;
			return false;
		}


	/**
		* errorHandler()
		*
		* @var string Error
		*	@var string Function
		*/
		protected function errorHandler($error,$function='')
		{
			if($function != '')
				$error .=  " in fuction '".$function."()'";
			$this->errorinfo[] = $error;
			return;
		}
		

/*************************************************************************************************************
 * End Helper Functions                                                                                      *
 *************************************************************************************************************/
 
}
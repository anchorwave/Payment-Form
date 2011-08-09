<?php
class ProcessorErrorHandler {

	public static function checkPayment( $processor, $submission, $success ) {
		if ( ! $sucess ) {		
			$errors = $processor->getErrorInfo();
			
			if ( empty( $errors ) ) $errors[] = "Payment Failed: Please ensure that your card is valid or your billing address is correct.";
			
			while( $error = array_shift( $errors ) ) {
				switch( $error ) {
				case "Invalid data type for argument 1 in fuction 'setCardNumber()'":
					$error = "Invalid Card Number";
					break;
				case "Invalid data type for argument 1 in fuction 'setCardCode()'":
					$error = "Invalid Card Verification Code";
					break;
				case "Invalid value for state in fuction 'setCustomerState()'":
					$error = "Invalid State";
					break;
				case "Invalid value for zip in fuction 'setCustomerZip()'":
					$error = "Invalid Zipcode";
					break;
				case "Invalid value for phone in fuction 'setCustomerPhone()'":
					$error = "Invalid Phone Number";
					break;
				case "Required value x_card_num not set":
					$error = "Card Number is Required";
					break;
				case "The merchant login ID or password is invalid or the account is inactive. in fuction 'sendRequest()'":
					$error = "The merchant login ID or password is invalid or the account is inactive.";
					break;
				case "A valid amount is required. in fuction 'sendRequest()'":
					$error = "A valid amount is required.";
					break;
				case "Credit card expiration date is invalid. in fuction 'sendRequest()'":
					$error = "The Credi card expiration date is invalid.";
					break;
				}
				
				do_action( 'payment_form_error', $error );
			}
		}
	}
	
}
<?php
class PaymentHandler {
	
	private $payment_processer;
	private $payment_form_submission;
		
	public static function handlePayment( $submission ) {
		$handler = new PaymentHandler();
		$handler->_handlePayment( $submission );
	}
	
	private function _handlePayment( $submission ) {
	
		$this->getPaymentProcessor()->setCardType();
		$this->getPaymentProcessor()->setCardNumber( $submission->attr( 'card_number') );
		$this->getPaymentProcessor()->setExpiration( $submission->attr('card_expiration_month'), $submission->attr('card_expiration_year') );
		$this->getPaymentProcessor()->setCardCode( $submission->attr( 'card_code' ) );
		$this->getPaymentProcessor()->setAmount( $submission->getTotal() );
		
		$form = $submission->attr( 'form' );
		$this->getPaymentProcessor()->setDescription( $form->post_title );
		
		$this->getPaymentProcessor()->setCustomerName( $submission->attr('firstame'), $submission->attr('lastname') );
		$this->getPaymentProcessor()->setCustomerCompany( $submission->attr('company') );
		$this->getPaymentProcessor()->setCustomerAddress( $submission->attr('address1'), $submission->attr('address2') );
		$this->getPaymentProcessor()->setCustomerCity( $submission->attr('city') );
		$this->getPaymentProcessor()->setCustomerState( $submission->attr('state') );
		$this->getPaymentProcessor()->setCustomerZip( $submission->attr('zipcode') );
		$this->getPaymentProcessor()->setCustomerCountry( $submission->attr( 'country' ) );
		$this->getPaymentProcessor()->setCustomerEmail( $submission->attr('email') );
		$this->getPaymentProcessor()->setCustomerPhone( $submission->attr('phone') );
		$this->getPaymentProcessor()->setCustomerIP( $submission->attr('REMOTE_ADDR') );
		
		do_action( 'payment_handler_set_payment_instructions', $this->getPaymentProcessor(), $submission );

		$this->getPaymentProcessor()->sendRequest();
		
		do_action( 'payment_handled', $this->getPaymentProcessor(), $submission, $this->getPaymentProcessor()->getIsSuccess() );
		
		return $this->getPaymentProcessor()->getIsSuccess();
	}
	
	private function getPaymentProcessor() {
		if ( ! isset( $this->payment_processor ) ) {
		
			if ( ! ( $processor_name = PaymentFormOptions::attr( 'processor' ) ) ) {
				return false;
			}
			if ( ! ( $username = PaymentFormOptions::attr( 'username' ) ) ) {
				return false;
			}
			if ( ! ( $password = PaymentFormOptions::attr( 'password' ) ) ) {
				return false;
			}
			
			$processor_class = PaymentForm::getProcessor( $processor_name );
			$this->payment_processor = new $processor_class( array( $username, $password ) );
			
			if ( PaymentFormOptions::attr( 'test_mode' ) ) {
				$this->payment_processor->setTestRequest();
			}
			
		}
		return $this->payment_processor;
	}
	
}
?>
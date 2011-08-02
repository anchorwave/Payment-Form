<?php
class PaymentFormSubmission {

	private $payment_form_data;
	private $total;
	private $errors = array();

	public function __construct() {
		if ( ! empty( $_POST ) ) {
			$form = get_post( $_POST['form_id'] );
			$this->payment_form_data = array_merge( $_POST, array( 
				'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
				'country' => 'US',
				'form' => $form
			) );
		}
	}
	
	public static function submit() {
		$submission = new PaymentFormSubmission();
		$submission->_submit();
	}
	
	private function _submit() {
	
		if ( ! PaymentFormShortCode::isShortCodeUsed() ) return;
		if ( empty( $_POST['form_id'] ) ) return;
		if ( ! $this->checkSubmission() ) return;
		
		do_action( 'payment_form_submission', $this );
	}
	
	/*
	* Check for Errors
	*/
	
	public static function verifyNonce( $errors, $submission ) {
	
		$nonce = $submission->attr( 'payment_form_nonce' );
		if ( ( $_SESSION['payment_form_nonce'] == $nonce ) &&
			( $nonce != "" )
		) {
			$errors[] = "Your Payment has already been processed.";
		}
		$_SESSION['payment_form_nonce'] = $submission->attr( 'payment_form_nonce' );
		return $errors;
	}
	
	private function checkSubmission() {
		$errors = array();
		$errors = apply_filters( 'get_payment_form_submission_errors', $errors, $this );
		if ( ! empty( $errors ) ) {
			while( $error = array_shift( $errors ) ) {
				do_action( 'payment_form_error', $error );
			}
			return false;
		}
		return true;
	}
	
	/*
	* Creating and checking a nonce
	*/	
	
	public function getNonce() {
		return hash( 'sha512', self::getRandomString() );
	}
	
	public function getRandomString( $bits = 256 ) {
		$bytes = ceil($bits / 8);
		$return = '';
		for ($i = 0; $i < $bytes; $i++) {
			$return .= chr(mt_rand(0, 255));
		}
		return $return;
	}
	
	/*
	* Getters
	*/
	
	public function getTotal() {
		if ( ! isset( $this->total ) ) {
		
			$total = 0;
		
			$products = $this->attr( 'payment_form_product' );
			$products = ( array ) $products;
			
			if ( empty( $products ) ) return 0;
				
			foreach( $products as $id => $amounts ) {
				if ( ! ( get_post_type( $id ) == 'product' ) ) continue;
				
				$price = get_post_meta( $id, 'price', true );
				
				$price = (float) $price;
				
				while( $amount = array_shift( $amounts ) ) {
					$amount = ( float ) $amount;
					if ( $price == 0 ) $total += $amount;
					else $total += $amount * $price;
				}
			}
			
			$this->total = $total;
		}
		
		return $this->total;
	}
	
	public function attr( $name ) {
		if ( gettype( $name ) == 'array' ) {
			list( $key, $val ) = $name;
			return $this->payment_form_data[$key][$val];
		}
		return $this->payment_form_data[$name];
	}

}
?>
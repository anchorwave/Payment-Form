<?php
class PaymentProcessorRegistor {

	/*
	* Methods to detect and include available payment processors
	*/

	public static function registerPaymentProcessors() {
		$lib = new PaymentProcessorRegistor();
		$lib->_registerPaymentProcessors();
	}

	public function _registerPaymentProcessors() {
		$processors = scandir( PAYMENT_FORM_DIR . '/classes/payment_processors/' );
		while( $processor = array_shift( $processors ) ) {
			if ( $processor == '.' || $processor == '..' ) continue;
			$this->addProcessor( $processor );
		}
	}
	
	private function addProcessor( $filename ) {
		$this->requireProcessor( $filename );
		$this->addProcessorToPaymentFormOptions( $filename );		
	}
	
	private function requireProcessor( $filename ) {
		$filepath = PAYMENT_FORM_DIR . '/classes/payment_processors/' . $filename;
		require_once( $filepath );
	}
	
	private function addProcessorToPaymentFormOptions( $filename ) {
		PaymentForm::addProcessor( 
			$this->getProcessorLabel( $filename ), 
			$this->getProcessorClassname( $filename )
		);
	}
	
	private function getProcessorClassname( $filename ) {
		// Class name is filename
		$info = pathinfo( $filename );
		$class = $info['filename'];
		return $class;
	}
	
	private function getProcessorLabel( $filename ) {
		// Label is stored in a comment
		$subject = file_get_contents( PAYMENT_FORM_DIR . '/classes/payment_processors/' . $filename );
		$pattern = '/Name:([A-Za-z0-9\.\s]*)\n/';
		preg_match( $pattern, $subject, $matches );
		$label = $matches[1];
		$label = trim( $label );
		return $label;
	}
	
}
?>
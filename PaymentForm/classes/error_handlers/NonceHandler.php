<?php
class NonceHandler {

	public static function verifyNonce( $errors, $submission ) {
		$nonce = $submission->attr( 'payment_form_nonce' );
		if ( ( $_SESSION['payment_form_nonce'] == $nonce ) &&
			( $nonce != "" )
		) {
			$errors[] = "This form information has already been submitted.";
		}
		$_SESSION['payment_form_nonce'] = $submission->attr( 'payment_form_nonce' );
		return $errors;
	}	
	
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
	
	public static function addNonceField( $fields ) {
		$fields[] = sprintf( '<input type="hidden" name="payment_form_nonce" value="%s"/>',
			self::getNonce()
		);
		return $fields;
	}
	
}
?>
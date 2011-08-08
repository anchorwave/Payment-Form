<?php
class PaymentFormEmailHandler {

	public function __construct( $processor, $submission ) {
		$this->processor = $processor;
		$this->submission = $submission;
	}

	public static function sendReceiptEmail( $processor, $submission, $success ) {
		if ( ! $success ) return;
		
		if ( ! ( $to = $submission->attr( 'email' ) ) ) return;
		if ( ! ( $subject_str = get_post_meta( $submission->attr( 'form_id' ), 'email_subject', true ) ) ) return;
		if ( ! ( $body_str = get_post_meta( $submission->attr( 'form_id' ), 'email', true ) ) ) return;

		$handler = new PaymentFormEmailHandler( $processor, $submission, $to, $subject_str, $body_str );
		$handler->sendEmail( $to, $subject_str, $body_str );
	}
	
	public static function sendAdminEmail( $processor, $submission, $success ) {
		if ( ! $success ) return;

		if ( ! ( $to = PaymentFormOptions::attr( 'email' ) ) ) return;
		if ( ! ( $subject_str = get_post_meta( $submission->attr( 'form_id' ), 'admin_email_subject', true ) ) ) return;
		if ( ! ( $body_str = get_post_meta( $submission->attr( 'form_id' ), 'admin_email', true ) ) ) return;
				
		$handler = new PaymentFormEmailHandler( $processor, $submission );
		$handler->sendEmail( $to, $subject_str, $body_str );
	}

	private function sendEmail( $to, $subject_str, $body_str ) {
	
		$body_str = nl2br( $body_str );
		$output = new TransactionOutputHTML( array( 'processor' => $this->processor, 'submission' => $this->submission ) );
		$subject = $output->applyVariables( $subject_str );
		$body = $output->getOutput( $body_str );
		
		$headers = "";
		if ( $from = $this->getFrom() ) {
			$headers = $from . "\r\n";
		}
		
		wp_mail( $to, $subject,	$body, $headers );
	}
	
	public function getFrom() {
		$from_name = PaymentFormOptions::attr( 'email_from_name' );
		$from_email = PaymentFormOptions::attr( 'email_from' );
		
		if ( $from_name && $from_email ) {
			return sprintf( 'From: %s <%s>', $from_name, $from_email );
		} else if ( $from_name ) {
			return sprintf( 'From: %s', $from_name );
		} else if ( $from_email ) {
			return sprintf( 'From: %s', $from_email );
		}
	}

	public static function setContentType() {
		return 'text/html';
	}

}
?>
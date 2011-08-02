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
		$output = new TransactionOutput( array( 'processor' => $this->processor, 'submission' => $this->submission ) );
		$subject = $output->getOutput( $subject_str );
		$body = $output->getOutput( $body_str );
		
		// TODO move this to settings
		$headers = 'From: Adopt a Cop Tucson <noreply@adoptacoptucson.com>' . "\r\n";
		
		wp_mail( $to, $subject,	$body, $headers );
	}
	

	public static function setContentType() {
		return 'text/html';
	}

}
?>
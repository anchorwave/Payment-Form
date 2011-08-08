<?php
class TransactionOutputHTML extends TransactionOutput {
	
	public function getPurchaseTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/html/purchase.tpl' );
	}
	
	public function getProductTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/html/product.tpl' );
	}
	
	public function getBillingTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/html/billing.tpl' );
	}
	
	public function getAddressTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/html/address.tpl' );
	}
	
	public function filterOutput( $output ) {
		//$output = nl2br( $output );
		return $this->getTemplate()->getOutput( '/html/wrapper.tpl', array( 'output' => $output ) );
	}
	
	
}
?>
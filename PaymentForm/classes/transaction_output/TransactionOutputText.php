<?php
class TransactionOutputText extends TransactionOutput {
	
	public function getPurchaseTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/text/purchase.tpl' );
	}
	
	public function getProductTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/text/product.tpl' );
	}
	
	public function getBillingTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/text/billing.tpl' );
	}
	
	public function getAddressTemplate() {
		return file_get_contents( PAYMENT_FORM_DIR . '/templates/transaction_output/text/address.tpl' );
	}
	
	public function filterOutput( $output ) {

	}
	
	
}
?>
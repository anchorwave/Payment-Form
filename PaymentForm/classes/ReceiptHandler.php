<?php
class ReceiptHandler {

	public static function getOutput( $output, $purchase ) {
		$receipt_str = get_post_meta( $purchase['submission']->attr( 'form_id' ), "receipt", true );
		$receipt = new TransactionOutputHTML( $purchase );
		$output = $receipt->getOutput( $receipt_str );
		return $output;
	}	
}
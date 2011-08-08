<?php
interface iTransactionOutput {

	public function getPurchaseTemplate();
	public function getProductTemplate();
	public function getBillingTemplate();
	public function getAddressTemplate();
	public function filterOutput();
	public function getTemplate();
	
}
?>
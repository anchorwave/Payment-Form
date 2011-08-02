<?php
class TransactionOutput {

	private $processor;
	private $submission;
	
	private $template;

	public function __construct( $purchase ) {
		$this->processor = $purchase['processor'];
		$this->submission = $purchase['submission'];
		$this->template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/transaction_output' );
		setlocale( LC_MONETARY, 'en_US' );
	}

	public function getOutput( $template_str ) {
		return $this->template->getOutput( $template_str, array(
			'title' => $this->getTitle(),
			'description' => $this->getDescription(),
			'purchase' => $this->getPurchaseInfo( $products = $this->submission->attr( 'payment_form_product' ) ),
			'billing' => $this->getBillingInfo(),
			'firstname' => $this->submission->attr( 'firstname' ),
			'lastname' => $this->submission->attr( 'lastname' ),
			'address1' => $this->submission->attr( 'address1' ),
			'address2' => $this->submission->attr( 'address2' ),
			'city' => $this->submission->attr( 'city' ),
			'state' => $this->submission->attr( 'state' ),
			'zipcode' => $this->submission->attr( 'zipcode' ),
			'amount' => $this->submission->getTotal(),
			'email' => $this->submission->attr( 'email' ),
			'phone' => $this->submission->attr( 'phone' ),
			'amount' => money_format( '%i', $this->submission->getTotal() )
		), true );
	}
	
	private function getTitle() {
		$form = $this->submission->attr( 'form' );
		return $form->post_title;
	}
	
	private function getDescription() {
		$form = $this->submission->attr( 'form' );
		return $form->post_content;
	}
	
	public function getPurchaseInfo( $products ) {
		
		if ( empty( $products ) ) return;
		
		$temp = array();
		foreach( $products as $id => $value ) {
			if ( get_post_type( $id ) != 'product' ) continue;
			while( $individual = array_shift( $value ) ) {	
				$temp[] = $this->getProduct( $id, $individual );
			}
		}
		
		return $this->template->getOutput( '/purchase.tpl', array(
			'products' => implode( $temp )
		) );
	}
	
	private function getProduct( $id, $value ) {
	
		$price = get_post_meta( $id, 'price', true );
		$price = (double)$price;
		$product = get_post( $id );
		
		if ( (int)$price == 0 ) {
			$amount = $value;
			$amount = (double)$amount;
			$amount = money_format( '%i', $value );
		} else {
			$amount = sprintf( '%s x %d', money_format( '%i', $price ), $value );
		}
		
		return $this->template->getOutput( '/product.tpl', array(
			'title' => $product->post_title,
			'description' => $product->post_content,
			'amount' => $amount
		) );
	}
	
	private function getBillingInfo() {
		return $this->template->getOutput( '/billing.tpl', array(
			'firstname' => $this->submission->attr( 'firstname' ),
			'lastname' => $this->submission->attr( 'lastname' ),
			'address1' => $this->getAddress( 'Address', 'address1' ),
			'address2' => $this->getAddress( 'Address Line 2', 'address2' ),
			'city' => $this->submission->attr( 'city' ),
			'state' => $this->submission->attr( 'state' ),
			'zipcode' => $this->submission->attr( 'zipcode' ),
			'email' => $this->submission->attr( 'email' ),
			'phone' => $this->submission->attr( 'phone' ),
		) );
	}
	
	private function getAddress( $label, $key ) {
		if ( $this->submission->attr( $key ) ) {
			return $this->template->getOutput( '/address.tpl', array(
				'label' => $label,
				'address' => $this->submission->attr( $key )
			) );
		}
	}

}
?>
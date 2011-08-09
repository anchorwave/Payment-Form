<?php
class ProductShortCode {

	private $template;
	static $temporary = array();
	
	public function __construct() {
		$this->template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/payment_form' );
	}

	/*
	* Display a payment form
	*/
	
	public static function getOutput( $atts ) {	
		$shortcode = new ProductShortCode();
		return $shortcode->_getOutput( $atts );
	}
	
	public function _getOutput( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0,
			'display' => 'name',
			'value' => 0
		), $atts );
		extract( $atts );
		if ( ! $this->checkProduct( $id ) ) return;	
		$this->setupVariables( $id );
		return $this->getProductInfo( $display, $value );
	}
	
	private function setupVariables( $id ) {
		$post = get_post( $id );
		$custom = get_post_custom( $id );
		$custom = array_map( 'array_shift', $custom );
		$post = (array) $post;
		$post = array_merge( $post, $custom );
		$this->post = (object) $post;
	}
	
	private function checkProduct( $id ) {
		if ( (int) $id == 0 ) return false;
		if ( get_post_type( $id ) != "product" ) return false;
		
		return true;
	}	
	
	/*
	* Methods to get form content
	*/
	
	private function getProductInfo( $display, $value ) {
		switch( $display ) {
		case "inputname":
			do_action( 'payment_form_product', $this->post->ID );
			return sprintf( "payment_form_product[%d][]", $this->post->ID );
		case "price":
			setlocale(LC_MONETARY, 'en_US');
			return money_format( '%i', $this->post->price );
		case "name":
			return $this->post->post_title;
		case "description": 
			return $this->post->post_content;
		// The following are used to retain values from a submission
		case "value":
			return $this->getTemporary( $this->post->ID );
		}
		
	}
	
	public function setupTemporary( $id ) {
		if ( empty( self::$temporary[$id] ) ) {
			self::$temporary[$id] = 0;
		}
	}
	
	public function getTemporary( $id ) {
		$this->setupTemporary( $id );
		$temp = $_POST['payment_form_product'][$id][self::$temporary[$id]++];
		return $temp;
	}
	

}
?>
<?php
class PaymentFormShortCode {

	private $template;
	static $errors = array();
	static $purchase = array();
	static $products = array();
	
	public function __construct() {
		$this->template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/payment_form' );
	}

	/*
	* Display a payment form or receipt
	*/
	
	public static function getOutput( $atts ) {
		if ( ! empty( self::$purchase ) ) {
			$output = apply_filters( 'payment_form_receipt', null, self::$purchase );
		} else {
			$shortcode = new PaymentFormShortCode();
			$output = $shortcode->_getOutput( $atts );
		}
		return $output;
	}
	
	public function _getOutput( $atts ) {
		$atts = shortcode_atts( array(
			'id' => 0
		), $atts );
		extract( $atts );
		
		if ( ! $this->checkForm( $id ) ) return;
		
		$this->setupVariables( $id );
		return $this->getPaymentForm();
	}
	
	private function setupVariables( $id ) {
		$post = get_post( $id );
		$custom = get_post_custom( $id );
		$custom = array_map( 'array_shift', $custom );
		$post = (array) $post;
		$post = array_merge( $post, $custom );
		$this->post = (object) $post;
	}
		
	/*
	* Methods to get form 
	*/
	
	private function getPaymentForm() {
	
		$extra_footer = array();
		$extra_footer = apply_filters( 'get_payment_form_extra_fields_footer', $extra_footer );
		$extra_header = array();
		$extra_header = apply_filters( 'get_payment_form_extra_fields_header', $extra_header );
		
		return $this->template->getOutput( '/payment_form.tpl', array(
			'error' => $this->getErrorMessage(),
			'extra_footer' => implode( $extra_footer ),
			'extra_header' => implode( $extra_header ),
			'form_id' => $this->post->ID,
			'header' => $this->post->header,
			'footer' => $this->post->footer,
			'title' => $this->getTitle(),
			'products' => $this->getProducts(),
			'billing' => $this->getBilling(),
			'selected' => $this->getSelected(),
			'total' => $this->getTotal()
		) );
	}
	
	private function getTitle() {
		if ( ! get_post_meta( $this->post->ID, 'display_title', true ) ) return;
		return sprintf( '<h2>%s</h2>', $this->post->post_title );
	}
		
	private function getProducts() {
		$products = $this->post->form;
		$products = do_shortcode( $products ); // apply product variables
		$vars = array();
		$vars = apply_filters( 'get_product_variables', $vars );
		$products = $this->template->getOutput( $products, $vars, true );
		return $products;
	}
	
	private function getBilling() {
		return $this->template->getOutput( '/billing.tpl', array(
			'firstname' => $_POST['firstname'],
			'lastname' => $_POST['lastname'],
			'company' => $_POST['company'],
			'address1' => $_POST['address1'],
			'address2' => $_POST['address2'],
			'city' => $_POST['city'],
			'state' => $_POST['state'],
			'zipcode' => $_POST['zipcode'],
			'phone' => $_POST['phone'],
			'email' => $_POST['email'],
			'state_options' => $this->getStateOptions(),
			'year_options' => $this->getYearOptions(),
			'card_types' => $this->getCardTypes()
		) );
	}
	
	public function getCardTypes() {
		$card_types = PaymentFormOptions::attr( 'card_types' );
		
		$images = array();
		if ( PaymentFormOptions::attr( 'visa' ) ) {
			$images[] = $this->getImage( 'visa.png', 'Visa' );
		}
		if ( PaymentFormOptions::attr( 'mastercard' ) ) {
			$images[] = $this->getImage( 'mastercard.png', 'Mastercard' );
		}
		if ( PaymentFormOptions::attr( 'discover' ) ) {
			$images[] = $this->getImage( 'discover.png', 'Discover' );
		}
		if ( PaymentFormOptions::attr( 'amex' ) ) {
			$images[] = $this->getImage( 'american_express.png', 'American Express' );
		}
		
		if ( empty( $images ) ) return;
		
		return $this->template->getOutput( '/card_types.tpl', array( 'card_types' => implode( $images ) ) );
	}
	
	public function getImage( $filename, $alt ) {
		$src = sprintf( '%s/images/%s', PAYMENT_FORM_URL, $filename );
		return sprintf( '<img src="%s" alt="%s">', $src, $alt );
	}
	
	public function getYearOptions() {
		$temp = array();
		for( $i = 0; $i < 10; $i++ ) {
			$year = date( 'Y', strtotime( "+$i years" ) );			
			$temp[] = sprintf( '<option value="%1$s">%1$s</option>', $year );
		}
		return implode( $temp );
	}
	
	public function getStateOptions() {
		$states = array( 'AL', 'AK', 'AZ', 'AR', 'CA', 'CO','CT', 'DE', 'DC', 'FL', 'GA', 'HI', 'ID', 'IL', 
			'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',	'MA', 'MI',	'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ', 'NM', 
			'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
		);
		$temp = array();
		while( $state = array_shift( $states ) ) {
			$selected = '';
			if ( $_POST['state'] == $state ) $selected = 'selected="selected"';
			$temp[] = sprintf( '<option value="%1$s" %2$s>%1$s</option>', $state, $selected );
		}
		return implode( $temp );
	}
	
	private function getSelected() {
		if ( empty( $_POST['payment_form_product'] ) ) return;
	
		$temp = array();
		foreach( $_POST['payment_form_product'] as $id => $values ) {
			if ( get_post_type( $id ) != 'product' ) return;
			while( $value = array_shift( $values ) ) {
				$temp[] = sprintf( '<input type="hidden" name="product_selected[%d]" value="%s"/>', $id, $value );
			}
		}
		return implode( $temp );
	}
	
	public function getTotal() {
		$prices = array_map( 'PaymentFormShortCode::mapPrice', self::$products );
		return $this->template->getOutput( '/total.tpl', array(
			'prices' => implode( $prices )
		) );
	}
	
	public static function mapPrice( $id ) {
		return sprintf( 
			'<input type="hidden" name="product_price[%d]" value="%s"/>',
			$id, get_post_meta( $id, 'price', true )
		);
	}
	
	public static function addProduct( $id ) {
		if ( ! in_array( $id, self::$products ) ) {
			self::$products[] = $id;
		}
	}
	
	private function checkForm( $id ) {
		if ( (int) $id == 0 ) return false;
		if ( get_post_type( $id ) != "payment_form" ) return false;
		
		return true;
	}		
	
	/*
	* Error Messages
	*/
	
	private function getErrorMessage() {
		self::$errors = array_map( 'PaymentFormShortCode::errorWrap', self::$errors );
		return implode( self::$errors );
	}
	
	public static function addError( $error ) {
		self::$errors[] = $error;
	}
	
	public static function errorWrap( $error ) {
		return sprintf( '<div class="payment_form_error">%s</div>', $error );
	}
	
	/*
	* Receipt
	*/
	
	public static function setPaymentReceived( $processor, $submission, $success ) {
		if ( ! $success ) return;
		
		self::$purchase = array(
			'processor' => $processor,
			'submission' => $submission
		);
	}
	
	/*
	* Scripts/Styles
	*/
	
	public static function enqueueScripts() {
		if ( self::isShortcodeUsed() ) {
			do_action( 'payment_form_ssl_only' );
			if ( ! session_id() ) session_start();
			wp_enqueue_script( 'payment_form_shortcode_js',
				PAYMENT_FORM_URL . '/js/short_codes/payment_form/payment_form.js',
				array( 'jquery' ), '1.0', true
			);
			wp_enqueue_style( 'payment_form_css',
				PAYMENT_FORM_URL . '/css/short_codes/payment_form/payment_form.css'
			);
			do_action( 'payment_form_enqueue_scripts' );
		}
	}
	
	public function isShortcodeUsed() {
		global $posts;
		$pattern = get_shortcode_regex(); 
		preg_match( '/'.$pattern.'/s', $posts[0]->post_content, $matches ); 
		if (is_array($matches) && $matches[2] == 'payment_form') return true;
		return false;
	}

}
?>
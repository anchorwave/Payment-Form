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
	* Display a payment form
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
	
	private function checkForm( $id ) {
		if ( (int) $id == 0 ) return false;
		if ( get_post_type( $id ) != "payment_form" ) return false;
		
		return true;
	}	
	
	/*
	* Methods to get form content
	*/
	
	private function getPaymentForm() {
		return $this->template->getOutput( '/payment_form.tpl', array(
			'error' => $this->getErrorMessage(),
			'nonce' => $this->getNonce(),
			'form_id' => $this->post->ID,
			'header' => $this->post->header,
			'footer' => $this->post->footer,
			'title' => $this->post->post_title,
			'products' => $this->getProducts(),
			'billing' => $this->getBilling(),
			'selected' => $this->getSelected(),
			'total' => $this->getTotal()
		) );
	}
	
	private function getNonce() {
		return sprintf( '<input type="hidden" name="payment_form_nonce" value="%s"/>',
			PaymentFormSubmission::getNonce()
		);
	}
	
	private function getProducts() {
		$products = $this->post->form;
		$products = do_shortcode( $products );
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
			'year_options' => $this->getYearOptions()
		) );
	}
	
	public function getYearOptions() {
		$temp = array();
		for( $i = 0; $i < 10; $i++ ) {
			$year = date( 'Y', strtotime( "+$i years" ) );			
			$temp[] = sprintf( '<option value="%1$s">%1$s</option>', $year );
		}
		return implode( $temp );
	}
	
	/*
	*
	*/
	
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
	
	/*
	* Product Total
	*/
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
			
			wp_enqueue_script( 'payment_form_shortcode_js', // Handle
				PAYMENT_FORM_URL . '/js/short_codes/payment_form/payment_form.js',
				array( 'jquery' ), // Dependencies
				'1.0', // Version
				true // In Footer
			);
			
			wp_enqueue_style( 'payment_form_css',
				PAYMENT_FORM_URL . '/css/short_codes/payment_form/payment_form.css'
			);
			
			do_action( 'payment_form_enqueue_scripts' );
		}
	}
	
	public static function sslOnly() {
		if ( ! self::isSslAvailable() ) return;
		if( self::isSslOn() ) return;
		
		$rpath=str_replace('://','s://',$_SERVER["SCRIPT_URI"]);
		
		if(strpos($rpath,'www')===false)
			$rpath=str_replace('://','://www.',$rpath);
		if( $_GET ) {
			foreach($_GET as $g=>$v) {
				if(!$getstr) $getstr='?';
				else $getstr.='&';
				$getstr.=$g."=".$v;
			}
			$rpath.=$getstr;
		}
		header('location:'.$rpath);
		die();
	}
	
	public function isSslAvailable() {
		$curl = curl_init( sprintf( "https://%s/", $_SERVER['SERVER_NAME'] ) );
		curl_setopt($curl, CURLOPT_NOBODY, TRUE);
		curl_setopt($curl, CURL_HEADERFUNCTION, 'ignoreHeader');
		curl_exec($curl);
		$res = curl_errno($curl);

		if($res == 0) {
			$info = curl_getinfo($curl);
			if( $info['http_code'] == 200 ) {
				# Supports SSL
				return true;
			}
		}
		return false;
	}
	
	public function isSslOn() {
		if(strtolower($_SERVER['HTTPS'])!='on') return false;
		return true;
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
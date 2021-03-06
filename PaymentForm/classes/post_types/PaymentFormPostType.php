<?php
class PaymentFormPostType {
	
	static $template;
	
	public static function registerType() {
	
		$labels = array(
			'name' => _x( 'Payment Forms', 'post type general name' ),
			'singular_name' => _x( 'Form', 'post type singular name' ),
			'add_new' => _x( 'Add New', 'payment_form' ),
			'add_new_item' => __( 'Add New Form' ),
			'parent' => __( 'Parent Listing' )
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'hierarchical' => true,
			'menu_position' => null,
			'supports' => array('title','custom_fields')
		);

		register_post_type( 'payment_form', $args );
		flush_rewrite_rules();
	}
	
	public static function addMetaBoxForm() {
		add_meta_box( 'payment-form-meta-box-form',
			'Payment Form Template',
			'PaymentFormPostType::showMetaBoxForm', 
			'payment_form',
			'normal',
			'high'
		);
	}

	public static function addMetaBoxReceipt() {
		add_meta_box( 'payment-form-meta-box-receipt',
			'Receipt Template',
			'PaymentFormPostType::showMetaBoxReceipt', 
			'payment_form',
			'normal',
			'high'
		);
	}	
	
	public static function addMetaBoxEmail() {
		add_meta_box( 'payment-form-meta-box-email',
			'Email Template',
			'PaymentFormPostType::showMetaBoxEmail', 
			'payment_form',
			'normal',
			'high'
		);
	}	
	
	public static function addMetaBoxAdminEmail() {
		add_meta_box( 'payment-form-meta-box-admin-email',
			'Admin Email Template',
			'PaymentFormPostType::showMetaBoxAdminEmail', 
			'payment_form',
			'normal',
			'high'
		);
	}
	
	public static function addMetaBoxHeader() {
		add_meta_box( 'payment-form-meta-box-header',
			'Payment Form Header',
			'PaymentFormPostType::showMetaBoxHeader', 
			'payment_form',
			'normal',
			'high'
		);
	}
	
	public static function addMetaBoxFooter() {
		add_meta_box( 'payment-form-meta-box-footer',
			'Payment Form Footer',
			'PaymentFormPostType::showMetaBoxFooter', 
			'payment_form',
			'normal',
			'high'
		);
	}
	
	public static function addMetaBoxShortcode() {
		add_meta_box( 'payment-form-meta-box-shortcode',
			'Shortcode',
			'PaymentFormPostType::showMetaBoxShortcode', 
			'payment_form',
			'side',
			'high'
		);
	}
	
	public static function addMetaBoxProducts() {
		add_meta_box( 'payment-form-meta-box-products',
			'Products',
			'PaymentFormPostType::showMetaBoxProducts', 
			'payment_form',
			'side',
			'high'
		);	
	}
	
	public static function addMetaBoxSettings() {
		add_meta_box( 'payment-form-meta-box-settings',
			'Settings',
			'PaymentFormPostType::showMetaBoxSettings',
			'payment_form',
			'side',
			'high'
		);			
	}
	
	public static function showMetaBoxForm() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-form.tpl', self::getPostArgs() );
	}

	public static function showMetaBoxSettings() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-settings.tpl', self::getPostArgs() );
	}	
	
	public static function showMetaBoxReceipt() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-receipt.tpl', self::getPostArgs() );
	}
	
	public static function showMetaBoxEmail() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-email.tpl', self::getPostArgs() );
	}
	
	public static function showMetaBoxAdminEmail() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-admin-email.tpl', self::getPostArgs() );
	}		
	
	public static function showMetaBoxFooter() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-footer.tpl', self::getPostArgs() );
	}
	
	public static function showMetaBoxHeader() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-header.tpl', self::getPostArgs() );
	}
	
	public static function showMetaBoxShortcode() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-shortcode.tpl', self::getPostArgs() );
	}

	public static function showMetaBoxProducts() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/payment_form/' );
		echo self::$template->getOutput( 'meta-box-products.tpl', array( 'products' => self::getAvailableProducts() ) );
	}
	
	public function getAvailableProducts() {
		$products = get_posts( array(
			'post_type' => 'product',
			'numberposts' => -1
		) );
		$temp = array();
		while( $product = array_shift( $products ) ) {
			$temp[] = sprintf( '<strong>%d</strong> - %s', $product->ID, $product->post_title );
		}
		return implode( '<br/>', $temp );
	}
	
	public function getPostArgs() {
		global $post;
		$custom = get_post_custom( $post->ID );
		$custom = array_map( 'array_shift', $custom );
		$custom = array_map( 'htmlspecialchars', $custom );
		$custom = ( object ) $custom;
		
		return array(
			'id' => $post->ID,
			'form' => $custom->form,
			'receipt' => $custom->receipt,
			'footer' => $custom->footer,
			'header' => $custom->header,
			'email' => $custom->email,
			'email_subject' => $custom->email_subject,
			'admin_email' => $custom->admin_email,
			'admin_email_subject' => $custom->admin_email_subject,
			'transaction-output-variables' => self::getTransactionOutputVariables(),
			'form-variables' => self::getFormVariables(),
			'display_title_checked' => self::getChecked( $custom->display_title )
		);
	}
	
	public function getChecked( $value ) {
		if ( $value ) return 'checked="checked"';
	}

	public function getFormVariables() {
		return self::$template->getOutput( '/form-variables.tpl' );
	}
	
	public function getTransactionOutputVariables() {
		return self::$template->getOutput( '/transaction-output-variables.tpl' );
	}

	public static function savePost( $post_id ) {
		if ( get_post_type( $post_id ) == 'payment_form' ) {			
			update_post_meta( $post_id, 'form', $_POST['form'] );
			update_post_meta( $post_id, 'receipt', $_POST['receipt'] );
			update_post_meta( $post_id, 'email', $_POST['email'] );
			update_post_meta( $post_id, 'email_subject', $_POST['email_subject'] );
			update_post_meta( $post_id, 'admin_email_subject', $_POST['admin_email_subject'] );
			update_post_meta( $post_id, 'admin_email', $_POST['admin_email'] );
			update_post_meta( $post_id, 'header', $_POST['header'] );
			update_post_meta( $post_id, 'footer', $_POST['footer'] );
			update_post_meta( $post_id, 'display_title', $_POST['display_title'] );
		}
	}
	
	public static function enqueueScripts() {
		if ( self::isPaymentFormPostType() ) {
			wp_enqueue_style( 'payment_form_post_type_css', PAYMENT_FORM_URL . '/css/post_types/payment_form/payment_form.css' );
		}
	}
	
	public function isPaymentFormPostType() {
		if ( $_GET['post_type'] == 'payment_form' ) return true;
		if ( get_post_type( $_GET['post'] ) == 'payment_form' ) return true;
		return false;
	}
	
}
?>
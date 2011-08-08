<?php
class TransactionPostType {
	
	static $template;
	
	public static function registerType() {
	
		$labels = array(
			'name' => _x( 'Transactions', 'post type general name' ),
			'singular_name' => _x( 'Transaction', 'post type singular name' ),
			'add_new' => _x( 'Add New', 'transaction' ),
			'add_new_item' => __( 'Add New Transaction' ),
			'parent' => __( 'Parent Listing' )
		);
		
		$args = array(
			'labels' => $labels,
			'publicly_queryable' => false,
			'show_ui' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('title','custom_fields')
		);

		register_post_type( 'transaction', $args );
		flush_rewrite_rules();
	}
	
	public static function addMetaBox() {
		add_meta_box( 'transaction-meta-box',
			'Transaction Details',
			'TransactionPostType::showMetaBox', 
			'transaction',
			'normal',
			'high'
		);
	}
	
	public static function showMetaBox() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/transaction/' );
		echo self::$template->getOutput( 'meta-box.tpl', self::getPostArgs() );
	}
	
	public function getPostArgs() {
		global $post;
		$custom = get_post_custom( $post->ID );
		$custom = ( object ) array_map( 'array_shift', $custom );

		return array(
			'items' => self::getPurchaseInfo(),
			'firstname' => $custom->firstname,
			'lastname' => $custom->lastname,
			'address1' => $custom->address1,
			'address2' => $custom->address2,
			'city' => $custom->city,
			'state' => $custom->state,
			'zipcode' => $custom->zipcode,
			'phone' => $custom->phone,
			'email' => $custom->email,
			'amount' => $custom->amount,
			'success' => ( $custom->success ) ? 'Yes' : 'No',
			'transaction_id' => $custom->transaction_id
		);
	}
	
	public function getPurchaseInfo() {
		global $post;
		$items = get_post_meta( $post->ID, 'items', true );
		$output = new TransactionOutputHTML( null );
		return $output->getPurchaseInfo( $items );
	}
	
	public function _savePost( $post_id, $processor, $submission ) {
		if ( get_post_type( $post_id ) == 'transaction' ) {
			update_post_meta( $post_id, 'firstname', $submission->attr( 'firstname' ) );
			update_post_meta( $post_id, 'lastname', $submission->attr( 'lastname' ) );
			update_post_meta( $post_id, 'address1', $submission->attr( 'address1' ) );
			update_post_meta( $post_id, 'address2', $submission->attr( 'address2' ) );
			update_post_meta( $post_id, 'city', $submission->attr( 'city' ) );
			update_post_meta( $post_id, 'state', $submission->attr( 'state' ) );
			update_post_meta( $post_id, 'zipcode', $submission->attr( 'zipcode' ) );
			update_post_meta( $post_id, 'phone', $submission->attr( 'phone' ) );
			update_post_meta( $post_id, 'email', $submission->attr( 'email' ) );
			update_post_meta( $post_id, 'amount', $submission->getTotal() );
			update_post_meta( $post_id, 'items', $submission->attr( 'payment_form_product' ) );
			update_post_meta( $post_id, 'transaction_id', $processor->getTransactionId() );
			update_post_meta( $post_id, 'success', $processor->getIsSuccess() );
		}
	}
	
	/*
	* Save Transaction information from a purchase
	* Listener to the payment_handled event
	*/
	
	public static function saveTransaction( $processor, $submission, $success ) {
		if ( ! $success ) return;
	
		$post_title = sprintf( '%s %s', $submission->attr( 'firstname' ), $submission->attr( 'lastname') );
		
		$post_id = wp_insert_post( array(
			'post_title' => $post_title,
			'post_status' => 'publish',
			'post_author' => 1,
			'post_type' => 'transaction'
		) );
		
		self::_savePost( $post_id, $processor, $submission );	
	}
	
	public static function enqueueScripts() {
		if ( self::isThisPostType() ) {
			wp_enqueue_style( 'transaction_post_type_css', PAYMENT_FORM_URL . '/css/post_types/transaction/transaction.css' );
		}
	}
	
	public function isThisPostType() {
		if ( $_GET['post_type'] == 'transaction' ) return true;
		if ( get_post_type( $_GET['post'] ) == 'transaction' ) return true;
		return false;
	}
	
	public static function removeAddNew() {
		global $submenu;
		unset($submenu['edit.php?post_type=transaction'][10]);
	}
	
}
?>
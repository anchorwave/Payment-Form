<?php
class ProductPostType {
	
	static $template;
	
	public static function registerType() {
	
		$labels = array(
			'name' => _x( 'Products', 'post type general name' ),
			'singular_name' => _x( 'Product', 'post type singular name' ),
			'add_new' => _x( 'Add New', 'product' ),
			'add_new_item' => __( 'Add New Product' ),
			'parent' => __( 'Parent Listing' )
		);
		
		$args = array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'hierarchical' => true,
			'menu_position' => null,
			'supports' => array('title','editor','custom_fields')
		);

		register_post_type( 'product', $args );
		flush_rewrite_rules();
	}
	
	public static function addMetaBox() {
		add_meta_box( 'product-meta-box',
			'Product Details',
			'ProductPostType::showMetaBox', 
			'product',
			'normal',
			'high'
		);
	}
	
	public static function showMetaBox() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/post_types/product/' );
		echo self::$template->getOutput( 'meta-box.tpl', self::getPostArgs() );
	}
	
	public function getPostArgs() {
		global $post;
		$custom = get_post_custom( $post->ID );
		$custom = ( object ) array_map( 'array_shift', $custom );
		
		return array(
			'price' => $custom->price
		);
	}

	public static function savePost( $post_id ) {
		if ( get_post_type( $post_id ) == 'product' ) {			
			update_post_meta( $post_id, 'price', $_POST['price'] );
		}
	}
	
	/*
	* Edit the post type columns
	*/
	
	public static function editColumns( $columns ) {
		return array(
			'cb' => '<input type="checkbox"/>',
			'title' => __( 'Product' ),
			'id' => __( 'Product Id' ),
			'date' => __( 'Date' )
		);
	}
	
	public static function manageColumns( $column, $post_id ) {
		switch( $column ) {
		case "id":
			echo $post_id;
			break;
		}
	}
	
}
?>
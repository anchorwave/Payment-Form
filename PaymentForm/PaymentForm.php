<?php

/*
Plugin Name: Payment Form
Plugin URI: http://anchorwave.com/
Description: Provide ability to create payment forms.
Version 1.0
Author: Mike Allen

Copyright 2011 Anchor Wave Internet Solutions (email : support@anchorwave.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.i

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Anchor Wave respectfully requests that any modifications made to this software
that would benefit the general population be submitted to support@anchorwave.com
for inclusion in subsequent versions.
*/

define( 'PAYMENT_FORM_DIR', dirname( __FILE__ ) );
define( 'PAYMENT_FORM_URL', plugins_url( null, __FILE__ ) );

require_once( PAYMENT_FORM_DIR . '/classes/common/AwTemplate.php' );
require_once( PAYMENT_FORM_DIR . '/classes/post_types/PaymentFormPostType.php' );
require_once( PAYMENT_FORM_DIR . '/classes/post_types/TransactionPostType.php' );
require_once( PAYMENT_FORM_DIR . '/classes/post_types/ProductPostType.php' );
require_once( PAYMENT_FORM_DIR . '/classes/short_codes/PaymentFormShortCode.php' );
require_once( PAYMENT_FORM_DIR . '/classes/short_codes/ProductShortCode.php' );
require_once( PAYMENT_FORM_DIR . '/classes/pages/PaymentFormOptions.php' );
require_once( PAYMENT_FORM_DIR . '/classes/common/AwSSL.php' );

require_once( PAYMENT_FORM_DIR . '/classes/error_handlers/NonceHandler.php' );
require_once( PAYMENT_FORM_DIR . '/classes/error_handlers/ProcessorErrorHandler.php' );

require_once( PAYMENT_FORM_DIR . '/classes/PaymentProcessorRegistor.php' );
require_once( PAYMENT_FORM_DIR . '/classes/PaymentProcessor.php' );

require_once( PAYMENT_FORM_DIR . '/classes/PaymentHandler.php' );
require_once( PAYMENT_FORM_DIR . '/classes/PaymentFormSubmission.php' );
require_once( PAYMENT_FORM_DIR . '/classes/PaymentFormEmailHandler.php' );
require_once( PAYMENT_FORM_DIR . '/classes/ReceiptHandler.php' );

require_once( PAYMENT_FORM_DIR . '/classes/transaction_output/iTransactionOutput.php' );
require_once( PAYMENT_FORM_DIR . '/classes/transaction_output/TransactionOutput.php' );
require_once( PAYMENT_FORM_DIR . '/classes/transaction_output/TransactionOutputHTML.php' );

add_action( 'admin_menu', 'PaymentForm::add_admin_menus' );
add_action( 'init', 'PaymentProcessorRegistor::registerPaymentProcessors' );
add_action( 'admin_init', 'PaymentFormOptions::registerSettings' );

add_action( 'payment_form_submission', 'PaymentHandler::handlePayment' );

add_filter( 'wp_mail_content_type', 'PaymentFormEmailHandler::setContentType' );

add_action( 'payment_handled', 'TransactionPostType::saveTransaction', 10, 3 );
add_action( 'payment_handled', 'PaymentFormEmailHandler::sendReceiptEmail', 10, 3 );
add_action( 'payment_handled', 'PaymentFormEmailHandler::sendAdminEmail', 10, 3 );
add_action( 'payment_handled', 'PaymentFormShortCode::setPaymentReceived', 10, 3 );
add_action( 'payment_handled', 'ProcessorErrorHandler::checkPayment', 10, 3 );

add_filter( 'payment_form_receipt', 'ReceiptHandler::getOutput', 10, 2 );

add_action( 'payment_form_ssl_only', 'AwSSL::sslOnly' );

// Product Shortcode
add_shortcode( 'product', 'ProductShortCode::getOutput' );

// Payment Form Shortcode
add_shortcode( 'payment_form', 'PaymentFormShortCode::getOutput' );

add_action( 'wp', 'PaymentFormShortCode::enqueueScripts' );

add_action( 'payment_form_error', 'PaymentFormShortCode::addError' );
add_action( 'payment_form_product', 'PaymentFormShortCode::addProduct' );


// Payment Form Submission ( hooks need to occur after payment form shortcode hooks )
add_action( 'wp', 'PaymentFormSubmission::submit' );

add_filter( 'get_payment_form_extra_fields_footer', 'NonceHandler::addNonceField' );
add_filter( 'get_payment_form_submission_errors', 'NonceHandler::verifyNonce', 10, 2 );

// Payment Form Post Type
add_action( 'init', 'PaymentFormPostType::registerType' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxHeader' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxFooter' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxForm' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxEmail' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxAdminEmail' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxReceipt' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxShortcode' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxProducts' );
add_action( 'admin_menu', 'PaymentFormPostType::addMetaBoxSettings' );
add_action( 'save_post', 'PaymentFormPostType::savePost' );
add_action( 'admin_init', 'PaymentFormPostType::enqueueScripts' );

// Transaction Post Type
add_action( 'init', 'TransactionPostType::registerType' );
add_action( 'admin_menu', 'TransactionPostType::addMetaBox' );
add_action( 'admin_menu', 'TransactionPostType::removeAddNew' );
add_action( 'admin_init', 'TransactionPostType::enqueueScripts' );

// Product Post Type
add_action( 'init', 'ProductPostType::registerType' );
add_action( 'admin_menu', 'ProductPostType::addMetaBox' );
add_action( 'save_post', 'ProductPostType::savePost' );
add_filter( 'manage_edit-product_columns', 'ProductPostType::editColumns' );
add_filter( 'manage_product_posts_custom_column', 'ProductPostType::manageColumns', 10, 2 );

class PaymentForm {

	public static $processors = array();

	public static function printPage() {
		switch( $_GET['page'] ) {
		case "payment-form-options":
			echo PaymentFormOptions::getOutput();
			break;
		}
	}
	
	public static function add_admin_menus() {
		add_options_page( 'Payment Form Options', // title
			'Payment Form', // menu label
			'manage_options', // capability
			'payment-form-options', // page
			'PaymentForm::printPage' // callback
		);
	}
	
	public function addProcessor( $label, $class ) {
		self::$processors[$label] = $class;
	}
	
	public function getProcessor( $label ) {
		return self::$processors[$label];
	}

}

?>
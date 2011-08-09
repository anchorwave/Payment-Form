<?php
class PaymentFormOptions {

	static $template;

	/*
	* Output
	*/
	
	public function getOutput() {
		self::$template = new AwTemplate( PAYMENT_FORM_DIR . '/templates/pages/payment_form_options' );
		return self::$template->getOutput( '/options.tpl', self::getOutputArgs() );
	}
	
	public function getOutputArgs() {
		return array(
			'settings_fields' => self::getSettingsFields(),
			'do_settings_sections' => self::getSettingsSections()
		);
	}
	
	public function getSettingsFields() {
		ob_start();
		settings_fields( 'payment_form_options' );
		return ob_get_clean();
	}
	
	public function getSettingsSections() {
		ob_start();
		do_settings_sections( 'payment_form_options' ); // page name
		return ob_get_clean();
	}
	
	/*
	* Register Settings
	*/
	
	public static function registerSettings() {
		register_setting( 'payment_form_options', 'payment_form_options', 'PaymentFormOptions::sanitize' ); //  group, name of options
		// section id, title, callback, page name
		add_settings_section( 'payment_form_general', 'General Settings', 'PaymentFormOptions::sectionTextGeneral', 'payment_form_options' );
		add_settings_section( 'payment_form_email', 'Email Settings', 'PaymentFormOptions::sectionTextEmail', 'payment_form_options' );
		add_settings_section( 'payment_form_card_types', 'Accepted Card Types', 'PaymentFormOptions::sectionTextCardTypes', 'payment_form_options' );
		add_settings_section( 'payment_form_developer', 'Developer Options', 'PaymentFormOptions::sectionTextDeveloper', 'payment_form_options' );
		
		add_settings_field(
			'payment_form_processor',
			'Processor',
			'PaymentFormOptions::inputCallbackProcessor',
			'payment_form_options',
			'payment_form_general'
		);

		add_settings_field(
			'payment_form_username',
			'Username',
			'PaymentFormOptions::inputCallbackUsername',
			'payment_form_options',
			'payment_form_general'
		);

		add_settings_field(
			'payment_form_password',
			'Password',
			'PaymentFormOptions::inputCallbackPassword',
			'payment_form_options',
			'payment_form_general'
		);
		
		// EMail Settings
		add_settings_field(
			'payment_form_email',
			'Admin Email',
			'PaymentFormOptions::inputCallbackEmail',
			'payment_form_options',
			'payment_form_email'
		);

		add_settings_field(
			'payment_form_email_from',
			'Email From',
			'PaymentFormOptions::inputCallbackEmailFrom',
			'payment_form_options',
			'payment_form_email'
		);

		add_settings_field(
			'payment_form_email_from_name',
			'Email From Name',
			'PaymentFormOptions::inputCallbackEmailFromName',
			'payment_form_options',
			'payment_form_email'
		);
		
		// Developer Settings
		add_settings_field(
			'payment_form_developer_mode',
			'Developer Mode',
			'PaymentFormOptions::inputCallbackDeveloperMode',
			'payment_form_options',
			'payment_form_developer'
		);
		add_settings_field(
			'payment_form_test_mode',
			'Test Mode',
			'PaymentFormOptions::inputCallbackTestMode',
			'payment_form_options',
			'payment_form_developer'
		);
		
		// Card Types
		add_settings_field(
			'payment_form_discover',
			'Discover',
			'PaymentFormOptions::inputCallbackDiscover',
			'payment_form_options',
			'payment_form_card_types'
		);
		add_settings_field(
			'payment_form_amex',
			'American Express',
			'PaymentFormOptions::inputCallbackAmex',
			'payment_form_options',
			'payment_form_card_types'
		);
		add_settings_field(
			'payment_form_visa',
			'Visa',
			'PaymentFormOptions::inputCallbackVisa',
			'payment_form_options',
			'payment_form_card_types'
		);
		add_settings_field(
			'payment_form_mastercard',
			'Mastercard',
			'PaymentFormOptions::inputCallbackMastercard',
			'payment_form_options',
			'payment_form_card_types'
		);
	}
	
	/*
	* Section Callbacks
	*/
	
	public static function sectionTextGeneral() {
		echo self::$template->getOutput( '/sections/general.tpl' );
	}
	
	public static function sectionTextCardTypes() {
		echo self::$template->getOutput( '/sections/card_types.tpl' );
	}
	
	public static function sectionTextEmail() {
		echo self::$template->getOutput( '/sections/email.tpl' );
	}

	public static function sectionTextDeveloper() {
		echo self::$template->getOutput( '/sections/developer.tpl' );
	}	
	
	/*
	* Input Callbacks
	*/
	
	public static function inputCallbackProcessor() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/processor.tpl', array(
			'name' => 'payment_form_options[processor]',
			'options' => self::getProcessorOptions()
		) );
	}

	public static function inputCallbackUsername() {
		$options = get_option( 'payment_form_options' );
		
		echo self::$template->getOutput( '/fields/username.tpl', array(
			'name' => 'payment_form_options[username]',
			'value' => $options['username']
		) );
	}
	
	public static function inputCallbackPassword() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/password.tpl', array(
			'name' => 'payment_form_options[password]',
			'value' => $options['password']
		) );
	}
	
	public static function inputCallbackEmail() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/email.tpl', array(
			'name' => 'payment_form_options[email]',
			'value' => $options['email']
		) );
	}	

	public static function inputCallbackDeveloperMode() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/developer_mode.tpl', array(
			'name' => 'payment_form_options[developer_mode]',
			'checked' => self::getChecked( $options['developer_mode'] )
		) );
	}
	
	public static function inputCallbackTestMode() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/test_mode.tpl', array(
			'name' => 'payment_form_options[test_mode]',
			'checked' => self::getChecked( $options['test_mode'] )
		) );
	}		

	public static function inputCallbackEmailFrom() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/email_from.tpl', array(
			'name' => 'payment_form_options[email_from]',
			'value' => $options['email_from']
		) );
	}
	
	public static function inputCallbackEmailFromName() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/email_from_name.tpl', array(
			'name' => 'payment_form_options[email_from_name]',
			'value' => $options['email_from_name']
		) );
	}		

	public static function inputCallbackVisa() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/visa.tpl', array(
			'name' => 'payment_form_options[visa]',
			'checked' => self::getChecked( $options['visa'] )
		) );
	}
	
	public static function inputCallbackDiscover() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/discover.tpl', array(
			'name' => 'payment_form_options[discover]',
			'checked' => self::getChecked( $options['discover'] )
		) );
	}

	public static function inputCallbackMastercard() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/mastercard.tpl', array(
			'name' => 'payment_form_options[mastercard]',
			'checked' => self::getChecked( $options['mastercard'] )
		) );
	}

	public static function inputCallbackAmex() {
		$options = get_option( 'payment_form_options' );
		echo self::$template->getOutput( '/fields/amex.tpl', array(
			'name' => 'payment_form_options[amex]',
			'checked' => self::getChecked( $options['amex'] )
		) );
	}	
	
	/*
	* Sanitize Callbacks
	*/
	
	public static function sanitize( $input ) {
		return $input;
	}
	
	/*
	* HTML Helpers
	*/
	
	public function getProcessorOptions() {
		$processors = PaymentForm::$processors;
		$options = get_option( 'payment_form_options' );
		
		if ( empty( $processors ) ) return;
		
		$rows = array();
		foreach( $processors as $label => $value ) {
			$rows[] = sprintf( '<option value="%s" %s>%s</option>', $label, self::getSelected( $label, $options['processor'] ), $label );
		}
		
		return implode( $rows );
	}
	
	public function getChecked( $value ) {
		if ( ( ( int ) $value ) == 1 ) return 'checked="checked"';
	}
	
	public function getSelected( $val1, $val2 ) {
		if ( $val1 === $val2 ) return 'selected="selected"';
	}
	
	/*
	* Getters/Setters
	*/
	
	public function getOptions() {
		$options = get_option( 'payment_form_options' );
		return $options;
	}
	
	public function attr( $name ) {
		$options = self::getOptions();
		if ( gettype( $name ) == 'array' ) {
			list( $key, $val ) = $name;
			return $options[$key][$val];
		}
		return $options[$name];
	}
	
}
?>
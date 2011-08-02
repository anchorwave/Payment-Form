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
		add_settings_section( 'payment_form_general', 'General Settings', 'PaymentFormOptions::sectionTextGeneral', 'payment_form_options' ); // section id, title, callback, page name
		
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

		add_settings_field(
			'payment_form_email',
			'Admin Email',
			'PaymentFormOptions::inputCallbackEmail',
			'payment_form_options',
			'payment_form_general'
		);		

	}
	
	/*
	* Section Callbacks
	*/
	
	public static function sectionTextGeneral() {
		echo self::$template->getOutput( '/sections/general.tpl', array() );
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
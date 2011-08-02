<?php
if ( ! class_exists( 'AwTemplate' ) ) {

	class AwTemplate {

		private $_dir;

		public function __construct( $dir ) {
			$this->_dir = $dir;
		}

		public function getOutput( $template_file, $template_vars = array(), $is_path_content = false ) {
		
			if ( $is_path_content ) {
				$template_str = $template_file;
			} else {
				$template_str = file_get_contents( $this->_dir . $template_file );
			}
			
			if ( ! ( strlen( $template_str ) > 0 ) ) return false;

			
			if ( count( $template_vars ) ) {
				foreach( $template_vars as $key => $val ) {
					$template_str = str_replace( "%$key%", $val, $template_str );
				}
			}
			
			return $template_str;
		}
	}

}
?>
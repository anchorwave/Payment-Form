<?php
class AwSSL {

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
	
}
?>
<?php
include('Exception.php');
include('Token.php'); 
include('Provider.php');

class OAuth2 {
	public static function provider($name, array $options = NULL) {
		$name = ucfirst(strtolower($name));
		$class = 'OAuth2_Provider_'.$name;
		include_once 'Provider/'.$name.'.php';
		return new $class($options);
	}
}
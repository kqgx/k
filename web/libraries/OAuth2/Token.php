<?php

abstract class OAuth2_Token {

	public static function factory($name = 'access', array $options = null) {
		$name	= ucfirst(strtolower($name));
		$class	= 'OAuth2_Token_'.$name;
		include_once 'Token/'.$name.'.php';
		return new $class($options);
	}

	public function __get($key) {
		return $this->$key;
	}
	
	public function __isset($key) {
		return isset($this->$key);
	}
}
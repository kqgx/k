<?php

$autoload['libraries']		= array('dcache', 'duri');
$autoload['language']		= array();
$autoload['drivers']		= array('cache');
$autoload['config']			= array();
$autoload['helper']			= array('durl', 'function', 'system', 'url', 'language', 'cookie', 'directory', 'my');
$autoload['model']			= array();

$autoload['packages'][]		= WEBPATH;
$autoload['packages'][]		= FCPATH;
$autoload['packages'][]		= APPPATH;
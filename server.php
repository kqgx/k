<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists("{$_SERVER['DOCUMENT_ROOT']}$uri" . $uri)) {
    return false;
}
require_once __DIR__ . '/public/index.php';

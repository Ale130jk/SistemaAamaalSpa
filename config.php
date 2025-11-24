<?php

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";

$host = $_SERVER['HTTP_HOST'];
$script_dir = str_replace('\\', '/', __DIR__); 
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']); 
$folder = str_replace($doc_root, '', $script_dir); 
define('BASE_URL', $protocol . '://' . $host . $folder . '/'); 
define('BASE_PATH', __DIR__ . '/');
define('GEMINI_API_KEY', 'AIzaSyAG5i3c80vb-4DFBGZViMhx9irvwUMMgS8');
date_default_timezone_set('America/Lima');
?>
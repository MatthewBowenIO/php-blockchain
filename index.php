<?php

require_once('Models/BlockChain.php');
require_once('API/api.php');

ini_set('log_errors' , '1');
ini_set('error_log' , 'errors.log');
ini_set('display_errors' , '0');

$inputs = array();
$post = array();

$inputs['httpHost'] = substr($_SERVER['HTTP_HOST'], 0, 14);

$scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']) );
$inputs['URI'] = '/'.substr_replace($_SERVER['REQUEST_URI'], '', 0, strlen($scriptName));
$inputs['URI'] = str_replace('//', '/', $inputs['URI']);

$inputs['method'] = @$_SERVER['REQUEST_METHOD'];

$inputs['raw_input'] = @file_get_contents('php://input');

@parse_str($inputs['raw_input'] , $post);

$inputs = array_merge($inputs,$post);

$app = new API($inputs);
$app->run();
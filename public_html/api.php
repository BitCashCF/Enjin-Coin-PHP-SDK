<?php

// Composer autoloading
include __DIR__ . '/../vendor/autoload.php';

// Load API class
$class = $_REQUEST['class'];

$server = new Zend\Json\Server\Server();
$server->setClass('\EnjinCoin\Api\\' . $class);

// SMD request
if ('GET' === $_SERVER['REQUEST_METHOD']) {
	$server->setTarget('/api.php')
		->setEnvelope(Zend\Json\Server\Smd::ENV_JSONRPC_2);

	$smd = $server->getServiceMap();

	header('Content-Type: application/json');
	echo $smd;
	return;
}

// Authenticate
// @todo: restrict method access to config permissions
if(!empty($_SERVER['X-Auth-Key'])) \EnjinCoin\Auth::init($_SERVER['X-Auth-Key']);

$server->handle();

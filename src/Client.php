<?php

var_dump($_SERVER['REMOTE_ADDR']);die;
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once  __DIR__ . '/../config/rpc_client.php';

try {
    $service = new \Zyyphper\LaravelRpc\BaseClientService("default","default",$config['default']);
    $result = $service->add(1,2);
    var_dump($result);
    sleep(1);
    $service1 = new \Zyyphper\LaravelRpc\BaseClientService("default","default1",$config['default']);
    $result = $service->sub(2,1);
    var_dump($result);
}catch (Throwable $exception) {
    var_dump($exception);
}
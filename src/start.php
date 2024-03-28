<?php

require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once  __DIR__ . '/../config/rpc_server.php';
try {
    $service = new \Zyyphper\LaravelRpc\Rpc\Server($config);
    register_shutdown_function('checkTermination');
    function checkTermination() {
        echo "关闭服务";
        exit();
    }
    $service->start();
}catch (Throwable $exception) {
    var_dump($exception);
    $service->close();
}

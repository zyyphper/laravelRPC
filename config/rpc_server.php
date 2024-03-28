<?php

use \Zyyphper\LaravelRpc\Rpc\Protocol;

return [
    'method' => Protocol::JSON,
    'host' => '127.0.0.1',
    'port' => 6666,
    'sock_type' => SOL_TCP,
    'module_server' => [
        'default' => \Zyyphper\LaravelRpc\BaseServerService::class,
        'default1' => \Zyyphper\LaravelRpc\BaseServerService::class
    ],
    'request_auth' => false,
    'auth_strategy' => \Zyyphper\LaravelRpc\Rpc\Auth\Strategy\EncryptStringStrategy::class
];
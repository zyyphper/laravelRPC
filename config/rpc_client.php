<?php

use \Zyyphper\LaravelRpc\Rpc\Protocol;

return [
    'default' => [
        'method' => Protocol::JSON,
        'host' => '127.0.0.1',
        'port' => 6666,
        'sock_type' => SOL_TCP,
        'appid' => '1',
        'secret_key' => '123456',
        'request_auth' => false,
        'auth_strategy' => \Zyyphper\LaravelRpc\Rpc\Auth\Strategy\EncryptStringStrategy::class
    ],
];

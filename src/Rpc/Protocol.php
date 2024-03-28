<?php


namespace Zyyphper\LaravelRpc\Rpc;

use Zyyphper\LaravelRpc\Rpc\Client\Protocol\Json AS JsonClient;

class Protocol
{
    /**
     * RPC服务连接（json_rpc协议）
     */
    const JSON = 1;

    public static function registerClient($config)
    {
        switch ($config['method']) {
            case self::JSON:
                return JsonClient::load($config);
        }
    }
}

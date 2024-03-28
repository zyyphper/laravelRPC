<?php
/**
 * 远程调用服务
 */
namespace Zyyphper\LaravelRpc;


use Zyyphper\LaravelRpc\Rpc\Client;

class BaseClientService extends Client
{
    public string $rpcServiceName = "default";

    public string $rpcModuleName = "default";
}

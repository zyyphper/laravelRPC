<?php

namespace Zyyphper\LaravelRpc\Rpc;


use Zyyphper\LaravelRpc\Rpc\Client\RpcClientInterface;

class Client
{
    /**
     * 远程调用服务名
     * @var string
     */
    public string $rpcServiceName = "";

    /**
     * 远程调用模块名
     * @var string
     */
    public string $rpcModuleName = "";
    /**
     * 远程调用服务对象
     * @var RpcClientInterface
     */
    protected  $rpcClient = null;


    public function __construct(string $rpcServiceName = "",string $rpcModuleName = "",$config = [])
    {
        if (!empty($rpcServiceName)) $this->rpcServiceName = $rpcServiceName;
        if (!empty($rpcModuleName)) $this->rpcModuleName = $rpcModuleName;
        if (empty($config)) $config = config('rpc_client')[$this->rpcServiceName];
        $config['service_name'] = $this->rpcServiceName;
        $config['module_name'] = $this->rpcModuleName;
        //远程调用服务初始化
        if (!empty($this->rpcServiceName) && is_null($this->rpcClient)) {
            $this->rpcClient = Protocol::registerClient($config);
        }
    }

    /**
     * 通过内置远程调用服务对象去远程调用指定服务方法
     * @param $name
     * @param $arguments
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        //判断当前服务是否已经连接
        if (!is_null($this->rpcClient)) {
            return $this->rpcClient->call($name,$arguments);
        }
        //非远程服务 报错弹出
        throw new \Exception("not exist function");
    }

    public function __destruct()
    {
        $this->rpcClient->close();
    }
}

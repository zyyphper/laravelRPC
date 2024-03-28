<?php


namespace Zyyphper\LaravelRpc\Rpc\Client;


interface RpcClientInterface
{
    public function open():object;

    public function call($functionName,$params,$id=1):array;

    public function close():bool;
}

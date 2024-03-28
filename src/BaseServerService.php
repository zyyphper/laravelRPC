<?php
/**
 * 可被远程调用服务
 */
namespace Zyyphper\LaravelRpc;


class BaseServerService
{
    public function add($param1,$param2)
    {
        return $param1 + $param2;
    }

    public function sub($param1,$param2)
    {
        return $param1 - $param2;
    }
}

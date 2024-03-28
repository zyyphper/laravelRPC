<?php


namespace Zyyphper\LaravelRpc\Rpc\Auth\Strategy;


interface AuthStrategy
{
    public function generateAuth($server,$module,$param):bool;

    public function checkAuth($server,$module,$param):bool;
}
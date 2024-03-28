<?php


namespace Zyyphper\LaravelRpc\Rpc\Auth;


use Zyyphper\LaravelRpc\Rpc\Auth\Strategy\AuthStrategy;

class User
{
    protected static $strategyInstance;

    private function __construct($strategy)
    {
        self::$strategyInstance = app($strategy);
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function load($strategy)
    {
        if (!self::$strategyInstance instanceof AuthStrategy) {
            return new self($strategy);
        }
        return self::class;
    }

    public function login($params): string
    {
        return self::$strategyInstance->login($params);
    }
}
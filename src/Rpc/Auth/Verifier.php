<?php


namespace Zyyphper\LaravelRpc\Rpc\Auth;


use Zyyphper\LaravelRpc\Rpc\Auth\Strategy\AuthStrategy;

class Verifier
{
    protected static $userInstance;

    private function __construct($user)
    {
        self::$userInstance = $user;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function load($strategy)
    {
        if (!self::$userInstance instanceof AuthStrategy) {
            return new self($strategy);
        }
        return self::class;
    }

    public function generateAuth($server,$module,$param): string
    {
        return self::$userInstance->generateAuth($server,$module,$param);
    }

    public function checkAuth($server,$module,$param): bool
    {
        return self::$userInstance->checkAuth($server,$module,$param);
    }
}
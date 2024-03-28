<?php
/**
 * 加密字符串策略
 * 使用固定长度密钥进行权限认证
 */
namespace Zyyphper\LaravelRpc\Rpc\Auth\Strategy;


class EncryptStringStrategy implements AuthStrategy
{
    public string $secretKey = "123456";

    public function checkAuth($server,$module,$param): bool
    {
        if ($checkResult = openssl_decrypt($param,"AES-128-CBC",$this->secretKey)) return false;
        if (!$param = json_decode($checkResult,true)) return false;
        //对当前服务当前模块进行鉴权
        if ($module == 'default1') {
            return false;
        }
        return true;
    }

    public function generateAuth($server,$module,$param): bool
    {
        return openssl_encrypt(json_encode($param),"AES-128-CBC",$this->secretKey);
    }
}

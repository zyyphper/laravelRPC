<?php


namespace Zyyphper\LaravelRpc\Rpc\Server\Protocol;


use Zyyphper\LaravelRpc\Rpc\Client\BaseClient;
use Zyyphper\LaravelRpc\Rpc\Client\RpcClientInterface;

class Json extends BaseClient implements RpcClientInterface
{

    /**
     * @var resource
     */
    protected $socket;

    public function open(): object
    {
        //创建socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            throw new \Exception("unable to create socket:" . socket_strerror(socket_last_error()));
        }
        socket_set_option( $this->socket, SOL_SOCKET, SO_REUSEADDR, 1 );
        socket_set_option( $this->socket, SOL_SOCKET, SO_REUSEPORT, 1 );
        $result = socket_connect($this->socket, $this->config['host'], $this->config['port']);
        if ($result === false) {
            throw new \Exception("unable to connect socket:" . socket_strerror(socket_last_error()));
        }
        return $this;
    }

    public function checkAuth(): bool
    {
        try {
            $checkResult = openssl_encrypt('laravel',"AES-128-CBC","123456");
            $result = socket_write($this->socket, $checkResult, strlen($checkResult));
            if ($result === false) {
                throw new \Exception("unable to send request:" . socket_strerror(socket_last_error()));
            }
            $response = "";
            do {
                $buffer = socket_read($this->socket, 1024);
                $response .= $buffer;
                if (strlen($response) < 1024) {
                    break;
                }
            } while (true);
            echo $response."\n";
            $checkAuthResult = json_decode($response,true);
            if ($checkAuthResult['code'] != 0) {
                throw new \Exception("check auth fail:" . $checkResult['msg']);
            }
            return true;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    public function call($functionName, $params, $id = 1): array
    {
        $requestBody = json_encode([
            'jsonrpc' => '2.0',
            'method' => $functionName,
            'params' => $params,
            'id' => 1
        ]);
        $result = socket_write($this->socket, $requestBody . "\n", strlen($requestBody) + 1);
        if ($result === false) {
            throw new \Exception("unable to send request:" . socket_strerror(socket_last_error()));
        }
        $response = "";
        do {
            $buffer = socket_read($this->socket, 1024);
            $response .= $buffer;
            if (strlen($response) < 1024) {
                break;
            }
        } while (true);
        return json_decode($response, true);
    }

    public function close(): bool
    {
        socket_close($this->socket);
        return true;
    }
}

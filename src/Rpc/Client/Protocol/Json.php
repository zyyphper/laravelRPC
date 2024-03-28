<?php


namespace Zyyphper\LaravelRpc\Rpc\Client\Protocol;


use Zyyphper\LaravelRpc\Rpc\Auth\Verifier;
use Zyyphper\LaravelRpc\Rpc\Client\RpcClientInterface;

class Json implements RpcClientInterface
{
    public static array $config;
    /**
     * @var self
     */
    private static ?Json $instance = null;

    private function __construct(array $config)
    {
        self::$config = $config;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function load(array $config)
    {
        //相同配置且存在返回相同的服务端对象
        if (!is_null(self::$instance) && self::$instance::$config && self::$instance::$config['service_name'] == $config['service_name']) {
            self::$instance::$config = $config;
            return self::$instance;
        }
        self::$instance = (new self($config))->open();
        self::$instance->login();
        return self::$instance;
    }

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
        $result = socket_connect($this->socket, self::$config['host'], self::$config['port']);
        if ($result === false) {
            throw new \Exception("unable to connect socket:" . socket_strerror(socket_last_error()));
        }
        return $this;
    }

    public function login(): bool
    {
        $loginToken = self::$config['appid'].":".self::$config['secret_key'];
        $result = socket_write($this->socket, $loginToken, strlen($loginToken));
        if ($result === false) {
            throw new \Exception("unable to send request:" . socket_strerror(socket_last_error()));
        }
        $result = $this->readBuffer();
        if ($result['code'] != 0) {
            return false;
        }
        return true;
    }

    public function call($functionName, $params, $id = 1): array
    {
        $requestBody = json_encode([
            'jsonrpc' => '2.0',
            'method' => self::$config['module_name'].":".$functionName,
            'params' => self::$config['request_auth'] ? Verifier::load(self::$config['auth_strategy'])->generateAuth(self::$config['module_name'],$functionName,$params) : $params,
            'id' => $id
        ]);
        $result = socket_write($this->socket, $requestBody, strlen($requestBody));
        if ($result === false) {
            throw new \Exception("unable to send request:" . socket_strerror(socket_last_error()));
        }
        return $this->readBuffer();
    }

    public function close(): bool
    {
        socket_write($this->socket, "close", strlen("close"));
        socket_close($this->socket);
        return true;
    }

    protected function readBuffer()
    {
        $response = "";
        do {
            $buffer = socket_read($this->socket, 1024);
            $response .= $buffer;
            if (strlen($response) < 1024) {
                break;
            }
        } while (true);
        return json_decode($response,true);
    }
}

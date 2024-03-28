<?php

namespace Zyyphper\LaravelRpc\Rpc;


use Event;
use EventBase;
use EventConfig;
use Zyyphper\LaravelRpc\Rpc\Auth\Verifier;

class Server
{
    protected array $config =[];
    protected array $eventArr = [];
    protected array $clientArr = [];
    protected array $loginClientArr = [];
    protected $socket = null;
    protected EventBase $eventBase;



    public function __construct($config = [])
    {
        if (empty($config)) $config = config("rpc_server");
        $this->config = $config;
    }

    public function start()
    {
        try {
            echo "server start"."\n";
            $this->init();
            //创建socket监听服务
            $event = new Event($this->eventBase,$this->socket,Event::READ | Event::PERSIST,$this->connect);
            $event->add(60);
            echo "start listening"."\n";
            $this->eventBase->dispatch();
        }catch (\Throwable $exception) {
            var_dump($exception);
            $this->close();
        }
    }

    public function __get($name)
    {
        return \Closure::fromCallable([$this,$name]);
    }

    public function connect($listenSocket,$eventFlag)
    {
        // socket_accept接受连接，生成一个新的socket，一个客户端连接socket
        $connectionSocket = socket_accept($listenSocket);
        echo "socket：".intval($connectionSocket)." accept\n";
        $this->clientArr[] = $connectionSocket;
        //从客户端读取数据
        $this->listenReadEvent($connectionSocket);
    }

    public function request($connectionSocket,$eventFlag)
    {
        $request = $this->readBuffer($connectionSocket);
        if (empty($request)) return;
        echo "socket：".intval($connectionSocket)." receive：".$request."\n";
        if ($request == 'close') {
            $this->clientClose($connectionSocket);
            return;
        }
        if (!array_key_exists(intval($connectionSocket),$this->loginClientArr)) {
            //登录
            $this->login($connectionSocket,$request);
            return;
        }
        if ($this->loginClientArr[intval($connectionSocket)] == 0) {
            //向客户端返回数据
            $this->listenWriteEvent($connectionSocket,json_encode(['code'=>1,'msg'=>'login fail']));
            return;
        }
        $this->dispatch($connectionSocket,$request);
    }

    public function response($connectionSocket,$eventFlag,$responseData)
    {
        //响应数据
        $this->writeBuffer($connectionSocket,$responseData);
        // 在写回调中逻辑执行完毕后，将该写事件删除掉...
        $writeEvent = $this->eventArr[intval($connectionSocket)]['write'];
        $writeEvent->del();
        unset($this->eventArr[intval($connectionSocket)]['write']);
    }

    protected function init()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option( $this->socket, SOL_SOCKET, SO_REUSEADDR, 1 );
        socket_set_option( $this->socket, SOL_SOCKET, SO_REUSEPORT, 1 );
        socket_bind($this->socket,$this->config['host'],$this->config['port']);
        socket_listen($this->socket,5);
        socket_set_nonblock($this->socket);//设置非阻塞
        $eventConfig = new EventConfig();
        $this->eventBase  = new EventBase($eventConfig);
        if ( 'epoll' != $this->eventBase->getMethod() ) {
            exit( "not epoll" );
        }
    }

    protected function readBuffer($connectionSocket)
    {
        $request = "";
        do {
            $buffer = socket_read($connectionSocket, 1024);
            $request .= $buffer;
            if (strlen($request) < 1024) {
                break;
            }
        } while (true);
        $request = trim($request);//数据清洗
        return $request;
    }

    protected function writeBuffer($connectionSocket,$responseData)
    {
        echo "socket：".intval($connectionSocket)." return：".$responseData."\n";
        socket_write($connectionSocket,$responseData,strlen($responseData));
    }

    protected function listenReadEvent($connectionSocket)
    {
        $readEvent = new Event($this->eventBase,$connectionSocket,Event::READ | Event::PERSIST,$this->request);
        $readEvent->add(60);
        $this->eventArr[intval($connectionSocket)]['read'] = $readEvent;
    }

    protected function listenWriteEvent($connectionSocket,$data)
    {
        $writeEvent = new Event($this->eventBase,$connectionSocket,Event::WRITE | Event::PERSIST,$this->response,$data);
        $writeEvent->add(60);
        $this->eventArr[intval($connectionSocket)]['write'] = $writeEvent;
    }

    protected function clientClose($connectionSocket)
    {
        $readEvent = $this->eventArr[intval($connectionSocket)]['read'];
        $readEvent->del();
        unset($this->eventArr[intval($connectionSocket)]['read'] );
        unset($this->authClientArr[intval($connectionSocket)]);
        if (($key = array_search($connectionSocket, $this->clientArr)) !== false) {
            unset($this->clientArr[$key]);
        }
        echo "socket：".intval($connectionSocket)." close"."\n";
        //向客户端返回数据
        $this->listenWriteEvent($connectionSocket,json_encode(['code'=>0,'msg'=>'close socket']));
    }

    protected function login($connectionSocket,$request)
    {
        socket_getpeername($this->socket,$address);
        echo "socket：".intval($connectionSocket)." start login"."\n";
        echo "ip：".$address."\n";
        list($account,$password) = explode(':',$request);

        if (!$checkResult) {
            echo "socket：".intval($connectionSocket)." no permission\n";
            $checkURI = 0;
            $responseData = json_encode(['code'=>1,'msg'=>'auth fail']);
        } else {
            $checkURI = 1;
            $responseData = json_encode(['code'=>0,'msg'=>'auth success']);
        }
        $this->authClientArr[intval($connectionSocket)] = $checkURI;
        //向客户端返回数据
        $this->listenWriteEvent($connectionSocket,$responseData);
    }

    protected function dispatch($connectionSocket,$request)
    {
        try {
            $request = json_decode($request,true);
            //json解码 服务分发处理
            if(!$request || !isset($request['jsonrpc']) || $request['jsonrpc'] != '2.0' || !isset($request['method']) || count(explode(':',$request['method'])) != 2) {
                $this->listenWriteEvent($connectionSocket,json_encode(['code'=>1,'msg'=>'invalid request']));
                return ;
            }
            list($func,$method) = explode(':',$request['method']);
            $params = $request['params'] ?? [];
            // 认证
            if($this->config['request_auth']) {
                $verifier = Verifier::load($this->config['auth_strategy']);
                if (!$checkResult = $verifier->checkAuth($this->config['auth_strategy'],$func,$params)) {
                    $this->listenWriteEvent($connectionSocket,json_encode(['code'=>1,'msg'=>'auth fail']));
                    return ;
                }
            }

            if (!method_exists($this->config['module_server'][$func],$method)) {
                $responseData = json_encode(['code'=>1,'msg'=>'not exist function']);
            } else {
                $param = call_user_func_array([app($this->config['module_server'][$func]),$method],$request['params']);
                $responseData = json_encode(['code'=>0,'msg'=>'success','data'=>$param]);
            }
        } catch (\Throwable $exception) {
            $responseData = json_encode(['code'=>1,'msg'=>$exception->getMessage()]);
        }
        //向客户端返回数据
        $this->listenWriteEvent($connectionSocket,$responseData);
    }

    public function close()
    {
        echo "server close"."\n";
        socket_close($this->socket);
    }

    public function __destruct()
    {
        $this->close();
    }

}

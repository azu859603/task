<?php

use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use Workerman\MySQL\Connection;

require_once __DIR__ . '/../../src/Connection.php';
require_once __DIR__ . '/../Config/config.php';

class Events
{
    public static $db = null;

    public static function onWorkerStart($worker)
    {
        self::$db = new Connection(DB_CONFIG['host'], DB_CONFIG['port'], DB_CONFIG['user'], DB_CONFIG['password'], DB_CONFIG['db_name']);
    }

    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, json_encode([
            'type' => 'cmd',
            'order' => 'init',
            'client_id' => $client_id,
        ]));
        $_SESSION['auth_timer_id'] = Timer::add(5, function ($client_id) {
            Gateway::closeClient($client_id);
        }, array($client_id), false);
    }

    public static function onMessage($client_id, $message)
    {
        $req_data = json_decode($message, true);
        if (!empty($req_data['type']) && $req_data['type'] == 'init') {
//            $_SESSION['uid'] = $req_data['data']['uid'];
            $_SESSION['uid'] = $req_data['uid'];
            Timer::del($_SESSION['auth_timer_id']);
        }
    }

    /**
     * 当用户断开连接时触发的方法
     */
//    public static function onClose($client_id)
//    {
//        if (isset($_SESSION['uid'])) {
//            $info = explode('_', $_SESSION['uid']);
//            $role = $info[0] == 'member' ? 'member' : 'backend_member';
//            $other_client_id = Gateway::getClientIdByUid($info[0] . '_' . $info[1]);
//            if (!$other_client_id) {
//                // 没有任何一端在线，设为离线
//                self::$db->update('rf_' . $role)->cols(['online_status' => 0])->where('id=' . $info[1])->query();
//            }
//        }
//    }

    /**
     * 断线操作
     * @param $client_id
     */
    public static function onClose($client_id)
    {
        if (!empty($_SESSION['uid'])) {
            $info = explode('_', $_SESSION['uid']);
            if($info[0]=='member'){
                $uid = $info[1];
                self::$db->update('rf_member')->cols(['online_status' => 0])->where('id=' . $uid)->query();
            }
        }
    }

}

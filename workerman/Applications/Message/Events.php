<?php

use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;
use Workerman\MySQL\Connection;

require_once __DIR__ . '/../../src/Connection.php';
require_once __DIR__ . '/../Config/config.php';

class Events
{
    public static function onWorkerStart($worker)
    {
        global $db;
        $db = new Connection(DB_CONFIG['host'], DB_CONFIG['port'], DB_CONFIG['user'], DB_CONFIG['password'], DB_CONFIG['db_name']);
    }

    /**
     * 连接上后操作
     * @param $client_id
     */
    public static function onConnect($client_id)
    {
        Gateway::sendToClient($client_id, self::jsonEncode('init', ['client_id' => $client_id]));
        $_SESSION['auth_timer_id'] = Timer::add(5, function ($client_id) {
            Gateway::closeClient($client_id);
        }, array($client_id), false);
    }

    /**
     * 消息操作
     * @param $client_id
     * @param $message
     */
    public static function onMessage($client_id, $message)
    {
        $result = json_decode($message, true);
        if (!empty($result['type'])) {
            $type = $result['type'];
            switch ($type) {
                case 'init':
                    // 取消定时器
                    Timer::del($_SESSION['auth_timer_id']);
                    // 更改在线字段
                    if (!empty($result['data']['uid'])) {
                        $uid = $result['data']['uid'];
                        $_SESSION['uid'] = $uid;
                        global $db;
                        $sql = "UPDATE `rf_member` SET `online_status` = 1 WHERE `id` = {$uid} and `status` = 1";
                        $db->query($sql);
                    }
                    break;
                default:
                    break;
            }
        }
    }

    /**
     * 断线操作
     * @param $client_id
     */
    public static function onClose($client_id)
    {
        if (!empty($_SESSION['uid'])) {
            $uid = $_SESSION['uid'];
            global $db;
            $sql = "UPDATE `rf_member` SET `online_status` = 0 WHERE `id` = {$uid}";
            $db->query($sql);
        }
    }

    /**
     * @param $type
     * @param $data
     * @return false|string
     */
    public static function jsonEncode($type, $data)
    {
        return json_encode([
            'type' => $type,
            'data' => $data,
        ]);
    }

}

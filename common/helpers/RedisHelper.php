<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:56
 */

namespace common\helpers;

use Yii;
use yii\web\UnprocessableEntityHttpException;

class RedisHelper
{

    /**
     * redis限制并发
     * @param $memberId
     * @param $actionName
     * @param $time
     * @return mixed|object|\yii\redis\Connection|null
     * @throws UnprocessableEntityHttpException
     */
    public static function verifyOld($memberId, $actionName, $time = 3)
    {
        $redis = Yii::$app->redis;
        $thisAppEnglishName = Yii::$app->params['thisAppEnglishName'];
        $redis_key = $thisAppEnglishName . '_member_id_' . $memberId . '_' . $actionName;
        $redis_result = $redis->get($redis_key);
        if (empty($redis_result)) {
            $redis->set($redis_key, 1);
            $redis->expire($redis_key, $time);
        } else {
            throw new UnprocessableEntityHttpException('请勿频繁操作！');
        }
        return $redis;
    }

    /**
     * redis限制并发
     * @param $memberId
     * @param $actionName
     * @param $time
     * @return mixed|object|\yii\redis\Connection|null
     * @throws UnprocessableEntityHttpException
     */
    public static function verify($memberId, $actionName, $time = 3)
    {
        $thisAppEnglishName = Yii::$app->params['thisAppEnglishName'];
        $redis_key = $thisAppEnglishName . '_member_id_' . $memberId . '_' . $actionName;

        $oRedisLock = new RedisLock($redis_key, $time);// 创建redislock对象
        $is_lock = $oRedisLock->lock();
        if (!$is_lock) {
            throw new UnprocessableEntityHttpException('请勿频繁操作！');
        }
        return Yii::$app->redis;
    }
}
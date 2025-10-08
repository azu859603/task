<?php

namespace common\helpers;
use Yii;

class RedisLock
{
    private $_redis;
    private $key;
    private $expire;

    /**
     * 初始化redis
     *
     * RedisLock constructor.
     * @param string $key 锁标识
     * @param int $expire 锁过期时间
     */
    public function __construct($key, $expire = 3)
    {
        try{
            $this->_redis = Yii::$app->redis;
            $this->key = $key;
            $this->expire = $expire;
        }catch (\Exception $ex) {
            return  new \Exception($ex->getMessage());
        }
    }

    /**
     * 获取锁
     *
     * @return bool
     */
    public function lock()
    {
        //不存在则返回1，存在返回0
        $is_lock = $this->_redis->setnx($this->key, time() + $this->expire);
        // 不能获取锁
        if (!$is_lock) {
            // 判断锁是否过期
            $lock_time = $this->_redis->get($this->key);

            // 锁已过期，删除锁，重新获取
            if (time() > $lock_time) {
                $this->unlock($this->key);
                $is_lock = $this->_redis->setnx($this->key, time() + $this->expire);
            }
        }

        return $is_lock ? true : false;
    }

    /**
     * 释放锁
     *
     * @return Boolean
     */

    public function unlock()
    {
        return $this->_redis->del($this->key);
    }
}
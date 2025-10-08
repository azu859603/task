<?php

namespace frontend\controllers;

use yii\helpers\Json;
use yii\web\Controller;
use Yii;

class KsController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex21(){
        $get = Yii::$app->request->get();
        $redis_key = !empty($get['ip']) ? $get['ip'] : "0";
        $redis = Yii::$app->redis;
        $redis->set($redis_key, Json::encode(['callback' => !empty($get['callback']) ? $get['callback'] : "", "type" => 21]));
        $redis->expire($redis_key, 86400);
        echo("完成");
        exit;
    }
}
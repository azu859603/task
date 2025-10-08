<?php

namespace frontend\controllers;

use common\models\tea\RegisterIp;
use yii\helpers\Json;
use yii\web\Controller;
use Yii;

class DyController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex11()
    {
        $get = Yii::$app->request->get();
        $redis_key = !empty($get['ip']) ? $get['ip'] : "0";
        $redis = Yii::$app->redis;
        $redis->set($redis_key, Json::encode(['callback' => !empty($get['callback']) ? $get['callback'] : "", "type" => 11]));
        $redis->expire($redis_key, 86400);
//        $model = new RegisterIp();
//        $model->ip = !empty($get['ip']) ? $get['ip'] : "";
//        $model->callback = !empty($get['callback']) ? $get['callback'] : "";
//        $model->type = 11;
//        $model->save(false);
//        if ($get['callback_url'] != "__CALLBACK_URL__") {
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $get['callback_url']);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            $response = curl_exec($ch);
//            if (!$response) {
//                echo('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
//                exit;
//            }
//            echo 'HTTP Status Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
//            echo 'Response Body: ' . $response . PHP_EOL;
//            exit;
//        }
        echo("完成");
        exit;
    }

    public function actionIndex12()
    {
        $get = Yii::$app->request->get();
        $redis_key = !empty($get['ip']) ? $get['ip'] : "0";
        $redis = Yii::$app->redis;
        $redis->set($redis_key, Json::encode(['callback' => !empty($get['callback']) ? $get['callback'] : "", "type" => 12]));
        $redis->expire($redis_key, 86400);
//        $model = new RegisterIp();
//        $model->ip = !empty($get['ip']) ? $get['ip'] : "";
//        $model->callback = !empty($get['callback']) ? $get['callback'] : "";
//        $model->type = 12;
//        $model->save(false);
//        if ($get['callback_url'] != "__CALLBACK_URL__") {
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, $get['callback_url']);
//            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//            $response = curl_exec($ch);
//            if (!$response) {
//                echo('Error: "' . curl_error($ch) . '" - Code: ' . curl_errno($ch));
//                exit;
//            }
//            echo 'HTTP Status Code: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . PHP_EOL;
//            echo 'Response Body: ' . $response . PHP_EOL;
//            exit;
//        }
        echo("完成");
        exit;
    }

    public function actionIndex13()
    {
        $get = Yii::$app->request->get();
        $redis_key = !empty($get['ip']) ? $get['ip'] : "0";
        $redis = Yii::$app->redis;
        $redis->set($redis_key, Json::encode(['callback' => !empty($get['callback']) ? $get['callback'] : "", "type" => 13]));
        $redis->expire($redis_key, 86400);
        echo("完成");
        exit;
    }
}
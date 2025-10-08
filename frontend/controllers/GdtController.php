<?php

namespace frontend\controllers;

use common\helpers\CommonPluginHelper;
use common\helpers\GdtHelper;
use common\models\tea\GdtToken;
use common\models\tea\RegisterIp;
use yii\web\Controller;
use Yii;

class GdtController extends Controller
{
    public $enableCsrfValidation = false;


    public function actionTest()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->ip = $get['ip'];
        $model->type = 1;
        $model->save(false);
        if ($get['callback'] != "__CALLBACK__") {
            $data = [
                'actions' => [
                    [
                        'user_id' => [
                            'hash_imei' => $get['muid'],
                        ],
//                        'action_type' => 'REGISTER',
                        'action_type' => 'ACTIVATE_APP',
                    ]
                ]
            ];
            CommonPluginHelper::curl_json(urldecode($get['callback']), $data);
            echo("完成1");
            exit;
        }
        echo("完成2");
        exit;
    }

    public function actionTest2()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->ip = $get['ip'];
        $model->type = 2;
        $model->save(false);
        if ($get['callback'] != "__CALLBACK__") {
            $data = [
                'actions' => [
                    [
                        'user_id' => [
                            'hash_imei' => $get['muid'],
                        ],
                        'action_type' => 'ACTIVATE_APP',
                    ]
                ]
            ];
            CommonPluginHelper::curl_json(urldecode($get['callback']), $data);
            echo("完成1");
            exit;
        }
        echo("完成2");
        exit;
    }

    public function actionTest3()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->ip = $get['ip'];
        $model->type = 3;
        $model->save(false);
        if ($get['callback'] != "__CALLBACK__") {
            $data = [
                'actions' => [
                    [
                        'user_id' => [
                            'hash_imei' => $get['muid'],
                        ],
                        'action_type' => 'ACTIVATE_APP',
                    ]
                ]
            ];
            CommonPluginHelper::curl_json(urldecode($get['callback']), $data);
            echo("完成1");
            exit;
        }
        echo("完成2");
        exit;
    }

    public function actionTest4()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->ip = $get['ip'];
        $model->type = 4;
        $model->save(false);
        if ($get['callback'] != "__CALLBACK__") {
            $data = [
                'actions' => [
                    [
                        'user_id' => [
                            'hash_imei' => $get['muid'],
                        ],
                        'action_type' => 'ACTIVATE_APP',
                    ]
                ]
            ];
            CommonPluginHelper::curl_json(urldecode($get['callback']), $data);
            echo("完成1");
            exit;
        }
        echo("完成2");
        exit;
    }

    public function actionTest5()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->ip = $get['ip'];
        $model->type = 5;
        $model->save(false);
        if ($get['callback'] != "__CALLBACK__") {
            $data = [
                'actions' => [
                    [
                        'user_id' => [
                            'hash_imei' => $get['muid'],
                        ],
                        'action_type' => 'ACTIVATE_APP',
                    ]
                ]
            ];
            CommonPluginHelper::curl_json(urldecode($get['callback']), $data);
            echo("完成1");
            exit;
        }
        echo("完成2");
        exit;
    }

    public function actionIndex()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->muid = $muid = $get['muid'];
        $model->ip = $get['ip'];
        $model->type = 1;
        $model->save(false);
        $client_id = Yii::$app->debris->config('gdt_client_id');
        $account = GdtToken::find()->where(['advertiser_id' => $client_id])->asArray()->one();
        $access_token = $account['access_token'];
        $account_id = Yii::$app->debris->config('gdt_account_id');
        $user_action_set_id = Yii::$app->debris->config('gdt_user_action_set_id');
        GdtHelper::eqq_user_actions_add($access_token, $account_id, $user_action_set_id, 'ACTIVATE_APP', $muid);
        exit("完成");
    }

    public function actionOauth()
    {
        // 授权访问 https://developers.e.qq.com/oauth/authorize?client_id=1112003001&redirect_uri=https%3A%2F%2Fg02mx67.com%2Fgdt%2Foauth&state=1
        $get = Yii::$app->request->get();
        $authorization_code = $get['authorization_code'];
        $client_id = Yii::$app->debris->config('gdt_client_id');
        $client_secret = Yii::$app->debris->config('gdt_client_secret');
        $redirect_uri = Yii::$app->request->hostInfo . "/gdt/oauth";
        $result = GdtHelper::eqq_oauth_token($client_id, $client_secret, "authorization_code", $redirect_uri, $authorization_code);
        $model = new GdtToken();
        $model->advertiser_id = $client_id;
        $model->access_token = $result['data']['access_token'];
        $model->refresh_token = $result['data']['refresh_token'];
        $model->access_token_expires_in = $result['data']['access_token_expires_in'];
        $model->refresh_token_expires_in = $result['data']['refresh_token_expires_in'];
        $model->save(false);
        exit("完成");
    }


    public function actionIndex1()
    {
        $get = Yii::$app->request->get();
        $model = new RegisterIp();
        $model->ip = $get['ip'];
        $model->muid = $muid = $get['muid'];
        $model->type = 2;
        $model->save(false);
        $client_id = Yii::$app->debris->config('gdt_client_id1');
        $account = GdtToken::find()->where(['advertiser_id' => $client_id])->asArray()->one();
        $access_token = $account['access_token'];
        $account_id = Yii::$app->debris->config('gdt_account_id1');
        $user_action_set_id = Yii::$app->debris->config('gdt_user_action_set_id1');
        GdtHelper::eqq_user_actions_add($access_token, $account_id, $user_action_set_id, 'ACTIVATE_APP', $muid);
        exit("完成");
    }


    public function actionOauth1()
    {
        // 授权访问 https://developers.e.qq.com/oauth/authorize?client_id=1112003001&redirect_uri=https%3A%2F%2Fg02mx67.com%2Fgdt%2Foauth&state=1
        $get = Yii::$app->request->get();
        $authorization_code = $get['authorization_code'];
        $client_id = Yii::$app->debris->config('gdt_client_id1');
        $client_secret = Yii::$app->debris->config('gdt_client_secret1');
        $redirect_uri = Yii::$app->request->hostInfo . "/gdt/oauth1";
        $result = GdtHelper::eqq_oauth_token($client_id, $client_secret, "authorization_code", $redirect_uri, $authorization_code);
        $model = new GdtToken();
        $model->advertiser_id = $client_id;
        $model->access_token = $result['data']['access_token'];
        $model->refresh_token = $result['data']['refresh_token'];
        $model->access_token_expires_in = $result['data']['access_token_expires_in'];
        $model->refresh_token_expires_in = $result['data']['refresh_token_expires_in'];
        $model->save(false);
        exit("完成");
    }
}
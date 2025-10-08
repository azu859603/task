<?php

namespace frontend\controllers;

use common\helpers\GdtHelper;
use common\models\tea\GdtAdd;
use common\models\tea\GdtAdd1;
use common\models\tea\GdtToken;
use yii\web\Controller;
use Yii;

class Gdt1Controller extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $get = Yii::$app->request->get();
        $model = new GdtAdd();
        $model->muid = $muid = $get['muid'];
        $model->click_time = $get['click_time'];
        $model->app_type = $get['app_type'];
        $model->ip = $get['ip'];
        $model->save(false);
        $client_id = Yii::$app->debris->config('gdt_client_id');
        $account = GdtToken::find()->where(['advertiser_id' => $client_id])->asArray()->one();
        $access_token = $account['access_token'];
        $account_id = Yii::$app->debris->config('gdt_account_id');
        $user_action_set_id = Yii::$app->debris->config('gdt_user_action_set_id');
        GdtHelper::eqq_user_actions_add($access_token,$account_id,$user_action_set_id,'ACTIVATE_APP', $muid);
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
        $model = new GdtAdd1();
        $model->muid = $muid = $get['muid'];
        $model->click_time = $get['click_time'];
        $model->app_type = $get['app_type'];
        $model->ip = $get['ip'];
        $model->save(false);
        $client_id = Yii::$app->debris->config('gdt_client_id1');
        $account = GdtToken::find()->where(['advertiser_id' => $client_id])->asArray()->one();
        $access_token = $account['access_token'];
        $account_id = Yii::$app->debris->config('gdt_account_id1');
        $user_action_set_id = Yii::$app->debris->config('gdt_user_action_set_id1');
        GdtHelper::eqq_user_actions_add($access_token,$account_id,$user_action_set_id,'ACTIVATE_APP', $muid);
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


    public function actionTest(){
        $client_id = Yii::$app->debris->config('gdt_client_id');
        $account = GdtToken::find()->where(['advertiser_id' => $client_id])->asArray()->one();
        $access_token = $account['access_token'];
        $account_id = Yii::$app->debris->config('gdt_account_id');
        $user_action_set_id = Yii::$app->debris->config('gdt_user_action_set_id');
        $muid = Yii::$app->request->get('muid');
        $result = GdtHelper::eqq_user_actions_add($access_token,$account_id,$user_action_set_id,'REGISTER', $muid);
        var_dump($result);
    }
}
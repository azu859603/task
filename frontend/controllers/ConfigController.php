<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;

/**
 * Class ConfigController
 * @package frontend\controllers
 * @author 原创脉冲
 */
class ConfigController extends Controller
{
    public $layout = false; // 关闭布局


    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $privacy_agreement = Yii::$app->debris->config('privacy_agreement');
        echo $privacy_agreement;exit;
    }
}

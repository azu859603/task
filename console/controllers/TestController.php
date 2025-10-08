<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/9
 * Time: 23:30
 */

namespace console\controllers;


use common\helpers\ArrayHelper;
use common\helpers\FileHelper;
use yii\console\Controller;
use Yii;
use yii\helpers\Console;
use yii\helpers\Json;

class TestController extends Controller
{
    public function actionIndex()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        exit('完成');
    }

    protected function getLogPath($type)
    {
        return Yii::getAlias('@runtime') . "/number/" . date('Y_m_d') . '/' . $type . '.txt';
    }
}
<?php

namespace console\controllers;

use common\models\task\Order;
use yii\console\Controller;

class TaskOrderController extends Controller
{

    /**
     * 修改订单状态
     */
    public function actionIndex()
    {
        $result = Order::updateAll(['status' => 4], ['and', ['<', 'created_at', time() - 86400], ['<>', 'status', 2]]);
        var_dump($result);exit;
    }
}
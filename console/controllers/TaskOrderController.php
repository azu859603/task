<?php

namespace console\controllers;

use common\models\task\AiContent;
use common\models\task\Order;
use yii\console\Controller;

class TaskOrderController extends Controller
{

    /**
     * 修改订单状态
     */
    public function actionIndex()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $models = Order::find()
            ->where(['in', 'status', [0, 1, 3]])
            ->andWhere(['<', 'created_at', time() - 86400])
            ->all();
        if (!empty($models)) {
            foreach ($models as $model) {
                $model->status = 4;
                $model->save(false);
                AiContent::updateAll(['status' => 0, 'oid' => 0], ['oid' => $model->id]);
            }
        }
//        $result = Order::updateAll(['status' => 4], ['and', ['<', 'created_at', time() - 86400], ['<>', 'status', 2]]);

        $this->stdout(date('Y-m-d H:i:s') . " ------ 完成" . PHP_EOL);
    }
}
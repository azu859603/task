<?php

namespace backend\modules\common\controllers;

use Yii;
use common\models\base\SearchModel;
use common\enums\StatusEnum;
use common\models\common\PayLog;
use backend\controllers\BaseController;

/**
 * Class PayLogController
 * @package backend\modules\common\controllers
 * @author 原创脉冲
 */
class PayLogController extends BaseController
{
    /**
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => PayLog::class,
            'scenario' => 'default',
            'partialMatchAttributes' => ['order_sn'], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC,
            ],
            'pageSize' => $this->pageSize,
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);
        $category = \yii\helpers\ArrayHelper::map(\common\models\member\RechargeCategory::find()->asArray()->all(), 'id', 'title');
        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'category' => $category,
        ]);
    }

    /**
     * 行为日志详情
     *
     * @param $id
     * @return string
     */
    public function actionView($id)
    {
        return $this->renderAjax($this->action->id, [
            'model' => PayLog::findOne($id),
        ]);
    }
}
<?php

namespace backend\modules\member\controllers;

use Yii;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\member\Notify;
use backend\modules\member\forms\NotifyAnnounceForm;
use backend\controllers\BaseController;
use common\traits\Curd;

/**
 * Class NotifyAnnounceController
 * @package backend\modules\sys\controllers
 * @author 哈哈
 */
class NotifyAnnounceController extends BaseController
{
    use Curd;

    /**
     * @var Notify
     */
    public $modelClass = Notify::class;

    /**
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['title'], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['type' => Notify::TYPE_ANNOUNCE]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return mixed
     */
    public function actionEdit()
    {
        $request = Yii::$app->request;
        $id = $request->get('id', null);
        $model = $this->findModel($id);
        $model->type = Notify::TYPE_ANNOUNCE;
        $model->sender_id = Yii::$app->user->id;
        if ($model->load($request->post()) && $model->save()) {
            return $this->redirect(['index']);
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }
}
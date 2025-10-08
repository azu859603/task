<?php

namespace backend\modules\member\controllers;

use common\models\member\Member;
use Yii;
use common\models\member\MemberLoginLog;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * MemberLoginLog
 *
 * Class MemberLoginLogController
 * @package backend\modules\member\controllers
 */
class MemberLoginLogController extends BaseController
{
    /**
     * @var MemberLoginLog
     */
    public $modelClass = MemberLoginLog::class;

    /**
     * 首页
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => [], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);

        $backend_id = Yii::$app->user->identity->getId();
        if ($backend_id != 1) {
            $a_id = Yii::$app->user->identity->aMember->id;
            $childrenIds = Member::getChildrenIds($a_id);
            $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
        }

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }
}

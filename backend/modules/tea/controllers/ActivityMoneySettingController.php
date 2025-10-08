<?php

namespace backend\modules\tea\controllers;

use common\models\tea\Activity;
use Yii;
use common\models\tea\ActivityMoneySetting;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * ActivityMoneySetting
 *
 * Class ActivityMoneySettingController
 * @package backend\modules\tea\controllers
 */
class ActivityMoneySettingController extends BaseController
{
    use Curd;

    /**
     * @var ActivityMoneySetting
     */
    public $modelClass = ActivityMoneySetting::class;


    /**
     * 首页
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
            $model = ActivityMoneySetting::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('ActivityMoneySetting'));
            $post = ['ActivityMoneySetting' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
//                'relations' => ['manager' => ['username'],'activity'=>['title']],
                'relations' => ['manager' => ['username']],
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $managers = \yii\helpers\ArrayHelper::map(\common\models\backend\Member::find()->select(['id', 'username'])->asArray()->all(), 'username', 'username');
//            $activity_lists = \yii\helpers\ArrayHelper::map(Activity::find()->select(['id','title'])->asArray()->all(),'title','title');
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'managers' => $managers,
//                'activity_lists' => $activity_lists,
            ]);
        }
    }
}

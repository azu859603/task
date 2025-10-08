<?php

namespace backend\modules\tea\controllers;

use Yii;
use common\models\tea\Activity;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
* Activity
*
* Class ActivityController
* @package backend\modules\tea\controllers
*/
class ActivityController extends BaseController
{
    use Curd;

    /**
    * @var Activity
    */
    public $modelClass = Activity::class;


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
            $model = Activity::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Activity'));
            $post = ['Activity' => $posted];
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
                'relations' => ['manager' => ['username']],
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $managers = \yii\helpers\ArrayHelper::map(\common\models\backend\Member::find()->select(['id','username'])->asArray()->all(),'username','username');
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'managers' => $managers,
            ]);
        }
    }
}

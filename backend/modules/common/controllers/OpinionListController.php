<?php

namespace backend\modules\common\controllers;

use Yii;
use common\models\common\OpinionList;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * OpinionList
 *
 * Class OpinionListController
 * @package backend\modules\common\controllers
 */
class OpinionListController extends BaseController
{
    use Curd;

    /**
     * @var OpinionList
     */
    public $modelClass = OpinionList::class;


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
            $model = OpinionList::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('OpinionList'));
            $post = ['OpinionList' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                if ($attribute == 'content') {
                    if (!empty($model->$attribute) && mb_strlen($model->$attribute) > 20) {
                        $output = mb_substr($model->$attribute, 0, 20, 'utf-8') . "..";
                    }
                }
                if ($attribute == 'remark') {
                    if (!empty($model->$attribute) && mb_strlen($model->$attribute) > 4) {
                        $output = mb_substr($model->$attribute, 0, 4, 'utf-8') . "..";
                    }
                }
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            };
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'relations' => ['member' => ['mobile']],
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);

            $dataProvider->query->with('member');

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }
}

<?php

namespace backend\modules\common\controllers;

use Yii;
use common\models\common\Languages;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * Languages
 *
 * Class LanguagesController
 * @package backend\modules\common\controllers
 */
class LanguagesController extends BaseController
{
    use Curd;

    /**
     * @var Languages
     */
    public $modelClass = Languages::class;


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
            $model = Languages::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Languages'));
            $post = ['Languages' => $posted];
            if ($model->load($post) && $model->save(false)) {
                if ($attribute == 'status') {
                    $output = ['1' => '启用', '0' => '禁用'][$model->status];
                } else {
                    $output = $model->$attribute;
                }
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'sort' => SORT_ASC,
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }


    /**
     * ajax编辑/创建
     * @return mixed
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                if ($model->is_default == 1) {
                    Languages::updateAll(['is_default' => 0], ['<>', 'id', $model->id]);
                }
                return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
            } else {
                return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}

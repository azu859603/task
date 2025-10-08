<?php

namespace backend\modules\tea\controllers;

use backend\modules\tea\forms\AddFkForm;
use common\models\tea\ActivityCardSetting;
use Yii;
use common\models\tea\ActivityCardList;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * ActivityCardList
 *
 * Class ActivityCardListController
 * @package backend\modules\tea\controllers
 */
class ActivityCardListController extends BaseController
{
    use Curd;

    /**
     * @var ActivityCardList
     */
    public $modelClass = ActivityCardList::class;


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
            $model = ActivityCardList::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('ActivityCardList'));
            $post = ['ActivityCardList' => $posted];
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
                'relations' => ['member' => ['mobile'], 'card' => ['title']],
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $card_lists = \yii\helpers\ArrayHelper::map(ActivityCardSetting::find()->select(['id', 'title'])->asArray()->all(), 'title', 'title');
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'card_lists' => $card_lists,
            ]);
        }
    }

    /**
     * ajax编辑/创建
     * @return mixed
     */
    public function actionAjaxEdit()
    {
        $model = new AddFkForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $card_list = \yii\helpers\ArrayHelper::map(ActivityCardSetting::find()->select(['id', 'title'])->where(['type' => 1])->asArray()->all(), 'id', 'title');
            $add_model = [];
            $time = time();
            $k = 0;
            for ($i = 1; $i <= 5; $i++) {
                $name = "p" . $i . "_number";
                if ($model->$name > 0) {
                    for ($j = 0; $j < $model->$name; $j++) {
                        $add_model[$k]['pid'] = $i;
                        $add_model[$k]['member_id'] = $model->member_id;
                        $add_model[$k]['remark'] = '【我的购买】进行了一轮购买，获得了一张' . $card_list[$i];
                        $add_model[$k]['type'] = 1;
                        $add_model[$k]['status'] = 0;
                        $add_model[$k]['created_at'] = $time;
                        $k++;
                    }
                }
            }
            $field = ['pid','member_id','remark','type','status','created_at'];
            Yii::$app->db->createCommand()->batchInsert(ActivityCardList::tableName(), $field, $add_model)->execute();

            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}

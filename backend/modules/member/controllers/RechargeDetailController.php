<?php

namespace backend\modules\member\controllers;

use Yii;
use common\models\member\RechargeDetail;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * RechargeDetail
 *
 * Class RechargeDetailController
 * @package backend\modules\member\controllers
 */
class RechargeDetailController extends BaseController
{
    use Curd;

    /**
     * @var RechargeDetail
     */
    public $modelClass = RechargeDetail::class;


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
            $model = RechargeDetail::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('RechargeDetail'));
            $post = ['RechargeDetail' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                isset($posted['status']) && $output = ['1' => '启用', '0' => '禁用'][$model->status];
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
                    'id' => SORT_DESC,
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $category = \yii\helpers\ArrayHelper::map(\common\models\member\RechargeCategory::find()->asArray()->all(), 'id', 'title');
            $category[10000] = "USDT-TRC20";
            $category[10001] ="线下充值-银行卡";
            $category[10002] ="线下充值-支付宝";
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'category' => $category,
            ]);
        }
    }
}

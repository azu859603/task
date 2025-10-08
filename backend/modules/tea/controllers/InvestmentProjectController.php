<?php

namespace backend\modules\tea\controllers;

use common\enums\StatusEnum;
use common\helpers\BcHelper;
use Yii;
use common\models\tea\InvestmentProject;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * InvestmentProject
 *
 * Class InvestmentProjectController
 * @package backend\modules\tea\controllers
 */
class InvestmentProjectController extends BaseController
{
    use Curd;

    /**
     * @var InvestmentProject
     */
    public $modelClass = InvestmentProject::class;


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
            $model = InvestmentProject::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('InvestmentProject'));
            $post = ['InvestmentProject' => $posted];
            if ($model->load($post)) {
                if (isset($posted['schedule'])) {
                    // 计算可投金额
                    $model->can_investment_amount = $model->all_investment_amount - BcHelper::mul($model->all_investment_amount, BcHelper::div($model->schedule, 100, 4), 0);
                    if ($model->schedule >= 100) {
                        $model->status = 0;
                    } else {
                        $model->status = 1;
                    }
                }
                $model->save(false);
                $output = $model->$attribute;
                isset($posted['status']) && $output = ['1' => '购买中', '0' => '购买已满'][$model->status];
                isset($posted['project_status']) && $output = ['1' => '启用', '0' => '禁用'][$model->project_status];
                isset($posted['home_show_switch']) && $output = ['1' => '展示', '0' => '隐藏'][$model->home_show_switch];
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => ['title'], // 模糊查询
                'defaultOrder' => [
                    'sort' => SORT_ASC,
                    'created_at' => SORT_DESC,
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query->with(['categoryList']);
//            $category_list = \yii\helpers\ArrayHelper::map(\common\models\tea\CategoryList::find()->asArray()->all(), 'id', 'title');
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
//                'category_list' => $category_list,
            ]);
        }
    }


    /**
     * 编辑/创建
     *
     * @return mixed
     */
    public function actionEdit()
    {
        $id = Yii::$app->request->get('id', null);
        $model = $this->findModel($id);
        $model->all_investment_amount = BcHelper::div($model->all_investment_amount, 10000, 0);
        if ($model->load(Yii::$app->request->post())) {
            if(!empty($model->project_superior_rebate_time)){
                $model->project_superior_rebate_time = date("Y-m-d H:i:s",strtotime($model->project_superior_rebate_time));
            }
            if(!empty($model->gift_amount_time)){
                $model->gift_amount_time = date("Y-m-d H:i:s",strtotime($model->gift_amount_time));
            }
            if (empty($model->project_img)) {
                return $this->message("产品图片必须上传", $this->redirect(['index']), 'error');
            }
            // 计算产品总额

            $model->all_investment_amount = BcHelper::mul($model->all_investment_amount, 10000, 0);
            // 计算可投金额
            $model->can_investment_amount = $model->all_investment_amount - BcHelper::mul($model->all_investment_amount, BcHelper::div($model->schedule, 100, 4), 0);
            if ($model->schedule >= 100) {
                $model->status = 0;
            } else {
                $model->status = 1;
            }

            $model->save();
            return $this->message("操作成功", $this->redirect(['index']));
        }

        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }
}

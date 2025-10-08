<?php


namespace api\modules\v1\controllers\tea;


use api\controllers\OnAuthController;
use common\helpers\BcHelper;
use common\helpers\DateHelper;
use common\helpers\ResultHelper;
use common\models\tea\Detail;
use common\models\tea\Project;
use yii\data\ActiveDataProvider;
use Yii;

class ProjectController extends OnAuthController
{
    public $modelClass = Project::class;


    protected $authOptional = ['index'];


    /**
     * 项目列表
     * @return array|ActiveDataProvider|\yii\db\ActiveRecord
     */
    public function actionIndex()
    {
        $models = Project::find()
            ->where(['status' => 1])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->asArray()
            ->all();
        foreach ($models as $k => $model) {
            $money = BcHelper::mul($model['least_amount'], BcHelper::div($model['income'], 100, 4));
            $models[$k]['money'] = $money;
            $models[$k]['all_money'] = BcHelper::mul($model['deadline'], $money);
        }
        return $models;
    }


    /**
     * 项目详情
     */
    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID必须填写！");
        }
        $model = Project::find()
            ->where(['id' => $id])
            ->with(['bill' => function ($query) {
                $query->orderBy(['created_at' => SORT_DESC])
                    ->with(['detail' => function ($query) {
                        $query->select([
                            'id',
                            'b_id',
                            'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d\') as created_at',
                        ])->where(['status' => 1]);
                    }])->where(['member_id' => $this->memberId])->andWhere(['<', 'status', 3]);
            }])
            ->asArray()
            ->one();
        if (empty($model['bill'])) {
            $count = 0;
            $principal = 0;
            $income_amount_all = 0;
        } else {
            $count = $model['deadline'] - $model['bill']['settlement_times'];
            if ($model['bill']['settlement_times']==$model['deadline']){
                $principal = $model['bill']['investment_amount'];
            }else{
                $principal = BcHelper::mul(BcHelper::div($model['bill']['investment_amount'], $model['deadline']), $model['bill']['settlement_times']);
            }
            $income_amount_all = $model['bill']['income_amount_all'];
        }

        $model['count'] = $count;
        $model['principal'] = $principal;
        $model['income_amount_all'] = $income_amount_all;
        $today = DateHelper::today();
        $detail = Detail::find()->where(['project_id' => $id, 'member_id' => $this->memberId])->andWhere(['between', 'created_at', $today['start'], $today['end']])->asArray()->one();
        if (empty($detail)) {
            $model['project_status'] = -1;
        } else {
            $model['project_status'] = $detail['status'];
        }

        return $model;
    }


    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['create', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
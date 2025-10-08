<?php

namespace api\modules\v1\controllers\dj;

use api\controllers\OnAuthController;
use common\helpers\ArrayHelper;
use common\helpers\BcHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\dj\PromotionDetail;
use common\models\dj\PromotionOrder;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use yii\data\ActiveDataProvider;

class PromotionController extends OnAuthController
{
    public $modelClass = PromotionOrder::class;

    /**
     * 订单列表
     */
    public function actionIndex()
    {
        $search = Yii::$app->request->get('search');
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['member_id' => $this->memberId])
                ->andFilterWhere(['like', 'title', $search])
                ->orderBy('id desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 订单列表
     */
    public function actionList()
    {
        $model = new ActiveDataProvider([
            'query' => PromotionDetail::find()
                ->select([
                    'sum(`clicks`) as clicks',
                    'sum(`quantity`) as quantity',
                    'created_at'
                ])
                ->where(['member_id' => $this->memberId])
                ->groupBy(['created_at'])
                ->orderBy('created_at desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
        $model = $model->getModels();
        $model = ArrayHelper::arraySort($model,'created_at');

        $model = array_values($model);
        return $model;
    }

    public function actionDetail()
    {
        $all_clicks = PromotionDetail::find()->where(['member_id' => $this->memberId])->sum('clicks') ?? 0;
        $all_quantity = PromotionDetail::find()->where(['member_id' => $this->memberId])->sum('quantity') ?? 0;
        $doing_money = PromotionDetail::find()->where(['member_id' => $this->memberId])->sum('doing_money') ?? 0;
        $over_money = PromotionDetail::find()->where(['member_id' => $this->memberId])->sum('over_money') ?? 0;
        return ['all_clicks' => $all_clicks, 'all_quantity' => $all_quantity,'doing_money' => $doing_money,'over_money' => $over_money];

    }

    /**
     * 创建订单
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $model = new PromotionOrder();
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                'member' => Member::findOne($this->memberId),
                'num' => $model->money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => '【推广】扣除余额',
                'pay_type' => CreditsLog::PROMOTION_TYPE,
            ]));
            $promotion_number = Yii::$app->debris->backendConfig('promotion_number')??"10-20";
            $promotion_number = explode("-", $promotion_number);
            $promotion_number = rand($promotion_number[0],$promotion_number[1]);
            $promotion_number = BcHelper::mul($model->money,$promotion_number,0);
            $model->all_number = $promotion_number;
            $model->number = $promotion_number;
            $model->save(false);
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "提交成功");
        } else {
            $error = array_values($model->errors) ? array_values($model->errors) : [['系统繁忙,请稍后再试']];
            return ResultHelper::json(ResultHelper::ERROR_CODE, $error[0][0]);
        }
    }


    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
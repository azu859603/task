<?php

namespace backend\modules\dj\controllers;

use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\common\Statistics;
use common\models\dj\PromotionDetail;
use common\models\dj\PromotionOrder;
use common\models\dj\SellerAvailableList;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use common\models\dj\Orders;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * Orders
 *
 * Class OrdersController
 * @package backend\modules\dj\controllers
 */
class OrdersController extends BaseController
{
    use Curd;

    /**
     * @var Orders
     */
    public $modelClass = Orders::class;


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
            $model = Orders::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Orders'));
            $post = ['Orders' => $posted];
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
                'partialMatchAttributes' => [], // 模糊查询
//                'relations' => ['member' => ['mobile'], 'seller' => ['mobile']],
                'relations' => ['seller' => ['mobile']],
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);

//            $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
            $lang = Yii::$app->request->get('lang', $default_lang);

            $dataProvider->query
                ->with([
                    'shortPlaysList' => function ($query) use ($lang) {
                        $query->with([
                            'translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            },
                        ]);
                    },
                ]);

            $backend_id = Yii::$app->user->identity->getId();
            if ($backend_id != 1) {
                $a_id = Yii::$app->user->identity->aMember->id;
                $childrenIds = Member::getChildrenIds($a_id);
                $dataProvider->query->andFilterWhere(['in', 'seller_id', $childrenIds]);
            }

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
            return $model->save()
                ? $this->message("操作成功", $this->redirect(Yii::$app->request->referrer))
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    public function actionReturnGoods($id)
    {
        $return_goods_time = Yii::$app->debris->backendConfig('return_goods_time');
        $order = Orders::find()
            ->where(['id' => $id, 'status' => 0])
            ->andWhere(['<', 'created_at', time() - $return_goods_time * 86400])
            ->one();
        if (empty($order)) {
            return $this->message("该订单尚未到达退货时间或已失效", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $order->status = 2;
        $order->save();
        // 退货增加买家余额
        Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
            'member' => Member::findOne($order->member_id),
            'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
            'num' => $order->money,
            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
            'remark' => "【短剧】买家退货",
        ]));
        // 卖家扣除信用分
        $seller_member = Member::findOne($order->seller_id);
        $seller_member->credit_score = $seller_member->credit_score - 1;
        $seller_member->save();
        return $this->message("退货成功", $this->redirect(Yii::$app->request->referrer));
    }


    /**
     * 一键完成
     * @param $mobile
     * @return mixed|string
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionOverOrder($mobile)
    {
        $member = Member::find()->where(['mobile' => $mobile])->with(['account', 'sellerLevel'])->one();
        if (empty($member)) {
            return $this->message("操作失败，卖家账号错误", $this->redirect(['index']), 'error');
        }

        $orders = Orders::find()
            ->where(['income_status' => 0, 'status' => 1, 'seller_id' => $member->id])
            ->andWhere(['<', 'created_at', time()])
            ->all();
        if (empty($orders)) {
            return $this->message("操作失败，卖家暂无可完成的订单", $this->redirect(['index']), 'error');
        }
        foreach ($orders as $order) {
            $order->income_status = 1;
            $order->over_time = time();
            $order->save();

            // 卖家获得本金和利润
            Yii::$app->services->memberCreditsLog->incrCanWithdrawMoney(new CreditsLogForm([
                'member' => $member,
                'pay_type' => CreditsLog::FB_TYPE,
                'num' => $order->dx_money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【收益】退还代销费",
            ]));
            if ($order->income > 0) {
                Yii::$app->services->memberCreditsLog->incrCanWithdrawMoney(new CreditsLogForm([
                    'member' => $member,
                    'pay_type' => CreditsLog::INCOME_TYPE,
                    'num' => $order->income,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【收益】销售短剧获取收益",
                ]));
                // 如果是推流订单，则扣除推流费用
                if ($order->push_flow_switch == 1) {
                    $push_flow = $member->sellerLevel->push_flow;
                    $push_flow_money = BcHelper::mul($order->income, BcHelper::div($push_flow, 100, 4));
                    if ($push_flow_money > 0) {
                        Yii::$app->services->memberCreditsLog->decrCanWithdrawMoney(new CreditsLogForm([
                            'member' => $member,
                            'pay_type' => CreditsLog::PROMOTION_TYPE,
                            'num' => $push_flow_money,
                            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                            'remark' => "【推流】扣除手续费",
                        ]));
                    }
                    $promotion = PromotionOrder::find()->where(['member_id' => $member->id, 'status' => 1])->one();
                    if (!empty($promotion)) {
                        $date = date("Y-m-d");
                        // 添加详情
                        $promotion_detail = PromotionDetail::find()->where(['pid' => $promotion->id, 'created_at' => $date])->one();
                        if (empty($promotion_detail)) {
                            $promotion_detail = new PromotionDetail();
                            $promotion_detail->member_id = $promotion->member_id;
                            $promotion_detail->pid = $promotion->id;
                            $promotion_detail->created_at = $date;
                        }
                        // 收益增减
                        $promotion_detail->doing_money = BcHelper::sub($promotion_detail->doing_money, $order->income);
                        $promotion_detail->over_money = BcHelper::add($promotion_detail->over_money, $order->income);
                        $promotion_detail->save(false);
                    }
                }

                // 增加总收益
                $member->account->investment_income = BcHelper::add($member->account->investment_income, $order->income);
                $member->account->save(false);
                // 合约利润和非合约利润
                $seller_id = $order->seller_id;
                $seller_order = Orders::find()->where(['id' => $order->id])
                    ->with([
                        'shortPlaysList' => function ($query) use ($seller_id) {
                            $query->with([
                                'sellerAvailableList' => function ($query) use ($seller_id) {
                                    $query->where(['member_id' => $seller_id]);
                                }
                            ]);
                        }
                    ])
                    ->asArray()
                    ->one();

                // 若是已上架
                if (!empty($seller_order['shortPlaysList']['sellerAvailableList'])) {
                    $member->account->contract_profit = $order->income;
                } else {
                    $member->account->non_contractual_profit = $order->income;
                }
                $member->account->save(false);


                // 加入统计表 获取最上级用户ID 并且不是系统卖家
                if ($member['type'] == 1 && $member['id'] != 1) {
                    $first_member = Member::getParentsFirst($member);
                    $b_id = $first_member['b_id'] ?? 0;
                    Statistics::updateIncomeMoney(date("Y-m-d"), $order->income, $b_id);
                    // 发放佣金
                    if (!empty($member['pid'])) {
                        $commission_one = BcHelper::mul($order->income, BcHelper::div(Yii::$app->debris->backendConfig('commission_one'), 100, 4));
                        $member_one = Member::findOne($member['pid']);
                        if ($commission_one > 0) {
                            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                'member' => $member_one,
                                'pay_type' => CreditsLog::COMMISSION_TYPE,
                                'num' => $commission_one,
                                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                'remark' => "【返佣】获得一级返佣",
                            ]));
                            Statistics::updateCommissionMoney(date("Y-m-d"), $commission_one, $b_id);
                            if (!empty($member_one['pid'])) {
                                $commission_two = BcHelper::mul($order->income, BcHelper::div(Yii::$app->debris->backendConfig('commission_two'), 100, 4));
                                $member_two = Member::findOne($member_one['pid']);
                                if ($commission_two > 0) {
                                    Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                        'member' => $member_two,
                                        'pay_type' => CreditsLog::COMMISSION_TYPE,
                                        'num' => $commission_two,
                                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                        'remark' => "【返佣】获得二级返佣",
                                    ]));
                                    Statistics::updateCommissionMoney(date("Y-m-d"), $commission_two, $b_id);
                                    if (!empty($member_two['pid'])) {
                                        $commission_three = BcHelper::mul($order->income, BcHelper::div(Yii::$app->debris->backendConfig('commission_three'), 100, 4));
                                        $member_three = Member::findOne($member_two['pid']);
                                        if ($member_three > 0) {
                                            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                                'member' => $member_three,
                                                'pay_type' => CreditsLog::COMMISSION_TYPE,
                                                'num' => $commission_three,
                                                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                                'remark' => "【返佣】获得三级返佣",
                                            ]));
                                            Statistics::updateCommissionMoney(date("Y-m-d"), $commission_three, $b_id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $this->message("处理成功", $this->redirect(['index']));
    }
}

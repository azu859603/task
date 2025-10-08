<?php

namespace backend\modules\dj\controllers;

use backend\modules\dj\forms\AddOrderForm;
use common\helpers\BcHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\common\Statistics;
use common\models\dj\Orders;
use common\models\dj\PromotionDetail;
use common\models\dj\PromotionOrder;
use common\models\dj\SellerAvailableOrder;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use common\models\dj\SellerAvailableList;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\db\Expression;
use yii\web\UnprocessableEntityHttpException;

/**
 * SellerAvailableList
 *
 * Class SellerAvailableListController
 * @package backend\modules\dj\controllers
 */
class SellerAvailableListController extends BaseController
{
    use Curd;

    /**
     * @var SellerAvailableList
     */
    public $modelClass = SellerAvailableList::class;


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
            $model = SellerAvailableList::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('SellerAvailableList'));
            $post = ['SellerAvailableList' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $keyword = Yii::$app->request->get('keyword');
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'relations' => ['member' => ['mobile']],
                'defaultOrder' => [
                    'status'=>SORT_DESC,
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
                ->joinWith([
                    'shortPlaysList' => function ($query) use ($lang, $keyword) {
                        $query->joinWith([
                            'translation' => function ($query) use ($lang, $keyword) {
                                $query->where(['lang' => $lang]);
                                if ($keyword) {
                                    $query->andFilterWhere(['like', 'title', $keyword]);
                                }
                            },
                        ]);
                    },
                ]);

            $backend_id = Yii::$app->user->identity->getId();
            if ($backend_id != 1) {
                $a_id = Yii::$app->user->identity->aMember->id;
                $childrenIds = Member::getChildrenIds($a_id);
                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
            }

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'keyword' => $keyword,
            ]);
        }
    }

//    public function actionAddOrder()
//    {
//        $mobile = Yii::$app->request->get('mobile');
//        if (empty($mobile)) {
//            return $this->message('请输入卖家进行操作', $this->redirect(Yii::$app->request->referrer), 'error');
//        }
//        $ids = Yii::$app->request->get('ids');
//        if (empty($ids)) {
//            return $this->message('请选择数据进行操作', $this->redirect(Yii::$app->request->referrer), 'error');
//        }
//
//        $model = new AddOrderForm();
//        if ($model->load(Yii::$app->request->post())) {
//            $ids = explode(',', $ids);
//            foreach ($ids as $id) {
//                // 开启事务
//                $transaction = Yii::$app->db->beginTransaction();
//                try {
//                    $sellerAvailableList = SellerAvailableList::find()->where(['id' => $id])->with(['shortPlaysList'])->one();
//                    $order = new Orders();
//                    $order->seller_id = $sellerAvailableList->member_id;
//                    $order->pid = $id;
//                    $order->money = $sellerAvailableList->amount;
//                    $order->income = BcHelper::sub($sellerAvailableList->shortPlaysList->amount, $sellerAvailableList->amount);
//                    $order->private_key = md5($id . time());
//                    // 处理买家
//                    $order->member_id = $model->member_id;
//                    $time = strtotime($model->created_at);
//                    $order->created_at = $time;
//                    // 扣除买家余额
//                    Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
//                        'member' => Member::findOne($model->member_id),
//                        'time' => $time,
//                        'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
//                        'num' => $sellerAvailableList->shortPlaysList->amount,
//                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//                        'remark' => "【短剧】购买短剧扣除余额",
//                    ]));
//                    $order->save();
//                    // 新订单站内信
//                    Yii::$app->services->memberNotify->createMessage("新的订单", "您有新的订单，请前往订单列表查看", 1, [$order->seller_id], $time);
//                    $transaction->commit();
//                } catch (\Exception $e) {
//                    $transaction->rollBack();
//                    continue;
//                }
//            }
//            return $this->message("操作成功", $this->redirect(['index']));
//        }
//
//        return $this->render($this->action->id, [
//            'model' => $model,
//            'mobile' => $mobile,
//        ]);
//
//
//    }

    public function actionAddOrder($ids, $mobile)
    {
        $seller_id = Member::find()->where(['mobile' => $mobile])->select(['id'])->asArray()->one()['id'];
        $model = new AddOrderForm();
        $model->scenario = 'add_two';
        $ids = explode(',', $ids);

        $new_ids = SellerAvailableList::find()
            ->select(['pid'])
            ->where(['in', 'id', $ids])
            ->column();
        $virtual_count = Member::find()->where(['pid' => $seller_id, 'is_virtual' => 1, 'type' => 0])->count();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->start_time > $model->stop_time) {
                return $this->message("开始时间不能大于停止时间", $this->redirect(['index']), 'error');
            }
            $as = $new_ids;
//            $bs = $model->member_id;

            $bs = Member::find()->where(['pid' => $seller_id, 'is_virtual' => 1, 'type' => 0])->select(['id'])->orderBy(new Expression('rand()'))->limit($model->member_id)->column();
            if (empty($bs)) {
                return $this->message("操作失败，买家数量不足", $this->redirect(['index']), 'error');
            }

            $results = [];
            $bsCount = count($bs);
            foreach ($as as $i => $key) {
                $results[$key] = $bs[$i % $bsCount];
            }

//            $sellerMember = Member::find()->where(['id' => $seller_id])->with(['sellerLevel'])->one();

            foreach ($results as $pid => $member_id) {
                // 开启事务
                $transaction = Yii::$app->db->beginTransaction();
                try {


                    $shortPlaysList = ShortPlaysList::find()
                        ->where(['id' => $pid])
                        ->with([
                            'sellerAvailableList' => function ($query) use ($seller_id) {
                                $query->where(['member_id' => $seller_id]);
                            }
                        ])
                        ->one();


                    // 判断买家余额是否充足，不够的话重新找个买家
//                    $member = Member::find()->where(['id' => $member_id])
//                        ->with(['account'])
//                        ->one();
                    // 如果购买人的上级卖家未上架短剧
                    if (!empty($shortPlaysList['sellerAvailableList']) && $shortPlaysList['sellerAvailableList']['status'] == 1) {
//                        $income = BcHelper::mul(BcHelper::div($sellerMember->sellerLevel->profit, 100, 4), $shortPlaysList->amount);
//                        $dx_money = BcHelper::sub($shortPlaysList->amount, $income);

                        $sellerAvailableOrder = SellerAvailableOrder::find()
                            ->where(['member_id' => $seller_id, 'pid' => $pid])
                            ->andWhere(['>', 'buy_number', 0])
                            ->orderBy(['created_at' => SORT_ASC])
                            ->one();
                        $dx_money = BcHelper::div($sellerAvailableOrder['buy_money'],$sellerAvailableOrder['buy_number']);
                        $income = BcHelper::sub($shortPlaysList->amount, $dx_money);

                    } else {
                        throw new UnprocessableEntityHttpException('预售短剧数量已用完');
                    }
                    $order = new Orders();
                    $order->seller_id = $seller_id;
                    $order->pid = $shortPlaysList->id;
                    $order->money = $shortPlaysList->amount;
                    $order->dx_money = $dx_money;
                    $order->income = $income;
                    $order->private_key = md5($pid . time());
                    // 处理买家
                    $order->member_id = $member_id;
                    $time = rand(time() + ($model->start_time * 3600), time() + ($model->stop_time * 3600));
                    $order->created_at = $time;

                    $shortPlaysList->buy_number += 1;
                    $shortPlaysList->save();
                    // 新订单站内信
//                    Yii::$app->services->memberNotify->createMessage("新的订单", "您有新的订单，请前往订单列表查看", 1, [$order->seller_id], $time);


                    // 扣除卖家预售数量和金额
                    $sellerAvailableOrder->buy_number -= 1;
                    $sellerAvailableOrder->buy_money = BcHelper::sub($sellerAvailableOrder->buy_money, $dx_money);
                    $sellerAvailableOrder->save();
                    // 判断短剧是否该下架
                    if (SellerAvailableOrder::find()->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])->sum('buy_number') == 0) {
                        $sellerAvailableList = SellerAvailableList::find()->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])->one();
                        if (!empty($sellerAvailableList) && $order['seller_id'] != 1) {
                            $sellerAvailableList->status = 0;
                            $sellerAvailableList->save(false);
                        }
                    }


                    // 卖家发货
                    $seller_member = Member::find()->where(['id' => $order['seller_id']])->with(['sellerLevel', 'account'])->one();
                    $order->status = 1;
                    $time = time();
                    $order->updated_at = $time;
                    $order->over_time = BcHelper::add(BcHelper::mul(86400, $seller_member->sellerLevel->return_income_time, 0), $time, 0);

                    // 发货成功，发送站内信
                    Yii::$app->services->memberNotify->createMessage("发货通知", "您购买的短剧订单已发货，短剧密钥为：" . $order->private_key, 1, [$order->member_id], time());
                    // 统计订单总数和销售额
                    $seller_member->account->investment_all_money = BcHelper::add($seller_member->account->investment_all_money, $order->money);
                    $seller_member->account->investment_number = $seller_member->account->investment_number + 1;
                    $seller_member->account->save(false);
                    // 用户升级 更新等级
                    Yii::$app->services->memberLevel->updateSellerLevel($seller_member);
                    // 统计销售额
                    // 加入统计表 获取最上级用户ID
                    if ($seller_member['type'] == 1) {
                        $first_member = Member::getParentsFirst($seller_member);
                        $b_id = $first_member['b_id'] ?? 0;
                        Statistics::updateBuyMoney(date("Y-m-d"), $order->money, $b_id);
                    }

                    // 判断卖家是否开启推流
                    if ($seller_member->push_flow_switch == 1) {
                        $promotion = PromotionOrder::find()->where(['member_id' => $seller_member->id, 'status' => 1])->one();
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
                            $promotion_detail->quantity += 1;
                            // 未结算收益增加
                            $promotion_detail->doing_money = BcHelper::add($promotion_detail->doing_money, $order->income);
                            $order->push_flow_switch = 1;
                            $promotion_detail->save(false);
                        }
                    }
                    $order->save();

                    $transaction->commit();
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->message("操作中止，" . $e->getMessage(), $this->redirect(['index']), 'error');
                }
            }
            return $this->message("操作成功", $this->redirect(['index']));
        }
        return $this->render($this->action->id, [
            'model' => $model,
            'seller_id' => $seller_id,
            'ids' => $new_ids,
            'virtual_count' => $virtual_count,
        ]);


    }

    public function actionAdd($id, $seller_id)
    {
        $model = new AddOrderForm();
        $model->scenario = 'add_two';
        $pid = SellerAvailableList::find()
            ->select(['pid'])
            ->where(['id' => $id])
            ->asArray()
            ->one()['pid'];
        $virtual_count = Member::find()->where(['pid' => $seller_id, 'is_virtual' => 1, 'type' => 0])->count();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->start_time > $model->stop_time) {
                return $this->message("开始时间不能大于停止时间", $this->redirect(['index']), 'error');
            }


//            $sellerMember = Member::find()->where(['id' => $seller_id])->with(['sellerLevel'])->one();

            $bs = Member::find()->where(['pid' => $seller_id, 'is_virtual' => 1, 'type' => 0])->select(['id'])->orderBy(new Expression('rand()'))->limit($model->member_id)->column();
            if (empty($bs)) {
                return $this->message("操作失败，买家数量不足", $this->redirect(['index']), 'error');
            }
            if ($model->member_id > count($bs)) {
                return $this->message("操作失败，买家数量不足!", $this->redirect(['index']), 'error');
            }

            foreach ($bs as $member_id) {
                // 开启事务
                $transaction = Yii::$app->db->beginTransaction();
                try {
                    $shortPlaysList = ShortPlaysList::find()
                        ->where(['id' => $pid])
                        ->with([
                            'sellerAvailableList' => function ($query) use ($seller_id) {
                                $query->where(['member_id' => $seller_id]);
                            }
                        ])
                        ->one();

                    // 判断买家余额是否充足，不够的话重新找个买家
//                    $member = Member::find()->where(['id' => $member_id])
//                        ->with(['account'])
//                        ->one();
                    // 如果购买人的上级卖家未上架短剧
                    if (!empty($shortPlaysList['sellerAvailableList']) && $shortPlaysList['sellerAvailableList']['status'] == 1) {
//                        $income = BcHelper::mul(BcHelper::div($sellerMember->sellerLevel->profit, 100, 4), $shortPlaysList->amount);
//                        $dx_money = BcHelper::sub($shortPlaysList->amount, $income);

                        $sellerAvailableOrder = SellerAvailableOrder::find()
                            ->where(['member_id' => $seller_id, 'pid' => $pid])
                            ->andWhere(['>', 'buy_number', 0])
                            ->orderBy(['created_at' => SORT_ASC])
                            ->one();
                        $dx_money = BcHelper::div($sellerAvailableOrder['buy_money'],$sellerAvailableOrder['buy_number']);
                        $income = BcHelper::sub($shortPlaysList->amount, $dx_money);

                    } else {
                        throw new UnprocessableEntityHttpException('预售短剧数量已用完');
                    }

                    $order = new Orders();
                    $order->seller_id = $seller_id;
                    $order->pid = $shortPlaysList->id;
                    $order->money = $shortPlaysList->amount;
                    $order->dx_money = $dx_money;
                    $order->income = $income;
                    $order->private_key = md5($pid . time());
                    // 处理买家
                    $order->member_id = $member_id;
                    $time = rand(time() + ($model->start_time * 3600), time() + ($model->stop_time * 3600));
                    $order->created_at = $time;

                    $shortPlaysList->buy_number += 1;
                    $shortPlaysList->save();
                    // 新订单站内信
//                    Yii::$app->services->memberNotify->createMessage("新的订单", "您有新的订单，请前往订单列表查看", 1, [$order->seller_id], $time);

                    // 扣除卖家预售数量和金额
//                    $sellerAvailableOrder = SellerAvailableOrder::find()
//                        ->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])
//                        ->andWhere(['>', 'buy_number', 0])
//                        ->orderBy(['created_at' => SORT_ASC])
//                        ->one();
                    $sellerAvailableOrder->buy_number -= 1;
                    $sellerAvailableOrder->buy_money = BcHelper::sub($sellerAvailableOrder->buy_money, $dx_money);
                    $sellerAvailableOrder->save();
                    // 判断短剧是否该下架
                    if (SellerAvailableOrder::find()->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])->sum('buy_number') == 0) {
                        $sellerAvailableList = SellerAvailableList::find()->where(['member_id' => $order['seller_id'], 'pid' => $order['pid']])->one();
                        if (!empty($sellerAvailableList) && $order['seller_id'] != 1) {
                            $sellerAvailableList->status = 0;
                            $sellerAvailableList->save(false);
                        }
                    }


                    // 卖家发货
                    $seller_member = Member::find()->where(['id' => $order['seller_id']])->with(['sellerLevel', 'account'])->one();
                    $order->status = 1;
                    $time = time();
                    $order->updated_at = $time;
                    $order->over_time = BcHelper::add(BcHelper::mul(86400, $seller_member->sellerLevel->return_income_time, 0), $time, 0);

                    // 发货成功，发送站内信
                    Yii::$app->services->memberNotify->createMessage("发货通知", "您购买的短剧订单已发货，短剧密钥为：" . $order->private_key, 1, [$order->member_id], time());
                    // 统计订单总数和销售额
                    $seller_member->account->investment_all_money = BcHelper::add($seller_member->account->investment_all_money, $order->money);
                    $seller_member->account->investment_number = $seller_member->account->investment_number + 1;
                    $seller_member->account->save(false);
                    // 用户升级 更新等级
                    Yii::$app->services->memberLevel->updateSellerLevel($seller_member);
                    // 统计销售额
                    // 加入统计表 获取最上级用户ID
                    if ($seller_member['type'] == 1) {
                        $first_member = Member::getParentsFirst($seller_member);
                        $b_id = $first_member['b_id'] ?? 0;
                        Statistics::updateBuyMoney(date("Y-m-d"), $order->money, $b_id);
                    }
                    // 判断卖家是否开启推流
                    if ($seller_member->push_flow_switch == 1) {
                        $promotion = PromotionOrder::find()->where(['member_id' => $seller_member->id, 'status' => 1])->one();
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
                            $promotion_detail->quantity += 1;
                            // 未结算收益增加
                            $promotion_detail->doing_money = BcHelper::add($promotion_detail->doing_money, $order->income);
                            $order->push_flow_switch = 1;
                            $promotion_detail->save(false);
                        }
                    }
                    $order->save();

                    $transaction->commit();
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    return $this->message("操作中止，" . $e->getMessage(), $this->redirect(['index']), 'error');
                }
            }
            return $this->message("操作成功", $this->redirect(['index']));
        }
        return $this->render($this->action->id, [
            'model' => $model,
            'seller_id' => $seller_id,
            'id' => $pid,
            'virtual_count' => $virtual_count,
        ]);


    }

    public function actionCheck($id, $status)
    {
        $model = SellerAvailableList::find()->where(['id' => $id])->one();
        if (empty($model)) {
            return $this->message("信息错误！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status = $status;
        $model->save(false);
        return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
    }
}

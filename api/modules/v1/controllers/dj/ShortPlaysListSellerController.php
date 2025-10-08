<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:44
 */

namespace api\modules\v1\controllers\dj;


use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\dj\BuyLevelList;
use common\models\dj\SellerAvailableList;
use common\models\dj\SellerAvailableOrder;
use common\models\dj\SellerLevel;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\MemberCard;
use common\models\tea\InvestmentBill;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\Json;

class ShortPlaysListSellerController extends OnAuthController
{
    public $modelClass = ShortPlaysList::class;


    /**
     * 短剧数量统计
     * @return array
     */
    public function actionCount()
    {
        $model['shortPlaysCount'] = ShortPlaysList::find()
                ->where(['status' => StatusEnum::ENABLED])
                ->count() ?? 0;
        $model['sellerAvailableCount'] = SellerAvailableList::find()
                ->where(['status' => StatusEnum::ENABLED, 'member_id' => $this->memberId])
                ->count() ?? 0;
        $memberInfo = Member::find()
            ->select(['id', 'vip_level'])
            ->where(['id' => $this->memberId])
            ->with(['sellerLevel' => function ($query) {
                $query->select(['id', 'level', 'number']);
            }])
            ->asArray()
            ->one();
        $model['sellerCanAvailableCount'] = $memberInfo['sellerLevel']['number'];
        return $model;

    }

    /**
     * 能上架短剧列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $keyword = Yii::$app->request->get('keyword');
        $pid = Yii::$app->request->get('pid');
        if (empty($pid)) {
            return new ActiveDataProvider([
                'query' => $this->modelClass::find()
                    ->where(['status' => StatusEnum::ENABLED])
                    ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
                    ->with([
                        'sellerAvailableList' => function ($query) {
                            $query->select(['id', 'pid'])
                                ->where(['status' => StatusEnum::ENABLED, 'member_id' => $this->memberId]);
                        }
                    ])
                    ->joinWith([
                        'translation' => function ($query) use ($lang, $keyword) {
                            $query->where(['lang' => $lang]);
                            if ($keyword) {
                                $query->where(['like', 'title', "%" . $keyword . "%", false]);
                            }
                        },
                    ])
                    ->asArray(),
                'pagination' => [
                    'pageSize' => $this->pageSize,
                    'validatePage' => false,// 超出分页不返回data
                ],
            ]);
        } else {
            return new ActiveDataProvider([
                'query' => $this->modelClass::find()
                    ->where(['status' => StatusEnum::ENABLED])
                    ->andWhere('JSON_SEARCH(label,"one",:value) IS NOT NULL', [':value' => $pid])
                    ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
                    ->with([
                        'sellerAvailableList' => function ($query) {
                            $query->select(['id', 'pid'])
                                ->where(['status' => StatusEnum::ENABLED, 'member_id' => $this->memberId]);
                        }
                    ])
                    ->joinWith([
                        'translation' => function ($query) use ($lang, $keyword) {
                            $query->where(['lang' => $lang]);
                            if ($keyword) {
                                $query->where(['like', 'title', "%" . $keyword . "%", false]);
                            }
                        },
                    ])
                    ->asArray(),
                'pagination' => [
                    'pageSize' => $this->pageSize,
                    'validatePage' => false,// 超出分页不返回data
                ],
            ]);
        }

    }

    /**
     * 短剧详情
     */
    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "信息错误");
        }
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return ShortPlaysList::find()
            ->where(['id' => $id])
            ->with([
                'sellerAvailableList' => function ($query) {
                    $query->select(['id', 'pid'])
                        ->where(['status' => StatusEnum::ENABLED, 'member_id' => $this->memberId]);
                },
                'translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                },
                'shortPlaysDetail' => function ($query) use ($lang) {
                    $query->orderBy(['number' => SORT_ASC])
                        ->with([
                            'translation' => function ($query) use ($lang) {
//                                $query->where(['lang' => $lang]);
                                $query->where(['lang' => 'cn']);
                            },
                        ]);

                },
            ])
            ->asArray()
            ->one();
    }


    /**
     * 我的上架列表
     * @return array
     */
    public function actionList()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $models = new ActiveDataProvider([
            'query' => SellerAvailableList::find()
                ->select([
                    'id',
                    'member_id',
                    'pid',
                    'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',
                ])
                ->where(['member_id' => $this->memberId, 'status' => 1])
                ->orderBy('created_at desc')
                ->with(['shortPlaysList' => function ($query) use ($lang) {
                    $query->with(['translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }]);
                }])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
        $models = $models->getModels();
        foreach ($models as $k => $model) {
            $models[$k]['available_number'] = SellerAvailableOrder::find()->where(['member_id' => $model['member_id'], 'pid' => $model['pid']])->sum('buy_number') ?? 0;
        }
        return $models;
    }

    /**
     * 上架短剧
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
//    public function actionCreate()
//    {
//        RedisHelper::verify($this->memberId, $this->action->id);
//
//        if (Member::find()->where(['id' => $this->memberId, 'realname_status' => 0])->exists()) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
//        }
//
//        $pids = Yii::$app->request->post('pids');
//        if (!is_array($pids)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '系统繁忙,请稍后再试');
//        }
//        $memberInfo = Member::find()->where(['id' => $this->memberId])->with(['sellerLevel'])->one();
//        // 判断已上架的剧集数
//        $is_available_count = SellerAvailableList::find()
//                ->where(['member_id' => $this->memberId, 'status' => 1])
//                ->count() ?? 0;
//        $can_available_count = $memberInfo->sellerLevel->number - $is_available_count;
//        if (count($pids) > $can_available_count) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '已超过当前代销员等级上架数量，请提升会员等级后再试');
//        }
//
//        // 上架剧集
//        foreach ($pids as $pid) {
//            if (SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 1, 'pid' => $pid])->exists()) { // 若已上架过则跳过
//                return ResultHelper::json(ResultHelper::ERROR_CODE, '当前选中短剧包含已上架数据，请查验后重新上架');
//            }
//            if ($memberInfo->sellerLevel->can_available_switch == 0 && ShortPlaysList::find()->where(['id' => $pid, 'is_new' => 1])->exists()) {
//                return ResultHelper::json(ResultHelper::ERROR_CODE, '您所选短剧包含您的会员等级不能上架的新短剧，请提升会员等级后再重新上架');
//            }
//        }
//
//        foreach ($pids as $pid) {
//            $ShortPlaysList = ShortPlaysList::find()->where(['id' => $pid, 'status' => 1])->asArray()->one();
//            if (empty($ShortPlaysList)) { // 如果短剧不存在或被禁用则跳过
//                continue;
//            } else {
//                if (SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 1, 'pid' => $pid])->exists()) { // 若已上架过则跳过
//                    continue;
//                } else {
//                    $model = SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 0, 'pid' => $pid])->one();
//                    if (empty($model)) {
//                        $model = new SellerAvailableList();
//                    }
//                    $model->member_id = $this->memberId;
//                    $model->pid = $pid;
//                    $model->status = 1;
//                    $model->save();
//                }
//            }
//        }
//        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "操作成功");
//    }


    /**
     * 上架短剧
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);

        if (Member::find()->where(['id' => $this->memberId, 'realname_status' => 0])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
        }

        $orders = Yii::$app->request->post('orders');
        if (!is_array($orders)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '系统繁忙,请稍后再试');
        }

        $memberInfo = Member::find()->where(['id' => $this->memberId])->with(['sellerLevel', 'account'])->one();


        // 判断卖家金额是否充足
        $money = 0;
        $order_available_count = 0;
        foreach ($orders as $order) {
            $shortPlaysList = ShortPlaysList::find()->where(['id' => $order['id'], 'status' => 1])->select(['amount', 'is_new'])->asArray()->one();
//            $money = BcHelper::add($money, BcHelper::mul($shortPlaysList['amount'], $order['number']));
            $dx_money = BcHelper::sub($shortPlaysList['amount'], BcHelper::mul($shortPlaysList['amount'], BcHelper::div($memberInfo->sellerLevel->profit, 100, 4)));
            $money = BcHelper::add($money, BcHelper::mul($dx_money, $order['number']));
            if (!SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 1, 'pid' => $order['id']])->exists()) {
                $order_available_count += 1;
            }
            if ($memberInfo->sellerLevel->can_available_switch == 0 && $shortPlaysList['is_new'] == 1) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '您所选短剧包含您的会员等级不能上架的新短剧，请提升会员等级后再重新上架');
            }
        }
        // 判断余额
        if ($money > BcHelper::add($memberInfo->account->user_money, $memberInfo->account->can_withdraw_money)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '当前余额不足，请先充值后再试');
        }
//        if ($money > $memberInfo->account->user_money) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '当前余额不足，请先充值后再试');
//        }

        // 判断已上架的剧集数
        $is_available_count = SellerAvailableList::find()
                ->where(['member_id' => $this->memberId, 'status' => 1])
                ->count() ?? 0;
        $can_available_count = $memberInfo->sellerLevel->number - $is_available_count;
        if ($order_available_count > $can_available_count) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '已超过当前代销员等级上架数量，请提升会员等级后再试');
        }

        // 开始扣款，先扣余额钱包
        if ($memberInfo->account->can_withdraw_money > 0) {
            if ($memberInfo->account->can_withdraw_money >= $money) {
                // 扣除卖家余额钱包
                Yii::$app->services->memberCreditsLog->decrCanWithdrawMoney(new CreditsLogForm([
                    'member' => $memberInfo,
                    'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
                    'num' => $money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【预售】上架扣除余额",
                ]));
            } else {
                $last_money = BcHelper::sub($money, $memberInfo->account->can_withdraw_money);
                // 扣除卖家余额钱包
                Yii::$app->services->memberCreditsLog->decrCanWithdrawMoney(new CreditsLogForm([
                    'member' => $memberInfo,
                    'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
                    'num' => $memberInfo->account->can_withdraw_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【预售】上架扣除余额",
                ]));
                Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                    'member' => $memberInfo,
                    'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
                    'num' => $last_money,
                    'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                    'remark' => "【预售】上架扣除余额",
                ]));
            }
        } else {
            // 扣除卖家充值钱包
            Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                'member' => $memberInfo,
                'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
                'num' => $money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【预售】上架扣除余额",
            ]));
        }

        // 扣除卖家充值钱包
//        Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
//            'member' => $memberInfo,
//            'pay_type' => CreditsLog::SEND_SHORT_PLAYS_TYPE,
//            'num' => $money,
//            'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
//            'remark' => "【预售】上架扣除余额",
//        ]));


        foreach ($orders as $order) {
            $shortPlaysList = ShortPlaysList::find()->where(['id' => $order['id'], 'status' => 1])->asArray()->one();
            if (empty($shortPlaysList)) { // 如果短剧不存在或被禁用则跳过
                continue;
            } else {
                $model = SellerAvailableList::find()->where(['member_id' => $this->memberId, 'pid' => $order['id']])->one();
                if (empty($model)) {
                    $model = new SellerAvailableList();
                }
                $model->member_id = $this->memberId;
                $model->pid = $order['id'];
                $model->status = 1;
                $model->save();
            }
            // 添加上架订单
            $availableOrder = new SellerAvailableOrder();
            $availableOrder->member_id = $this->memberId;
            $availableOrder->pid = $order['id'];
            $availableOrder->number = $order['number'];
            $availableOrder->buy_number = $order['number'];
            $availableOrder->created_at = time();
//            $money = BcHelper::mul($shortPlaysList['amount'], $order['number']);

            $dx_money = BcHelper::sub($shortPlaysList['amount'], BcHelper::mul($shortPlaysList['amount'], BcHelper::div($memberInfo->sellerLevel->profit, 100, 4)));
            $money = BcHelper::mul($dx_money, $order['number']);

            $availableOrder->money = $money;
            $availableOrder->buy_money = $money;
            $availableOrder->save();
        }


        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "操作成功");
    }


    // 下架剧集
//    public function actionDown()
//    {
//        RedisHelper::verify($this->memberId, $this->action->id);
//        $pids = Yii::$app->request->post('pids');
//        if (!is_array($pids)) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '系统繁忙,请稍后再试');
//        }
//        if (Yii::$app->debris->backendConfig('can_down_short_plays') == 0) {
//            return ResultHelper::json(ResultHelper::ERROR_CODE, '下架短剧功能暂未开启');
//        }
//        // 下架剧集
//        foreach ($pids as $pid) {
//            $model = SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 1, 'pid' => $pid])->one();
//            if (empty($model)) {
//                return ResultHelper::json(ResultHelper::ERROR_CODE, '当前选中短剧包含未上架数据，请查验后重新下架');
//            }
//        }
//        // 下架剧集
//        foreach ($pids as $pid) {
//            if (SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 0, 'pid' => $pid])->exists()) { // 若已下架过则跳过
//                continue;
//            } else {
//                $model = SellerAvailableList::find()->where(['member_id' => $this->memberId, 'status' => 1, 'pid' => $pid])->one();
//                if (empty($model)) {
//                    continue;
//                }
//                $model->status = 0;
//                $model->save();
//            }
//        }
//        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "操作成功");
//    }


    /**
     * 热门排行列表
     * @return ActiveDataProvider
     */
    public function actionHotList()
    {

        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $sort = Yii::$app->request->get('sort', 1);
        if ($sort == 1) {
            $sort_desc = ['number' => SORT_DESC];
        } else {
            $sort_desc = ['created_at' => SORT_DESC];
        }
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['status' => StatusEnum::ENABLED])
                ->with([
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    },
                    'sellerAvailableList' => function ($query) {
                        $query->select(['id', 'pid'])
                            ->where(['status' => StatusEnum::ENABLED, 'member_id' => $this->memberId]);
                    }
                ])
                ->orderBy($sort_desc)
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
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
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
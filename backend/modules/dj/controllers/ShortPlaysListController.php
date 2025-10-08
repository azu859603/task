<?php

namespace backend\modules\dj\controllers;

use backend\modules\dj\forms\AddOrderForm;
use common\helpers\BcHelper;
use common\models\common\Languages;
use common\models\dj\Orders;
use common\models\dj\SellerAvailableList;
use common\models\dj\ShortPlaysListTranslations;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use Yii;
use common\models\dj\ShortPlaysList;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\db\Expression;
use yii\web\UnprocessableEntityHttpException;

/**
 * ShortPlaysList
 *
 * Class ShortPlaysListController
 * @package backend\modules\dj\controllers
 */
class ShortPlaysListController extends BaseController
{
    use Curd;

    /**
     * @var ShortPlaysList
     */
    public $modelClass = ShortPlaysList::class;


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
            $model = ShortPlaysList::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('ShortPlaysList'));
            $post = ['ShortPlaysList' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                isset($posted['status']) && $output = ['1' => '启用', '0' => '禁用'][$model->status];
                isset($posted['is_top']) && $output = [1 => '是', 0 => '否'][$model->status];
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
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }]);


            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
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
//        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $model = $this->findModel($id);
        $model_translations = ShortPlaysListTranslations::find()->where(['lang' => $lang, 'pid' => $id])->one();
        if (empty($model_translations)) {
            $model_translations = new ShortPlaysListTranslations();
            $model_translations->lang = $lang;
        }
        if ($model->load(Yii::$app->request->post()) && $model_translations->load(Yii::$app->request->post())) {
            $model->created_by = Yii::$app->user->getId();
            if ($model->save()) {
                $model_translations->pid = $model->id;
                if ($model_translations->save()) {
                    return $this->message("操作成功", $this->redirect(['index']));
                }
                return $this->message($this->getError($model_translations), $this->redirect(Yii::$app->request->referrer), 'error');
            } else {
                return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->render($this->action->id, [
            'model' => $model,
            'model_translations' => $model_translations,
            'lang' => $lang,
        ]);
    }

    public function actionAddOrderOne()
    {
        $ids = Yii::$app->request->get('ids');
        if (empty($ids)) {
            return $this->message('请选择数据进行操作', $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model = new AddOrderForm();
        $model->scenario = 'add_one';
        if ($model->load(Yii::$app->request->post())) {
            $seller_id = $model->seller_id;
            return $this->redirect(['add-order', 'seller_id' => $seller_id, 'ids' => $ids]);
        }
        return $this->render($this->action->id, [
            'model' => $model,
        ]);
    }

//    public function actionAddOrder($seller_id, $ids)
//    {
//        $model = new AddOrderForm();
//        $model->scenario = 'add_two';
//        $ids = explode(',', $ids);
//        $virtual_count = Member::find()->where(['pid'=>$seller_id,'is_virtual'=>1,'type'=>0])->count();
//
//        if ($model->load(Yii::$app->request->post())) {
//            if ($model->start_time > $model->stop_time) {
//                return $this->message("开始时间不能大于停止时间", $this->redirect(['index']), 'error');
//            }
//            $as = $ids;
////            $bs = $model->member_id;
//            $bs = Member::find()->where(['pid'=>$seller_id,'is_virtual'=>1,'type'=>0])->select(['id'])->orderBy(new Expression('rand()'))->limit($model->member_id)->column();
//            if(empty($bs)){
//                return $this->message("操作失败，买家数量不足", $this->redirect(['index']), 'error');
//            }
//            $results = [];
//            $bsCount = count($bs);
//            foreach ($as as $i => $key) {
//                $results[$key] = $bs[$i % $bsCount];
//            }
//
//            $sellerMember = Member::find()->where(['id' => $seller_id])->with(['sellerLevel'])->one();
//
//            foreach ($results as $pid => $member_id) {
//                // 开启事务
//                $transaction = Yii::$app->db->beginTransaction();
//                try {
//
//
//                    $shortPlaysList = ShortPlaysList::find()
//                        ->where(['id' => $pid])
//                        ->with([
//                            'sellerAvailableList' => function ($query) use ($seller_id) {
//                                $query->where(['member_id' => $seller_id]);
//                            }
//                        ])
//                        ->one();
//
////                    // 判断买家余额是否充足，不够的话重新找个买家  是否购买过此短剧，购买过则重新随机一个买家
////                    $member = Member::find()->where(['id' => $member_id])
////                        ->with(['account'])
////                        ->one();
////                    if ($shortPlaysList->amount > $member->account->user_money || Orders::find()->where(['member_id' => $member_id, 'pid' => $id])->andWhere(['<', 'status', 2])->exists()) {
////                        $amount = $shortPlaysList->amount;
////                        $members = Member::find()
////                            ->where(['pid' => $seller_id])
////                            ->joinWith([
////                                'account' => function ($query) use ($amount) {
////                                    $query->where(['>=', 'user_money', $amount]);
////                                },
////                            ])
////                            ->all();
////                        $member = [];
////                        foreach ($members as $memberInfo) {
////                            if (!Orders::find()->where(['member_id' => $memberInfo->id, 'pid' => $id])->andWhere(['<', 'status', 2])->exists()) {
////                                $member = $memberInfo;
////                                break;
////                            }
////                        }
////                        if (empty($member)) {
////                            throw new UnprocessableEntityHttpException('买家余额不足或已购买过所选短剧');
////                        }
////                    }
//
//
//                    // 判断买家余额是否充足，不够的话重新找个买家
//                    $member = Member::find()->where(['id' => $member_id])
//                        ->with(['account'])
//                        ->one();
//
//
////                    if ($shortPlaysList->amount > $member->account->user_money) {
////                        $amount = $shortPlaysList->amount;
////                        $member = Member::find()
////                            ->where(['pid' => $seller_id])
////                            ->joinWith([
////                                'account' => function ($query) use ($amount) {
////                                    $query->where(['>=', 'user_money', $amount]);
////                                },
////                            ])
////                            ->one();
////                        if (empty($member)) {
////                            throw new UnprocessableEntityHttpException('买家余额不足');
////                        }
////                    }
//
//                    // 如果购买人的上级卖家未上架短剧
//                    if (empty($shortPlaysList['sellerAvailableList'])) {
//                        $income = BcHelper::mul(BcHelper::div($sellerMember->sellerLevel->profit_rebate, 100, 4), $shortPlaysList->amount);
//                        $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
//                    } else {
//                        $income = BcHelper::mul(BcHelper::div($sellerMember->sellerLevel->profit, 100, 4), $shortPlaysList->amount);
//                        $dx_money = BcHelper::sub($shortPlaysList->amount, $income);
//                    }
//
//                    $order_model = new Orders();
//                    $order_model->seller_id = $seller_id;
//                    $order_model->pid = $shortPlaysList->id;
//                    $order_model->money = $shortPlaysList->amount;
//                    $order_model->dx_money = $dx_money;
//                    $order_model->income = $income;
//                    $order_model->private_key = md5($pid . time());
//                    // 处理买家
//                    $order_model->member_id = $member->id;
//                    $time = rand(time() + ($model->start_time * 3600), time() + ($model->stop_time * 3600));
//                    $order_model->created_at = $time;
//
////                    // 扣除买家余额
////                    Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
////                        'member' => $member,
////                        'time' => $time,
////                        'pay_type' => CreditsLog::BUY_SHORT_PLAYS_TYPE,
////                        'num' => $order_model->money,
////                        'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
////                        'remark' => "【短剧】购买短剧扣除余额",
////                    ]));
//
//                    $order_model->save();
//                    $shortPlaysList->buy_number += 1;
//                    $shortPlaysList->save();
//                    // 新订单站内信
//                    Yii::$app->services->memberNotify->createMessage("新的订单", "您有新的订单，请前往订单列表查看", 1, [$order_model->seller_id], $time);
//                    $transaction->commit();
//                } catch (\Exception $e) {
//                    $transaction->rollBack();
//                    return $this->message("操作中止，" . $e->getMessage(), $this->redirect(['index']), 'error');
//                }
//            }
//            return $this->message("操作成功", $this->redirect(['index']));
//        }
//
//        return $this->render($this->action->id, [
//            'model' => $model,
//            'seller_id' => $seller_id,
//            'ids' => $ids,
//            'virtual_count' => $virtual_count,
//        ]);
//
//
//    }
}

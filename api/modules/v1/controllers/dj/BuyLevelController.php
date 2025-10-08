<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:44
 */

namespace api\modules\v1\controllers\dj;


use api\controllers\OnAuthController;
use common\helpers\BcHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\dj\BuyLevelList;
use common\models\dj\SellerAvailableList;
use common\models\dj\SellerLevel;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\MemberCard;
use common\models\tea\InvestmentBill;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\Json;

class BuyLevelController extends OnAuthController
{
    public $modelClass = BuyLevelList::class;


    /**
     * 会员说明列表
     * @return array|\yii\db\ActiveRecord[]
     */
    public function actionList()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return SellerLevel::find()
            ->where(['status' => 1])
            ->with([
                'translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                },
            ])
            ->orderBy(['level' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * 购买会员记录
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'member_id',
                    's_id',
                    'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',
                ])
                ->where(['member_id' => $this->memberId])
                ->orderBy('created_at desc')
                ->with(['sellerLevel' => function ($query) use ($lang) {
                    $query->with([
                        'translation' => function ($query) use ($lang) {
                            $query->where(['lang' => $lang]);
                        },
                    ]);
                }])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 购买会员
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, '');
        RedisHelper::verify($this->memberId, $this->action->id);

        if (Member::find()->where(['id' => $this->memberId, 'realname_status' => 0])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '请您先实名认证后再继续操作');
        }

        $s_id = Yii::$app->request->post('s_id');
        $levelModel = SellerLevel::findOne($s_id);
        if (empty($levelModel)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '系统繁忙,请稍后再试');
        }

        $memberInfo = Member::find()->where(['id' => $this->memberId])->with(['account'])->one();
        if ($levelModel->money > $memberInfo->account->user_money) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '余额不足');
        }
        if ($memberInfo->vip_level >= $levelModel->level) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '购买的会员等级不能大于当前会员等级');
        }

        // 开启事务
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 扣除余额
            Yii::$app->services->memberCreditsLog->decrMoney(new CreditsLogForm([
                'member' => $memberInfo,
                'num' => $levelModel->money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => '【会员】购买会员等级扣除余额',
                'pay_type' => CreditsLog::BUY_LEVEL_TYPE,
            ]));
            // 添加记录
            $model = new BuyLevelList();
            $model->member_id = $this->memberId;
            $model->s_id = $s_id;
            $model->save();
            // 更改会员等级
            $memberInfo->vip_level = $levelModel->level;
            $memberInfo->save(false);
            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "购买成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
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
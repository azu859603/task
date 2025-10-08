<?php
/**
 * Created by PhpStorm.
 * User: 毛阿毛
 * Date: 2019/11/28
 * Time: 11:57
 */

namespace services\member;

use common\enums\MemberLevelUpgradeTypeEnum;
use common\models\dj\SellerLevel;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\RedEnvelope;
use Yii;
use common\components\Service;
use common\enums\CacheEnum;
use common\enums\StatusEnum;
use common\models\member\Account;
use common\models\member\Level;
use common\models\member\Member;

/**
 * 用户等级类
 *
 * ````` 使用方法：``````
 * 根据用户信息获取可升等级 ： Yii::$app->services->memberLevel->getLevelByMember($member);
 * 余额+积分获取：Yii::$app->services->memberLevel->getLevel(1, 200, 300);
 *
 * Class LevelService
 * @author Maomao
 * @package services\member
 */
class LevelService extends Service
{
    /**
     * @var int $timeout 过期时间
     */
    private $timeout = 20;

    /**
     * @param Member $member
     * @return bool|Level|mixed|\yii\db\ActiveRecord
     */
    public function getLevelByMember(Member $member)
    {
        return $this->getLevel($member);
    }


    /**
     * 获取用户可升等级信息
     * @param $memberInfo
     * @return bool|Level|mixed|\yii\db\ActiveRecord
     */
//    public function getLevel($memberInfo)
//    {
//        if (!($levels = $this->getLevelForCache())) {
//            return false;
//        }
//
//
//        foreach ($levels as $level) {
//            if (!$this->getMiddle($level, $memberInfo)) {
//                continue;
//            }
//            if ($memberInfo->current_level < $level->level) {
//                return $level;
//            }
//        }
//
//        return false;
//    }

    /**
     * 获取用户可升等级信息
     * @param $memberInfo
     * @return bool|Level|mixed|\yii\db\ActiveRecord
     */
    public function getLevel($memberInfo)
    {
        if (!($levels = $this->getLevelForCache())) {
            return false;
        }
        foreach ($levels as $level) {
            if (!$this->getMiddle($level, $memberInfo)) {
                continue;
            }
            return $level;
        }

        return false;
    }


    /**
     * 获取用户可升等级信息
     * @param $memberInfo
     * @return bool|Level|mixed|\yii\db\ActiveRecord
     */
    public function getSellerLevel($memberInfo)
    {
        if (!($levels = $this->getSellerLevelForCache())) {
            return false;
        }
        foreach ($levels as $level) {
            if (!$this->getSellerMiddle($level, $memberInfo)) {
                continue;
            }
            return $level;
        }

        return false;
    }


    /**
     * 获取等级列表
     * @return array|mixed|\yii\db\ActiveRecord[]
     */
    public function getLevelForCache()
    {
        $key = CacheEnum::getPrefix('levelList');

        if (!($list = Yii::$app->cache->get($key))) {
            $list = $this->findAll();

            Yii::$app->cache->set($key, $list, $this->timeout);
        }

        return $list;
    }


    /**
     * 获取等级列表
     * @return array|mixed|\yii\db\ActiveRecord[]
     */
    public function getSellerLevelForCache()
    {
        $key = CacheEnum::getPrefix('sellerLevelList');

        if (!($list = Yii::$app->cache->get($key))) {
            $list = $this->findSellerAll();

            Yii::$app->cache->set($key, $list, $this->timeout);
        }

        return $list;
    }

    /**
     * @param $merchant_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findAll()
    {
        $merchant_id = Yii::$app->services->merchant->getId();

        return Level::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->andFilterWhere(['merchant_id' => $merchant_id])
            ->orderBy(['level' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }


    /**
     * @param $merchant_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findSellerAll()
    {
        return SellerLevel::find()
            ->where(['status' => StatusEnum::ENABLED])
            ->orderBy(['level' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }

    /**
     * @param $merchant_id
     * @return array|\yii\db\ActiveRecord[]
     */
    public function findAllByEdit()
    {
        $merchant_id = Yii::$app->services->merchant->getId();

        return Level::find()
            ->where(['status' => StatusEnum::ENABLED])
            // ->andWhere(['merchant_id' => $merchant_id])
            ->orderBy(['level' => SORT_ASC, 'id' => SORT_DESC])
            ->all();
    }


    /**
     * @param Level $level
     * @param $memberInfo
     * @return bool
     */
    private function getMiddle(Level $level, $memberInfo)
    {
        if (!$level) {
            return false;
        }

        $member_level_upgrade_type = Yii::$app->debris->backendConfig('member_level_upgrade_type');

        switch ($member_level_upgrade_type) {
            case MemberLevelUpgradeTypeEnum::INTEGRAL:
                if (abs($memberInfo->account->accumulate_integral) >= $level->integral) {
                    return true;
                }
                break;
            case MemberLevelUpgradeTypeEnum::CONSUMPTION_MONEY:
                if (abs($memberInfo->account->accumulate_money) >= $level->money) {
                    return true;
                }
                break;
            case MemberLevelUpgradeTypeEnum::EXPERIENCE:
                if (abs($memberInfo->account->experience) >= $level->experience) {
                    return true;
                }
                break;
        }

        return false;
    }


    /**
     * @param Level $level
     * @param $memberInfo
     * @return bool
     */
    private function getSellerMiddle(SellerLevel $seller_level, $memberInfo)
    {
        if (!$seller_level) {
            return false;
        }
        if (abs($memberInfo->account->investment_all_money) >= $seller_level->buy_money) {
            return true;
        }
        return false;
    }

    /**
     * @param $memberInfo
     */
    public function updateLevel($memberInfo)
    {
        /** @var Level $level */
        $level = Yii::$app->services->memberLevel->getLevel($memberInfo);
        if ($level != false) {
            Member::updateAll(['current_level' => $level->level], ['id' => $memberInfo->id]);
            // 如果当前升级内容
            if ($level->level > $memberInfo->current_level) {
                //  如果是升级 判断用户升级次数
                for ($i = $memberInfo->current_level + 1; $i <= $level->level; $i++) {
                    $title = "您已成功升级为VIP" . $i . "会员";
                    $level_array = Level::find()->where(['level' => $i])->select(['upgrade_money'])->asArray()->one();
                    if (!empty($level_array['upgrade_money'])) {
                        $upgrade_money = $level_array['upgrade_money'];
                        if ($upgrade_money > 0 && !RedEnvelope::find()->where(['member_id' => $memberInfo->id, 'title' => $title, 'type' => 2])->exists()) {
                            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                                'member' => $memberInfo,
                                'pay_type' => CreditsLog::GIFT_TYPE,
                                'num' => $upgrade_money,
                                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                                'remark' => "【系统】升级VIP" . $i . "，获得系统赠送红包",
                            ]));
                            // 添加升级弹窗提示
                            $model = new RedEnvelope();
                            $model->member_id = $memberInfo->id;
                            $model->title = $title;
                            $model->money = $upgrade_money;
                            $model->type = 2;
                            $model->save();
                        }
                    }
                }
            }
        }
    }


    /**
     * @param $memberInfo
     */
    public function updateSellerLevel($memberInfo)
    {
        /** @var Level $level */
        $level = Yii::$app->services->memberLevel->getSellerLevel($memberInfo);
        if ($level != false) {
            Member::updateAll(['vip_level' => $level->level], ['id' => $memberInfo->id]);
        }
    }
}
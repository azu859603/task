<?php

namespace common\models\dj;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_seller_level".
 *
 * @property int $id 主键
 * @property int $level 等级（数字越大等级越高）
 * @property string $money 售价
 * @property string $profit
 * @property string $profit_rebate
 * @property int $number 代售权数量
 * @property string $handling_fees_percentage 提现手续费
 * @property string $return_income_time
 * @property int $status 状态
 * @property int $can_available_switch
 * @property string $push_flow
 * @property string $buy_money
 */
class SellerLevel extends \yii\db\ActiveRecord
{
    /**
    public function behaviors()
    {
        return [
        [
            'class' => TimestampBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
            [
            'class' => BlameableBehavior::class,
            'attributes' => [
                ActiveRecord::EVENT_BEFORE_INSERT => ['created_by'],
                ],
            ],
        ];
    }
    * /

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_seller_level';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['level', 'number', 'status','can_available_switch'], 'integer'],
            [['money', 'handling_fees_percentage','profit','profit_rebate','return_income_time','push_flow','buy_money'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'level' => '等级（数字越大等级越高）',
            'money' => '售价',
            'number' => '上架数量',
            'handling_fees_percentage' => '提现手续费',
            'status' => '状态',
            'profit' => '利润百分比(已上架)',
            'profit_rebate' => '利润百分比(未上架)',
            'can_available_switch' => '能否上架新短剧',
            'return_income_time' => '返回收益时间',
            'push_flow' => '推流手续百分比',
            'buy_money' => '升级销售额',
        ];
    }

    public function getTranslation()
    {
        return $this->hasOne(SellerLevelTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(SellerLevelTranslations::class, ['pid' => 'id']);
    }
}

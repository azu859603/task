<?php

namespace common\models\dj;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_buy_level_list".
 *
 * @property int $id
 * @property int $member_id
 * @property int $s_id
 * @property int $created_at
 */
class BuyLevelList extends \yii\db\ActiveRecord
{


    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dj_buy_level_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['member_id', 's_id'], 'required'],
            [['member_id', 's_id', 'created_at'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => 'Member ID',
            's_id' => 'S ID',
            'created_at' => '购买时间',
        ];
    }

    /**
     * 关联用户表
     * @return \yii\db\ActiveQuery
     * @author 哈哈
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    public function getSellerLevel()
    {
        return $this->hasOne(SellerLevel::class, ['id' => 's_id']);
    }


}

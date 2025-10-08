<?php

namespace common\models\common;

use common\models\member\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "base_opinion_list".
 *
 * @property int $id
 * @property int $member_id 反馈会员ID
 * @property string $content 反馈内容
 * @property string $img_list 反馈图片
 * @property string $remark 回复内容
 * @property int $created_at 反馈时间
 */
class OpinionList extends \yii\db\ActiveRecord
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
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->member_id = Yii::$app->user->identity->member_id;
            $this->remark = "感谢您提供的宝贵意见，我们会尽快处理！";
        }

        return parent::beforeSave($insert);
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_opinion_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['content'], 'required'],
            [['member_id', 'created_at'], 'integer'],
            [['content','img_list','remark'], 'string'],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '反馈会员',
            'content' => '反馈内容',
            'img_list' => '反馈图片',
            'created_at' => '反馈时间',
            'remark' => '回复',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}

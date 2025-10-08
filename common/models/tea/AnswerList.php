<?php

namespace common\models\tea;

use common\enums\AppEnum;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_answer_list".
 *
 * @property int $id
 * @property int $member_id 会员
 * @property int $q_id 问题
 * @property string $answer 回答
 * @property int $created_at 回答时间
 */
class AnswerList extends \yii\db\ActiveRecord
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
            [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['member_id'],
                ],
                'value' => function () {
                    if (Yii::$app->id == AppEnum::API) {
                        return Yii::$app->user->identity->member_id;
                    }else{
                        return 0;
                    }
                },
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_answer_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['q_id', 'answer'], 'required'],
            [['member_id', 'q_id', 'created_at'], 'integer'],
            [['answer'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'member_id' => '会员',
            'q_id' => '问题',
            'answer' => '回答',
            'created_at' => '回答时间',
        ];
    }
}

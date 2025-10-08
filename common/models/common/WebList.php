<?php

namespace common\models\common;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_web_list".
 *
 * @property int $id
 * @property string $web_url
 * @property int $sort
 * @property int $status
 */
class WebList extends \yii\db\ActiveRecord
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
        return 't_web_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['web_url'], 'required'],
            [['sort', 'status'], 'integer'],
            [['web_url'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'web_url' => '域名',
            'sort' => '排序',
            'status' => '状态',
        ];
    }
}

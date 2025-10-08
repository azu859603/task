<?php

namespace common\models\dj;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_laber_list_translations".
 *
 * @property int $id
 * @property int $pid
 * @property string $title
 * @property string $lang
 */
class LaberListTranslations extends \yii\db\ActiveRecord
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
        return 'dj_laber_list_translations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid'], 'integer'],
            [['title'], 'string', 'max' => 255],
            [['lang'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => 'Pid',
            'title' => '标题',
            'lang' => 'Lang',
        ];
    }
}

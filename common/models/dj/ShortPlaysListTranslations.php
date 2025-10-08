<?php

namespace common\models\dj;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_short_plays_list_translations".
 *
 * @property int $id
 * @property string $title 标题
 * @property string $banner 宣传图
 * @property string $synopsis 简介
 * @property int $pid
 * @property string $lang
 */
class ShortPlaysListTranslations extends \yii\db\ActiveRecord
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
        return 'dj_short_plays_list_translations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid'], 'integer'],
            [['title', 'banner', 'synopsis'], 'string', 'max' => 255],
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
            'title' => '剧名',
            'banner' => '封面图',
            'synopsis' => '简介',
            'pid' => 'Pid',
            'lang' => 'Lang',
        ];
    }
}

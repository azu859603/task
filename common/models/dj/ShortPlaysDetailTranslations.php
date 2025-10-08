<?php

namespace common\models\dj;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_short_plays_detail_translations".
 *
 * @property int $id
 * @property int $pid
 * @property string $title
 * @property string $content
 * @property string $banner
 * @property string $lang
 */
class ShortPlaysDetailTranslations extends \yii\db\ActiveRecord
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
        return 'dj_short_plays_detail_translations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid'], 'integer'],
            [['title', 'content', 'banner'], 'string', 'max' => 255],
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
            'content' => '剧集地址',
            'banner' => '封面图',
            'lang' => 'Lang',
        ];
    }

    public function getShortPlaysDetail(){
        return $this->hasOne(ShortPlaysDetail::class,['id'=>'pid']);
    }
}

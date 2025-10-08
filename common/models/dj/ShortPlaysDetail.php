<?php

namespace common\models\dj;

use common\models\common\Languages;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_short_plays_detail".
 *
 * @property int $id
 * @property int $pid 短剧ID
 * @property int $sort 排序
 * @property int $like_number 点赞量
 * @property int $collect_number 收藏量
 * @property int $status 状态
 * @property int $created_at 添加时间
 * @property int $type
 * @property int $number
 * @property string $vid
 */
class ShortPlaysDetail extends \yii\db\ActiveRecord
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
        return 'dj_short_plays_detail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid'], 'required'],
            [['pid', 'sort', 'like_number', 'collect_number', 'status', 'created_at','type','number'], 'integer'],
            [['vid'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '短剧ID',
            'sort' => '排序',
            'like_number' => '点赞量',
            'collect_number' => '收藏量',
            'status' => '状态',
            'created_at' => '添加时间',
            'type' => '是否免费',
            'number' => '第几集',
            'vid' => 'VID',
        ];
    }

    public function getTranslation()
    {
        return $this->hasOne(ShortPlaysDetailTranslations::class, ['pid' => 'id'])->where(['lang'=>'cn']);
    }

    public function getTranslations()
    {
        return $this->hasMany(ShortPlaysDetailTranslations::class, ['pid' => 'id'])->where(['lang'=>'cn']);
    }

    public function getShortPlaysList()
    {
        return $this->hasOne(ShortPlaysList::class, ['id' => 'pid']);
    }

    public function getLikeList(){
        return $this->hasOne(LikeList::class, ['pid' => 'id']);
    }

    public function getCollectList(){
        return $this->hasOne(CollectList::class, ['pid' => 'id']);
    }
}

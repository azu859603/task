<?php

namespace common\models\dj;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_seller_level_translations".
 *
 * @property int $id 主键
 * @property string $title 等级名称
 * @property string $detail 会员介绍
 * @property string $lang 语言
 * @property string $banner
 * @property int $pid 父类
 */
class SellerLevelTranslations extends \yii\db\ActiveRecord
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
        return 'dj_seller_level_translations';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lang', 'pid'], 'required'],
            [['pid'], 'integer'],
            [['title', 'detail','banner'], 'string', 'max' => 255],
            [['lang'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => '主键',
            'title' => '等级名称',
            'detail' => '会员介绍',
            'lang' => '语言',
            'pid' => '父类',
            'banner' => '封面图',
        ];
    }
}

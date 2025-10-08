<?php

namespace common\models\dj;

use common\models\backend\Member;
use common\models\common\Languages;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dj_short_plays_list".
 *
 * @property int $id
 * @property int $created_by 发布人
 * @property string $amount 单价
 * @property array $label 标签
 * @property int $status 状态
 * @property int $sort 排序
 * @property int $created_at 添加时间
 * @property int $is_top
 * @property int $number
 * @property string $vid
 * @property string $aka
 * @property int $buy_number
 * @property int $is_new
 */
class ShortPlaysList extends \yii\db\ActiveRecord
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
        return 'dj_short_plays_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['label','amount','sort'], 'required'],
            [['created_by', 'status', 'sort', 'created_at','is_top','number','buy_number','is_new'], 'integer'],
            [['amount'], 'number'],
            [['label'], 'safe'],
            [['vid','aka'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => '发布人',
            'amount' => '单价',
            'label' => '标签',
            'status' => '状态',
            'sort' => '排序',
            'created_at' => '添加时间',
            'is_top' => '是否置顶',
            'number' => '播放量',
            'vid' => 'VID',
            'buy_number' => '销售量',
            'is_new' => '是否最新',
            'aka' => '别名',
        ];
    }

    public function getTranslation()
    {
        return $this->hasOne(ShortPlaysListTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(ShortPlaysListTranslations::class, ['pid' => 'id']);
    }

    /**
     * 关联管理员
     * @return \yii\db\ActiveQuery
     * @author 原创脉冲
     */
    public function getManager()
    {
        return $this->hasOne(Member::class, ['id' => 'created_by']);
    }

    public function getShortPlaysDetail(){
        return $this->hasOne(ShortPlaysDetail::class, ['pid' => 'id']);
    }

    public function getShortPlaysDetails(){
        return $this->hasMany(ShortPlaysDetail::class, ['pid' => 'id']);
    }

    public function getSellerAvailableList(){
        return $this->hasOne(SellerAvailableList::class, ['pid' => 'id']);
    }
}

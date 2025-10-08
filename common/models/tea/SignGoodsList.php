<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_sign_goods_list".
 *
 * @property int $id
 * @property string $banner 封面
 * @property string $title 标题
 * @property string $content 详情
 * @property string $experience 累积经验
 * @property int $sign_day 积分数量
 * @property int $total_amount 总共数量
 * @property int $remaining_amount 剩余数量
 * @property int $created_at 添加时间
 * @property int $status 状态
 * @property int $sort 排序
 * @property int $type
 * @property string $money
 * @property int $times
 */
class SignGoodsList extends \yii\db\ActiveRecord
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
        return 't_sign_goods_list';
    }

    public static $typeExplain = [
        1 => "实物奖品",
        2 => "现金红包",
//        3 => "幸运抽奖",
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'content', 'sign_day', 'money', 'type', 'times'], 'required'],
            [['content'], 'string'],
            [['experience', 'money'], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
            [['banner'], 'file', 'extensions' => 'png,jpg,jpeg,gif', 'mimeTypes' => 'image/jpeg, image/png, image/gif', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
            [['sign_day', 'total_amount', 'remaining_amount', 'created_at', 'status', 'sort', 'type', 'times'], 'integer'],
            [['banner'], 'string', 'max' => 255],
            [['title'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'banner' => '封面(270X270)',
            'title' => '标题',
            'content' => '详情',
            'sign_day' => '单价(积分)',
            'total_amount' => '总共数量',
            'remaining_amount' => '剩余数量',
            'created_at' => '添加时间',
            'status' => '状态',
            'sort' => '排序',
            'experience' => '累积经验',
            'money' => '数量',
            'type' => '商品类型',
            'times' => '可兑换次数',
        ];
    }
}

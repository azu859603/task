<?php

namespace common\models\tea;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_coupon_list".
 *
 * @property int $id
 * @property string $number 数额
 * @property int $max 认购项目金额
 * @property int $valid_date 有效天数
 * @property int $type 类型
 * @property int $status 状态
 * @property string $remark
 */
class CouponList extends \yii\db\ActiveRecord
{
    public static $typeExplain = [
        1 => "红包",
        2 => "加息",
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 't_coupon_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['number', 'max', 'valid_date','remark'], 'required'],
            [['number',], 'number', 'numberPattern' => '/^\d+(.\d{1,2})?$/', 'message' => '小数点后位数不能大于俩位'],
            [['max', 'valid_date', 'type', 'status'], 'integer'],
            [['remark', 'remark'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'number' => '数额',
            'max' => '认购项目金额',
            'valid_date' => '有效天数',
            'type' => '类型',
            'status' => '状态',
            'remark' => '备注',
        ];
    }
}

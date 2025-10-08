<?php

namespace common\models\common;

use common\enums\AppEnum;
use common\enums\StatusEnum;
use common\models\backend\Member;
use Yii;

/**
 * This is the model class for table "task_img_details".
 *
 * @property int $id
 * @property int $pid 所属类别
 * @property string $title 文章分类名称
 * @property string $content 图片详情
 * @property string $jump_url 跳转地址
 * @property int $sort 排序(越大越靠前)
 * @property int $status 状态(1正常,0禁用)
 * @property int $created_at 添加时间
 * @property int $updated_at 修改时间
 * @property int $created_by 发布人
 * @property int $updated_by 修改人
 * @property int $jump_type 跳转类型(0,站内,1站外)
 */
class ImgDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            \yii\behaviors\BlameableBehavior::className(),
            \yii\behaviors\TimestampBehavior::className()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_img_details';
    }

    /**
     * 获取所以启用的图片分类标识符
     * @return array
     */
    public static function getImgPidArray()
    {
        return ImgCategory::find()->where(['status' => StatusEnum::ENABLED])->select(['id'])->asArray()->column();
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'title'], 'required'],
            [['pid', 'sort', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by', 'jump_type'], 'integer'],
            [['title'], 'string', 'max' => 50],
            [['jump_url'], 'string', 'max' => 255],
            [['content'], 'file', 'extensions' => 'png,jpg,jpeg,gif', 'mimeTypes' => 'image/jpeg, image/png, image/gif', 'maxSize' => 1024 * 1024 * 10, 'maxFiles' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '分类',
            'title' => '标题',
            'content' => '图片',
            'jump_url' => '跳转地址',
            'sort' => '排序',
            'status' => '状态',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
            'created_by' => '发布人',
            'updated_by' => '修改人',
            'jump_type' => '跳转类别',
        ];
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

    /**
     * 关联分类
     * @return \yii\db\ActiveQuery
     * @author 原创脉冲
     */
    public function getCategory()
    {
        return $this->hasMany(ImgCategory::class, ['id' => 'pid']);
    }

    public function getTranslation()
    {
        return $this->hasOne(ImgDetailsTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(ImgDetailsTranslations::class, ['pid' => 'id']);
    }
}

<?php

namespace common\models\common;

use common\enums\StatusEnum;
use common\models\backend\Member;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "i_article_details".
 *
 * @property int $id
 * @property int $pid 所属类别
 * @property string $title 文章分类名称
 * @property string $content 详情
 * @property string $banner 图片
 * @property int $sort 排序(越大越靠前)
 * @property int $status 状态(1正常,0禁用)
 * @property int $created_at 添加时间
 * @property int $updated_at 修改时间
 * @property int $created_by 发布人
 * @property int $updated_by 修改人
 * @property int $is_topping 是否置顶(0,不置顶 1,置顶)
 * @property int $viewing_count 浏览次数
 */
class ArticleDetails extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'base_article_details';
    }


    public function behaviors()
    {
        return [
            [
                'class' => BlameableBehavior::class,
            ],
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pid', 'sort', 'created_at'], 'required'],
            [['pid', 'sort', 'status', 'updated_at', 'created_by', 'updated_by', 'is_topping', 'viewing_count'], 'integer'],
            [['content'], 'string'],
            [['title'], 'string', 'max' => 50],
            [['banner'], 'string', 'max' => 255],
            [['created_at'], 'datetime', 'timestampAttribute' => 'created_at'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pid' => '所属类别',
            'title' => '文章名称',
            'content' => '详情',
            'sort' => '排序',
            'status' => '状态',
            'created_at' => '添加时间',
            'updated_at' => '修改时间',
            'created_by' => '发布人',
            'updated_by' => '修改人',
            'is_topping' => '是否置顶',
            'viewing_count' => '浏览次数',
            'banner' => '封面(270X270)',
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
     * 获取所以启用的图片分类标识符
     * @return array
     */
    public static function getArticlePidArray()
    {
        return ArticleCategory::find()->select(['id'])->asArray()->column();
    }

    /**
     * @param $id
     * @param $lang
     * @return array|null|\yii\db\ActiveRecord
     * @author 原创脉冲
     */
    public static function getModelById($id, $lang)
    {
        return self::find()
            ->select(['id', 'title', 'banner', 'content', 'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at'])
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->with(['translation' => function ($query) use ($lang) {
                $query->where(['lang' => $lang]);
            }])
            ->asArray()
            ->one();
    }

    public function getTranslation()
    {
        return $this->hasOne(ArticleDetailsTranslations::class, ['pid' => 'id']);
    }

    public function getTranslations()
    {
        return $this->hasMany(ArticleDetailsTranslations::class, ['pid' => 'id']);
    }
}

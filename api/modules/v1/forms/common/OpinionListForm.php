<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/17
 * Time: 1:22
 */

namespace api\modules\v1\forms\common;

use yii\base\Model;
use Yii;
class OpinionListForm extends Model
{
    public $content;
    public $img_list;

    public function rules()
    {
        return [
            [['content'], 'required'],
            ['img_list', 'validateImg']
        ];
    }

    /**
     * 验证图片
     * @param $attribute
     * @author 原创脉冲
     */
    public function validateImg($attribute)
    {
        if (!$this->hasErrors()) {
            if (!is_array($this->img_list)) {
                $this->addError($attribute, "请上传数组形式的图片");
                return;
            }
            $can_opinion_number = Yii::$app->debris->config('can_opinion_number');
            if (!empty($can_opinion_number) && count($this->img_list) > $can_opinion_number) {
                $this->addError($attribute, "上传图片不能超过" . $can_opinion_number . "张");
                return;
            }
        }
    }

    public function attributeLabels()
    {
        return [
            'content' => '内容',
            'img_list' => '图片',
        ];
    }

}
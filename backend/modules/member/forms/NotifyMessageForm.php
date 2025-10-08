<?php

namespace backend\modules\member\forms;

use Yii;
use yii\base\Model;

/**
 * Class NotifyMessageForm
 * @package backend\modules\member\forms
 * @author 哈哈
 */
class NotifyMessageForm extends Model
{
    public $content;
    public $title;

    public $toManagerId;

    public function rules()
    {
        return [
            [['content', 'title','toManagerId'], 'required']
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => '标题',
            'content' => '内容',
            'toManagerId' => '发送对象',
        ];
    }
}
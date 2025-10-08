<?php
namespace backend\modules\member\forms;

use common\models\member\Notify;

/**
 * Class NotifyAnnounceForm
 * @package backend\modules\sys\forms
 * @author 哈哈
 */
class NotifyAnnounceForm extends Notify
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'content'], 'required'],
            [['content'], 'string'],
            [['title'], 'string', 'max' => 150],
        ];
    }
}
<?php


namespace common\models\tea;


use common\models\member\Member;
use yii\db\ActiveRecord;

class LastContact extends ActiveRecord
{
    public static function tableName()
    {
        return 'dk_last_contact';
    }

    /**
     *  记录最新私聊
     * @param $mid
     * @param $uid
     * @param $time
     * @param $content
     * @param $type
     * @param bool $inc
     */
    public static function savePrivateLog($mid, $uid, $time, $content, $type,$inc = false)
    {
        $model = self::findOne(['mid' => $mid, 'uid' => $uid]);
        if (!$model) {
            $model = new self();
            $model->mid = $mid;
            $model->uid = $uid;
            $model->unread_count = 0;
        }
        $model->last_time = $time;
        $model->last_content = $content;
        $model->type = $type;
        if ($inc) {
            $model->unread_count += 1;
        }
        $model->save(false);
    }

    public function getUserInfo()
    {
        return $this->hasOne(Member::class, ['id' => 'uid'])->select('id,avatar,nickname');
    }
}
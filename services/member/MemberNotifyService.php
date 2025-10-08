<?php

namespace services\member;

use Yii;
use yii\helpers\Json;
use yii\data\Pagination;
use common\enums\StatusEnum;
use common\components\Service;
use common\models\member\Notify;
use common\models\member\NotifyMember;
use common\enums\SubscriptionAlertTypeEnum;
use common\models\backend\NotifySubscriptionConfig;

/**
 * Class NotifyService
 * @package services\backend
 * @author 原创脉冲
 */
class MemberNotifyService extends Service
{
    /**
     * 创建一条或多条信息(私信)
     * @param $title
     * @param $content
     * @param $sender_id
     * @param $receiver
     * @param $$time
     * @return bool
     * @throws \yii\db\Exception
     */
    public function createMessage($title,$content, $sender_id, $receiver,$time)
    {
        $model = new Notify();
        $model->title = $title;
        $model->content = $content;
        $model->sender_id = $sender_id;
        $model->type = Notify::TYPE_MESSAGE;
        $model->created_at = $time;
        if ($model->save()) {
            $rows = [];
            $fields = ['notify_id', 'member_id', 'type', 'created_at', 'updated_at'];
            foreach ($receiver as $v) {
                $rows[] = [$model->id, $v, Notify::TYPE_MESSAGE, $time, time()];
            }
            !empty($rows) && Yii::$app->db->createCommand()->batchInsert(NotifyMember::tableName(), $fields, $rows)->execute();
            return true;
        }

        return false;
    }

    /**
     * 拉取公告
     *
     * @param int $member_id 用户id
     * @throws \yii\db\Exception
     */
    public function pullAnnounce($member_id)
    {
        $getIds = NotifyMember::find()
            ->where(['member_id' => $member_id, 'type' => Notify::TYPE_ANNOUNCE])
            ->select(['notify_id'])
            ->column();
        $notifys = Notify::find()
            ->where(['type' => Notify::TYPE_ANNOUNCE, 'status' => StatusEnum::ENABLED])
            ->andWhere(['not in', 'id', $getIds])
            ->asArray()
            ->all();
        // 新建UserNotify并关联查询出来的公告信息
        $rows = [];
        $fields = ['notify_id', 'member_id', 'type', 'created_at', 'updated_at'];
        foreach ($notifys as $notify) {
            $rows[] = [$notify['id'], $member_id, Notify::TYPE_ANNOUNCE, $notify['created_at'], time()];
        }

        !empty($rows) && Yii::$app->db->createCommand()->batchInsert(NotifyMember::tableName(), $fields, $rows)->execute();
    }

    /**
     * 拉取公告
     *
     * @param int $member_id 用户id
     * @throws \yii\db\Exception
     */
    public function pullAnnounceOld($member_id, $created_at)
    {
        // 从UserNotify中获取最近的一条公告信息的创建时间: lastTime
        $model = NotifyMember::find()
            ->where(['member_id' => $member_id, 'type' => Notify::TYPE_ANNOUNCE])
            ->orderBy('id desc')
            ->asArray()
            ->one();

        // 用lastTime作为过滤条件，查询Notify的公告信息
        $lastTime = $model ? $model['created_at'] : $created_at;
        $notifys = Notify::find()
            ->where(['type' => Notify::TYPE_ANNOUNCE, 'status' => StatusEnum::ENABLED])
            ->andWhere(['>', 'created_at', $lastTime])
            ->asArray()
            ->all();

        // 新建UserNotify并关联查询出来的公告信息
        $rows = [];
        $fields = ['notify_id', 'member_id', 'type', 'created_at', 'updated_at'];
        foreach ($notifys as $notify) {
            $rows[] = [$notify['id'], $member_id, Notify::TYPE_ANNOUNCE, $notify['created_at'], time()];
        }

        !empty($rows) && Yii::$app->db->createCommand()->batchInsert(NotifyMember::tableName(), $fields, $rows)->execute();
    }

    /**
     * 更新指定的notify，把isRead属性设置为true
     *
     * @param $member_id
     */
    public function read($member_id, $notifyIds)
    {
        NotifyMember::updateAll(['is_read' => true, 'updated_at' => time()], ['and', ['member_id' => $member_id], ['in', 'notify_id', $notifyIds]]);
    }

    /**
     * 全部设为已读
     *
     * @param $member_id
     */
    public function readAll($member_id)
    {
        NotifyMember::updateAll(['is_read' => true, 'updated_at' => time()], ['member_id' => $member_id, 'is_read' => false]);
    }
}
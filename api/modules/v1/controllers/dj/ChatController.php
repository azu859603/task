<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2019/11/24
 * Time: 2:08
 */

namespace api\modules\v1\controllers\dj;

use api\controllers\OnAuthController;
use api\modules\v1\forms\common\ImgForm;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\CommonPluginHelper;
use common\helpers\GatewayInit;
use common\helpers\ImageHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\base\User;
use common\models\common\Languages;
use common\models\common\Statistics;
use common\models\dj\Orders;
use common\models\dj\SellerAvailableList;
use common\models\dj\ShortPlaysDetail;
use common\models\dj\ShortPlaysDetailTranslations;
use common\models\dj\ShortPlaysList;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\ChatLog;
use common\models\tea\LastContact;
use common\models\tea\Room;
use common\models\tea\Words;
use GatewayClient\Gateway;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\web\UnprocessableEntityHttpException;

class ChatController extends OnAuthController
{
    public $modelClass = '';

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        GatewayInit::initBase();
        return true;
    }

    public function actionBinding()
    {
        if (empty($client_id = Yii::$app->request->post('client_id'))) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '未成功获取到clientID');
        }
        GatewayInit::initBase();
        Gateway::bindUid($client_id, "member_".$this->memberId);
        Member::updateAll(['online_status' => 1], ['id' => $this->memberId]);
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, 'OK');
    }

    /**
     * 聊天列表
     */
    public function actionIndex()
    {
        $friend_models = \common\models\backend\Member::find()
            ->where(['id' => 1])
            ->select(['id', 'head_portrait', 'nickname'])
            ->asArray()
            ->one();
        $last_friend_content = LastContact::find()
            ->where(['mid' => 1, 'uid' => $this->memberId])
            ->orderBy(['last_time' => SORT_DESC, 'id' => SORT_DESC])
            ->asArray()
            ->one();
        $friend_models['unread_count'] = ChatLog::find()->where(['from_id' => 1, 'to_id' => $this->memberId, 'is_read' => 0])->count() ?? 0;
        $friend_models['last_content'] = $last_friend_content['last_content'];
        $friend_models['type'] = $last_friend_content['type'];
        $friend_models['time'] = $last_friend_content['last_time'];
        $data['friend'][0] = $friend_models;

        // 获取管理
        $member = Member::findOne($this->memberId);
        $pid = Member::getParentsNumber($member);
        if (!empty($pid) && $pid != 1) {
            $p_member = Member::find()->where(['id' => $pid])->with(['bMember'])->one();
            if(!empty($p_member->bMember->id)){
                $friend_models_1 = \common\models\backend\Member::find()
                    ->where(['id' => $p_member->bMember->id])
                    ->select(['id', 'head_portrait', 'nickname'])
                    ->asArray()
                    ->one();
                $last_friend_content_1 = LastContact::find()
                    ->where(['mid' => $p_member->bMember->id, 'uid' => $this->memberId])
                    ->orderBy(['last_time' => SORT_DESC, 'id' => SORT_DESC])
                    ->asArray()
                    ->one();
                $friend_models_1['unread_count'] = ChatLog::find()->where(['from_id' => $p_member->bMember->id, 'to_id' => $this->memberId, 'is_read' => 0, 'to_type' => 2])->count() ?? 0;
                $friend_models_1['last_content'] = $last_friend_content_1['last_content'];
                $friend_models_1['type'] = $last_friend_content_1['type'];
                $friend_models_1['time'] = $last_friend_content_1['last_time'];
                $data['friend'][1] = $friend_models_1;
            }
        }
        return $data;
    }

    /**
     *  历史记录
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionChatHistory()
    {
        $params = \Yii::$app->request->get();
        $role_manager = ChatLog::ROLE_TYPE_MANAGER;
        $role_member = ChatLog::ROLE_TYPE_MEMBER;
        $identity = \Yii::$app->user->identity->member;
        $my_id = $identity->getId();
        $limit = 10;
        $order = " order by id desc";
        if (!isset($params['id'])) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '系统错误');
        }
        $type_and_id = explode('_', $params['id']);
        if ($params['type'] == 'friend') {
            $table_name = ChatLog::tableName();
            // 单人对话记录，只需要查询一次对方信息和我的信息
            $innerJoin = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) AND ((from_id = {$my_id} and from_type = {$role_member} and to_id = {$type_and_id[1]} and to_type = {$role_manager}) or (from_id = {$type_and_id[1]} and from_type = {$role_manager} and to_id = {$my_id} and to_type = {$role_member}))";
            if (isset($params['top_id'])) {
                $innerJoin .= " AND id < {$params['top_id']}";
            }
        } else {
            ChatLog::$room_id = $params['id'];
            $table_name = ChatLog::tableName();
            // 群聊记录
            $innerJoin = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) and to_id = 0 and to_type = 0";
            if (isset($params['top_id'])) {
                $innerJoin .= " AND id < {$params['top_id']}";
            }
        }
        $innerJoin .= $order . " limit {$limit}) b using (id)";
        $sql = "SELECT a.id,a.from_id,a.from_type,a.to_id,a.to_type,a.content,a.created_at,a.msg_type FROM {$table_name} a inner join {$innerJoin} ";
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sql);
        $log = $command->queryAll();
        $return_data = [];
        if ($log) {
            $to_info = [];
            foreach ($log as $k => $v) {
                $return_data[$k]['msg_id'] = $v['id'];
                $return_data[$k]['timestamp'] = $v['created_at'];
                $return_data[$k]['content'] = $v['content'];
                $return_data[$k]['msg_type'] = $v['msg_type'];
                if ($v['from_type'] == $role_member && $v['from_id'] == $my_id) {
                    $return_data[$k]['username'] = $identity->nickname;
                    $return_data[$k]['id'] = 'member_' . $my_id;
                    $return_data[$k]['avatar'] = $identity->head_portrait ?: '/resources/dist/img/profile_small.jpg';
                    $return_data[$k]['timestamp'] = $v['created_at'];
                    $return_data[$k]['content'] = $v['content'];
                } else {
                    $_id = $v['from_type'] . '_' . $v['from_id'];
                    if (!isset($to_info[$_id])) {
                        if ($v['from_type'] == $role_manager) {
                            $model = \common\models\backend\Member::find()->alias('a')
                                ->select('a.id,a.nickname,a.head_portrait');
                        } else if ($v['from_type'] == $role_member) {
                            $model = Member::find()->alias('a')
                                ->select('a.id,a.nickname,a.head_portrait');
                        } else {
                            // 系统消息
                            continue;
                        }
                        $to_info[$_id] = $model
                            ->where(['a.id' => $v['from_id']])
                            ->asArray()
                            ->one();
                    }
                    $role = $v['from_type'] == $role_manager ? 'manager_' : 'member_';
                    $return_data[$k]['username'] = $to_info[$_id]['nickname'];
                    $return_data[$k]['id'] = $role . $v['from_id'];
                    $return_data[$k]['avatar'] = $to_info[$_id]['head_portrait'];
                }
            }
        }
        return $return_data;
    }

    /**
     *  未读消息设为已读
     * @return string
     */
    public function actionReadPrivateMsg()
    {
        $data = \Yii::$app->request->post();
        if (!isset($data['id'])) {
            return '';
        }
        $id_info = explode('_', $data['id']);
        $type = $id_info[0] == 'member' ? ChatLog::ROLE_TYPE_MEMBER : ChatLog::ROLE_TYPE_MANAGER;
        ChatLog::setMsgRead($id_info[1], $type, ChatLog::ROLE_TYPE_MEMBER);
        return '';
    }

    /**
     *  发送消息
     * @return mixed|string
     */
    public function actionSendMsg()
    {
        $data = Yii::$app->request->post();
        // 保存记录
        $identity = \Yii::$app->user->identity->member;
        $my_id = $identity->getId();
        $uid = explode('_', $data['to'])[1];
        if ($data['type'] == 'friend') {
            $msg_id = ChatLog::saveChatLog($data['content'], ChatLog::ROLE_TYPE_MEMBER, $uid, ChatLog::ROLE_TYPE_MANAGER, $data['msgType']);
            $time = time();
            Gateway::sendToUid($data['to'], Json::encode([
                'id' => 'member_' . $my_id,
                'cid' => $msg_id,
                'username' => $identity->remark ?: $identity->mobile,
                'from_id' => 'member_' . $my_id,
                'avatar' => $identity->head_portrait ?: '/resources/dist/img/profile_small.jpg',
                'type' => 'message',
                'content' => $data['content'],
                'group_type' => $data['type'],
                'time' => $time,
                'msg_type' => $data['msgType'],
                'nickname' => $identity->mobile
            ]));
            // 写入私聊记录
            LastContact::savePrivateLog($uid, $my_id, $time, $data['content'], $data['msgType'], true);
        } else {
            $room_id = $data['to'];
            $room = Room::findOne($room_id);
            if ($room['status'] == Room::STATUS_GAG) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '房间已禁言');
            }
            if (!Words::matchWords($room_id, $data['content'])) {
                return ResultHelper::json(ResultHelper::ERROR_CODE, '请勿输入非法词汇');
            }
            ChatLog::$room_id = $room_id;
            $msg_id = ChatLog::saveChatLog($data['content'], ChatLog::ROLE_TYPE_MEMBER, 0, 0, $data['msgType']);
            $ids = Gateway::getUidListByGroup($data['to']);
            if ($ids) {
                $send_data = [
                    'id' => $data['to'],
                    'cid' => $msg_id,
                    'from_id' => 'member_' . $my_id,
                    'avatar' => $identity->head_portrait ?: '/resources/dist/img/profile_small.jpg',
                    'type' => 'message',
                    'content' => $data['content'],
                    'group_type' => $data['type'],
                    'time' => time(),
                    'msg_type' => $data['msgType'],
                    'nickname' => $identity->nickname,
                    'username' => $identity->mobile,
                ];
                foreach ($ids as $id) {
                    Gateway::sendToUid($id, Json::encode($send_data));
                }
            }
        }
        return $msg_id;
    }

    /**
     *  websocket重连，拉取消息
     */
    public function actionReconnect()
    {
        $role_manager = ChatLog::ROLE_TYPE_MANAGER;
        $role_member = ChatLog::ROLE_TYPE_MEMBER;
        $identity = \Yii::$app->user->identity->member;
        $id = $identity->getId();

        $time = time();
        $to_info = [];
        $disconnect_time = \Yii::$app->request->get('disconnect_time');
        if (!$disconnect_time) {
            return '';
        }
        $connection = \Yii::$app->db;
        if ($identity->room_ids) {
            foreach ($identity->room_ids as $room_id) {
                ChatLog::$room_id = $room_id;
                $_table_name = ChatLog::tableName();
                // 群消息
                $innerJoin = "(SELECT id FROM {$_table_name} b WHERE ( `status` = 1 ) and to_id = 0 and to_type = 0 and created_at between {$disconnect_time} and {$time} order by id desc limit 20) b using (id)";
                $sql = "SELECT a.id,a.from_id,a.from_type,a.to_id,a.to_type,a.content,a.created_at,a.msg_type FROM {$_table_name} a inner join {$innerJoin} order by id";
                $command = $connection->createCommand($sql);
                $log = $command->queryAll();
                if ($log) {
                    foreach ($log as $k => $v) {
                        $role = $v['from_type'] == $role_manager ? 'manager_' : 'member_';
                        $send_data = [];
                        $send_data['cid'] = $v['id'];
                        $send_data['time'] = (int)$v['created_at'];
                        $send_data['type'] = 'message';
                        $send_data['from_id'] = $role . $v['from_id'];
                        $send_data['content'] = $v['content'];
                        $send_data['msg_type'] = $v['msg_type'];
                        if ($v['from_type'] == $role_member && $v['from_id'] == $id) {
                            // 我发出的
                            continue;
                        } else {
                            $_id = $v['from_type'] . '_' . $v['from_id'];
                            if (!isset($to_info[$_id])) {
                                if ($v['from_type'] == $role_manager) {
                                    $model = \common\models\backend\Member::find()->alias('a')
                                        ->select('a.id,a.nickname,a.head_portrait');
                                } else if ($v['from_type'] == $role_member) {
                                    $model = Member::find()->alias('a')
                                        ->select('a.id,a.nickname,a.remark,a.head_portrait');
                                } else {
                                    // 系统消息
                                    continue;
                                }
                                $to_info[$_id] = $model
                                    ->where(['a.id' => $v['from_id']])
                                    ->asArray()
                                    ->one();
                            }
                            $send_data['group_type'] = 'group';
                            $send_data['username'] = $to_info[$_id]['nickname'];
                            $send_data['id'] = $room_id;
                            $send_data['avatar'] = ImageHelper::defaultHeaderPortrait($to_info[$_id]['head_portrait']);
                        }
                        Gateway::sendToUid('member_' . $id, Json::encode($send_data));
                    }
                }
            }
        }
        $table_name = ChatLog::tableName();
        // 私聊消息
        $innerJoin2 = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) AND to_id = {$id} and to_type = {$role_member} and created_at between {$disconnect_time} and {$time} order by id desc limit 20) b using (id)";
        $sql2 = "SELECT a.id,a.from_id,a.from_type,a.to_id,a.to_type,a.content,a.created_at,a.msg_type FROM {$table_name} a inner join {$innerJoin2} order by id";
        $command2 = $connection->createCommand($sql2);
        $log2 = $command2->queryAll();
        if ($log2) {
            foreach ($log2 as $k => $v) {
                $role = $v['from_type'] == $role_manager ? 'manager_' : 'member_';
                $send_data = [];
                $send_data['cid'] = $v['id'];
                $send_data['time'] = (int)$v['created_at'];
                $send_data['type'] = 'message';
                $send_data['from_id'] = $role . $v['from_id'];
                $send_data['content'] = $v['content'];
                $send_data['msg_type'] = $v['msg_type'];
                if ($v['from_type'] == $role_member && $v['from_id'] == $id) {
                    // 我发出的
                    continue;
                } else {
                    $_id = $v['from_type'] . '_' . $v['from_id'];
                    if (!isset($to_info[$_id])) {
                        if ($v['from_type'] == $role_manager) {
                            $model = \common\models\backend\Member::find()->alias('a')
                                ->select('a.id,a.nickname,a.head_portrait');
                        } else if ($v['from_type'] == $role_member) {
                            $model = Member::find()->alias('a')
                                ->select('a.id,a.nickname,a.remark,a.head_portrait');
                        } else {
                            // 系统消息
                            continue;
                        }
                        $to_info[$_id] = $model
                            ->where(['a.id' => $v['from_id']])
                            ->asArray()
                            ->one();
                    }
                    $send_data['group_type'] = 'friend';
                    $send_data['username'] = $to_info[$_id]['nickname'];
                    $send_data['id'] = $role . $v['from_id'];
                    $send_data['avatar'] = $to_info[$_id]['head_portrait'] ?: '/resources/dist/img/profile_small.jpg';
                }
                Gateway::sendToUid('member_' . $id, Json::encode($send_data));
            }
        }
        return '';
    }


    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
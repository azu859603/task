<?php

namespace backend\controllers;

use backend\modules\member\forms\RechargeForm;
use common\enums\StatusEnum;
use common\helpers\DateHelper;
use common\helpers\GatewayInit;
use common\helpers\ImageHelper;
use common\helpers\ResultHelper;
use common\helpers\UploadHelper;
use common\models\base\User;
use common\models\common\Attachment;
use common\models\common\Statistics;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\ChatLog;
use common\models\tea\LastContact;
use common\models\tea\Room;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use GatewayClient\Gateway;
use yii\helpers\Json;
use Yii;

/**
 * ChatController
 *
 * Class LevelController
 * @package backend\modules\room\controllers
 */
class ChatController extends BaseController
{

    public $enableCsrfValidation = false;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        GatewayInit::initBase();
        return true;
    }

    /**
     *  显示layim
     * @return string
     */
    public function actionIndex()
    {
        return '';
//        return $this->render('index');
    }


    /**
     *  将client_id和uid绑定，加入群组
     * @return array
     */
    public function actionBind()
    {
        $identity = \Yii::$app->user->identity;
        $client_id = \Yii::$app->request->post('client_id');
        Gateway::bindUid($client_id, 'manager_' . $identity->getId());
        if ($identity->room_ids && is_array($identity->room_ids) && count($identity->room_ids) > 0) {
//            var_dump($client_id);
//            var_dump($identity->room_ids);exit;
            foreach ($identity->room_ids as $v) {
                Gateway::joinGroup($client_id, $v);
            }

        }
        \common\models\backend\Member::setOnline();
        return ResultHelper::json(200, 'ok');
    }

    /**
     *  聊天室初始化
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionInit()
    {
        $identity = \Yii::$app->user->identity;
        $return_data['mine']['id'] = 'manager_' . $identity->id;
        $return_data['mine']['username'] = $identity->nickname;
        $return_data['mine']['avatar'] = ImageHelper::defaultHeaderPortrait($identity->head_portrait);
        if ($identity->room_ids) {
            $i = 0;
            foreach ($identity->room_ids as $k => $v) {
                $room = Room::findOne($v);
                if ($room['status'] != Room::STATUS_DISABLED) {
                    $name = $room['remark'] ?: $room['name'];
                    $return_data['group'][$i]['id'] = $room['id'];
                    $return_data['group'][$i]['groupname'] = $room['status'] == 2 ? $name . '(禁言)' : $name;
                    $return_data['group'][$i]['avatar'] = ImageHelper::defaultHeaderPortrait($room['avatar']);
                    ChatLog::$room_id = $v;
                    $last_msg = ChatLog::find()->select('id,content,from_id,from_type,created_at,msg_type')->where(['to_type' => 0, 'to_id' => 0, 'status' => ChatLog::STATUS_NORMAL])->orderBy(['id' => SORT_DESC])->limit(1)->one();
                    $return_data['group'][$i]['last_msg'] = $last_msg ? $last_msg->content : '';
                    $return_data['group'][$i]['msg_type'] = $last_msg ? (string)$last_msg->msg_type : '';
                    $return_data['group'][$i]['time'] = $last_msg ? (int)$last_msg->created_at : 0;
                    $return_data['group'][$i]['sender_name'] = '';
                    $i++;
                }
            }
        }
        $return_data['friend'][0]['id'] = 0;
        $return_data['friend'][0]['groupname'] = '会员';
        $return_data['friend'][0]['list'] = Member::memberLevelList($identity);

        return ResultHelper::json(0, '', $return_data);
    }

    /**
     *  查询在线状态
     * @param $id
     * @return array
     */
    public function actionUserStatus($id)
    {
        return ResultHelper::json(200, Member::getOnlineStatus($id));
    }

    /**
     *  发送消息
     * @return array
     */
    public function actionSendMsg()
    {
        $data = \Yii::$app->request->post();
        // 保存记录
        $identity = \Yii::$app->user->identity;
        if ($data['type'] == 'friend') {
            $uid = explode('_', $data['to'])[1];
            $msg_id = ChatLog::saveChatLog($data['content'], ChatLog::ROLE_TYPE_MANAGER, $uid, ChatLog::ROLE_TYPE_MEMBER, $data['msgType']);
            $time = time();
            Gateway::sendToUid($data['to'], Json::encode([
                'id' => 'manager_' . $identity->getId(),
                'cid' => $msg_id,
                'username' => $identity->nickname,
                'from_id' => 'manager_' . $identity->getId(),
                'avatar' => ImageHelper::defaultHeaderPortrait($identity->head_portrait),
                'type' => 'message',
                'content' => $data['content'],
                'group_type' => $data['type'],
                'time' => $time,
                'level' => '',
                'msg_type' => $data['msgType'],
                'nickname' => $identity->nickname
            ]));
            // 写入私聊记录
            LastContact::savePrivateLog($identity->getId(), $uid, $time, $data['content'], $data['msgType']);
        } else {
            ChatLog::$room_id = $data['to'];
            $msg_id = ChatLog::saveChatLog($data['content'], ChatLog::ROLE_TYPE_MANAGER, 0, 0, $data['msgType']);
            Gateway::sendToGroup($data['to'], Json::encode([
                'id' => $data['to'],
                'cid' => $msg_id,
                'username' => $identity->nickname,
                'from_id' => 'manager_' . $identity->getId(),
                'avatar' => ImageHelper::defaultHeaderPortrait($identity->head_portrait),
                'type' => 'message',
                'content' => $data['content'],
                'group_type' => $data['type'],
                'time' => time(),
                'level' => '',
                'msg_type' => $data['msgType'],
                'nickname' => $identity->nickname
            ]));
            if ($data['atArr']) {
                $at_arr = explode(',', $data['atArr']);
                $room = (new Room())->roomOne($data['to']);
                $send_data = [
                    'id' => $data['to'],
                    'name' => $room['name'],
                    'avatar' => ImageHelper::defaultHeaderPortrait($room['avatar']),
                    'content' => $data['content'],
                    'type' => 'cmd',
                    'order' => 'at',
                    'group_type' => $data['type']
                ];
                foreach ($at_arr as $value) {
                    Gateway::sendToUid($value, Json::encode($send_data));
                }
            }
        }
        return ResultHelper::json(200, 'ok', ['cid' => $msg_id]);
    }

    /**
     *  发送图片
     * @return array
     */
    public function actionImage()
    {
        try {
            $upload = new UploadHelper(\Yii::$app->request->post(), Attachment::UPLOAD_TYPE_IMAGES);
            $upload->verifyFile();
            $upload->save();

            return ResultHelper::json(0, '上传成功', ['src' => $upload->getBaseInfo()['url']]);
        } catch (\Exception $e) {
            return ResultHelper::json(404, $e->getMessage());
        }
    }

    /**
     *  设置消息为已读
     * @return array
     */
    public function actionRead()
    {
        $data = \Yii::$app->request->post();
        if ($data['type'] == 'friend') {
            $id_info = explode('_', $data['id']);
            $type = $id_info[0] == 'member' ? ChatLog::ROLE_TYPE_MEMBER : ChatLog::ROLE_TYPE_MANAGER;
            ChatLog::setMsgRead($id_info[1], $type, ChatLog::ROLE_TYPE_MANAGER);
            $identity = \Yii::$app->user->identity;
            $condition = ['and',
                ['mid' => $identity->getId()],
                ['uid' => $id_info[1]]
            ];
            LastContact::updateAll(['unread_count' => 0], $condition);
        }
        return ResultHelper::json(200, 'ok');
    }

    /**
     *  聊天记录页面
     * @return string
     */
    public function actionChatLog()
    {
        return $this->renderPartial('chat-log');
    }

    /**
     *  聊天记录
     * @return array|string
     * @throws \yii\db\Exception
     */
    public function actionChatLogDetail()
    {
        if (\Yii::$app->request->isAjax) {
            $params = \Yii::$app->request->get();
            $type_and_id = explode('_', $params['id']);
            $role_manager = ChatLog::ROLE_TYPE_MANAGER;
            $role_member = ChatLog::ROLE_TYPE_MEMBER;
            $identity = \Yii::$app->user->identity;
            $limit = 20;
            $id = $identity->getId();
            $order = " order by id desc";
            $sort = "";
            if ($params['type'] == 'friend') {
                $member = Member::findOne($type_and_id[1]);
                $table_name = ChatLog::tableName();
                // 单人对话记录
                $innerJoin = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) AND ((from_id = {$id} and from_type = {$role_manager} and to_id = {$type_and_id[1]} and to_type = {$role_member}) or (from_id = {$type_and_id[1]} and from_type = {$role_member} and to_id = {$id} and to_type = {$role_manager}))";
                if (isset($params['top_id'])) {
                    $innerJoin .= " AND id < {$params['top_id']}";
                }
                if (isset($params['bottom_id'])) {
                    $innerJoin .= " AND id > {$params['bottom_id']}";
                    $order = " order by id";
                    if (!isset($params['second_page'])) {
                        $sort = "order by id desc";
                    }
                }
            } else {
                // 群聊记录
                $table_name = 'rf_chat_log_' . $params['id'];
                $innerJoin = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) and to_id = 0 and to_type = 0";
                if (isset($params['top_id'])) {
                    $innerJoin .= " AND id < {$params['top_id']}";
                }
                if (isset($params['bottom_id'])) {
                    $innerJoin .= " AND id > {$params['bottom_id']}";
                    $order = " order by id";
                    if (!isset($params['second_page'])) {
                        $sort = "order by id desc";
                    }
                }
            }
            $innerJoin .= $order . " limit {$limit}) b using (id)";
            $sql = "SELECT a.id,a.from_id,a.from_type,a.to_id,a.to_type,a.content,a.created_at,a.msg_type FROM {$table_name} a inner join {$innerJoin}" . $sort;
            $connection = \Yii::$app->db;
            $command = $connection->createCommand($sql);
            $log = $command->queryAll();
            $return_data = [];
            if ($log) {
                $to_info = [];
                foreach ($log as $k => $v) {
                    $_id = $v['from_type'] . '_' . $v['from_id'];
                    $return_data[$k]['msg_id'] = $v['id'];
                    $return_data[$k]['timestamp'] = $v['created_at'] * 1000;
                    $return_data[$k]['content'] = $v['content'];
                    $return_data[$k]['msgType'] = $v['msg_type'];
                    if ($v['from_type'] == $role_manager && $v['from_id'] == $id) {
                        $return_data[$k]['username'] = $identity->nickname;
                        $return_data[$k]['id'] = 'manager_' . $id;
                        $return_data[$k]['avatar'] = ImageHelper::defaultHeaderPortrait($identity->head_portrait);
                        $return_data[$k]['timestamp'] = $v['created_at'] * 1000;
                        $return_data[$k]['content'] = $v['content'];
                    } else {
                        if (!isset($to_info[$_id])) {
                            if ($v['from_type'] == $role_manager) {
                                $model = \common\models\backend\Member::find()->alias('a')
                                    ->select('a.id,a.nickname,a.head_portrait');
                            } else if ($v['from_type'] == $role_member) {
                                $model = Member::find()->alias('a');
//                                    ->select('a.id,a.nickname,a.remark,a.head_portrait,b.name as level')
//                                    ->join('LEFT JOIN', 'dj_seller_level b', 'a.level_id=b.id');
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
//                        $return_data[$k]['level'] = isset($to_info[$_id]['level']) ? $to_info[$_id]['level'] : '';
                        $return_data[$k]['level'] = "会员";
                        $return_data[$k]['username'] = isset($to_info[$_id]['remark']) ? $to_info[$_id]['remark'] : $to_info[$_id]['mobile'];
                        $return_data[$k]['id'] = $role . $v['from_id'];
                        $return_data[$k]['avatar'] = ImageHelper::defaultHeaderPortrait($to_info[$_id]['head_portrait']);
                    }
                }
            }
            return $return_data ? ResultHelper::json(0, '', $return_data) : ResultHelper::json(1, '没有更多数据');
        }
        return $this->renderPartial('chat-log');
    }

    /**
     *  获取群成员
     * @return array
     */
    public function actionGroupMembers($id)
    {
        $managers = \common\models\backend\Member::find()
            ->select('id,nickname,online_status,head_portrait')
            ->where(['status' => User::STATUS_ACTIVE])
            ->orderBy(['id' => SORT_ASC, 'online_status' => SORT_DESC])
            ->andWhere(new \yii\db\Expression('FIND_IN_SET(:room_ids,room_ids)'))
            ->addParams([':room_ids' => $id])
            ->asArray()
            ->all();
        $return_data['list'] = [];
        $return_data['members'] = [];
        if ($managers) {
            foreach ($managers as $k => $v) {
                $return_data['list'][$k]['username'] = $v['nickname'];
                $return_data['list'][$k]['id'] = 'manager_' . $v['id'];
                $return_data['list'][$k]['avatar'] = ImageHelper::defaultHeaderPortrait($v['head_portrait']);
                $return_data['list'][$k]['status'] = $v['online_status'] ? 'online' : 'offline';
            }
        }
        // 会员
        $members = Member::find()->select('id,mobile,online_status,head_portrait')
            ->where(['status' => User::STATUS_ACTIVE])
            ->andWhere(new \yii\db\Expression('FIND_IN_SET(:room_ids,room_ids)'))
            ->addParams([':room_ids' => $id])
            ->orderBy(['online_status' => SORT_DESC])
            ->asArray()
            ->all();

        if ($members) {
            foreach ($members as $key => $value) {
                $return_data['members'][$key]['username'] = $value['mobile'];
                $return_data['members'][$key]['id'] = 'member_' . $value['id'];
                $return_data['members'][$key]['avatar'] = ImageHelper::defaultHeaderPortrait($value['head_portrait']);
                $return_data['members'][$key]['status'] = $value['online_status'] ? 'online' : 'offline';
            }
        }
        return ResultHelper::json(0, '', $return_data);
    }

    /**
     *  获取最新昵称
     * @param $id
     * @return array
     */
    public function actionGetInfo($id)
    {
        $member = Member::findOne(explode('_', $id)[1]);
        $return_data = [
            'nickname' => $member->remark ?: $member->mobile,
            'avatar' => ImageHelper::defaultHeaderPortrait($member->head_portrait),
        ];
        return ResultHelper::json(200, 'ok', $return_data);
    }

    /**
     *  个人信息
     * @return string
     */
    public function actionInformation()
    {
        $id = explode('_', \Yii::$app->request->get('id'));
        $model = Member::findOne($id[1]);
        return $this->renderPartial('information', ['model' => $model]);
    }


    /**
     *  群聊开关
     * @return array
     */
    public function actionSwitchGag()
    {
        $id = \Yii::$app->request->post('id');
        $type = \Yii::$app->request->post('type');
        $model = Member::findOne($id);
        $model->gag_status = $type == 'true' ? 1 : 0;
        $identity = \Yii::$app->user->identity;
        $content = '会员 [' . $model->nickname . ']';
        $content .= $model->gag_status == Member::STATUS_GAG ? ' 被禁言' : ' 解除禁言';
        Gateway::sendToGroup($model->room_id, Json::encode([
            'id' => $model->room_id,
            'type' => 'cmd',
            'content' => $content,
            'group_type' => 'group',
            'time' => time(),
            'order' => 'gag',
            'uid' => "member_" . $model->id,
            'status' => $model->gag_status
        ]));
        return ResultHelper::json(200, $model->save() ? '修改成功' : '修改失败');
    }


    /**
     *  撤回消息
     * @return array
     */
    public function actionRevokeMessage()
    {
        $params = \Yii::$app->request->post();
        $identity = \Yii::$app->user->identity;
        $my_id = $identity->getId();
        $ex = explode('_', $params['room_id']);
        if (count($ex) == 1) {
            ChatLog::$room_id = $params['room_id'];
        } else {
            $member = Member::findOne($ex[1]);
            ChatLog::$room_id = $member->room_id;
        }
        $msg = ChatLog::findOne(['id' => $params['msg_id'], 'status' => ChatLog::STATUS_NORMAL]);
        if ($msg && $msg->from_id == $my_id && $msg->from_type == ChatLog::ROLE_TYPE_MANAGER) {
            if ($params['type'] == 'group') {
                // 群消息
                Gateway::sendToGroup($params['room_id'], Json::encode([
                    'id' => $params['room_id'],
                    'type' => 'cmd',
                    'group_type' => 'group',
                    'order' => 'revoke_message',
                    'msg_id' => $msg->id,
                    'content' => $identity->nickname . '撤回了一条消息',
                    'from_id' => 'manager_' . $my_id,
                ]));
            } else {
                // 私聊消息
                Gateway::sendToUid('member_' . $msg->to_id, Json::encode([
                    'id' => 'manager_' . $my_id,
                    'type' => 'cmd',
                    'group_type' => 'friend',
                    'order' => 'revoke_message',
                    'msg_id' => $msg->id,
                    'content' => $identity->nickname . '撤回了一条消息',
                    'from_id' => 'manager_' . $my_id
                ]));
                Gateway::sendToUid('manager_' . $my_id, Json::encode([
                    'id' => 'member_' . $msg->to_id,
                    'type' => 'cmd',
                    'group_type' => 'friend',
                    'order' => 'revoke_message',
                    'msg_id' => $msg->id,
                    'content' => '我撤回了一条消息',
                    'from_id' => 'manager_' . $my_id,
                    'to_id' => 'member_' . $msg->to_id
                ]));
            }
            $msg->status = ChatLog::STATUS_REVOKE;
            $msg->save();
            return ResultHelper::json(200, '操作成功');
        }
        return ResultHelper::json(400, '操作失败');
    }

    /**
     *  websocket重连，拉取消息
     */
    public function actionReconnect()
    {
        exit;
        $role_manager = ChatLog::ROLE_TYPE_MANAGER;
        $role_member = ChatLog::ROLE_TYPE_MEMBER;
        $identity = \Yii::$app->user->identity;
        $id = $identity->getId();
        $room_ids = $identity->room_ids;
        $disconnect_time = \Yii::$app->request->post('disconnect_time');
        $time = time();
        $connection = \Yii::$app->db;
        $to_info = [];
        if ($room_ids) {
            foreach ($room_ids as $room_id) {
                $table_name = 'rf_chat_log_' . $room_id;
                // 群消息
                $innerJoin = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) and to_id = 0 and to_type = 0 and created_at between {$disconnect_time} and {$time} order by id desc limit 20) b using (id)";
                $sql = "SELECT a.id,a.from_id,a.from_type,a.to_id,a.to_type,a.content,a.created_at,a.msg_type FROM {$table_name} a inner join {$innerJoin} order by id";
                $command = $connection->createCommand($sql);
                $log = $command->queryAll();
                if ($log) {
                    foreach ($log as $k => $v) {
                        $role = $v['from_type'] == $role_manager ? 'manager_' : 'member_';
                        $send_data = [];
                        $send_data['cid'] = $v['id'];
                        $send_data['time'] = $v['created_at'];
                        $send_data['type'] = 'text';
                        $send_data['from_id'] = $role . $v['from_id'];
                        $send_data['content'] = $v['content'];
                        $send_data['msg_type'] = $v['msg_type'];
                        if ($v['from_type'] == $role_manager && $v['from_id'] == $id) {
                            // 我发出的
                            continue;
                        } else {
                            $_id = $v['from_type'] . '_' . $v['from_id'];
                            if (!isset($to_info[$_id])) {
                                if ($v['from_type'] == $role_manager) {
                                    $model = \common\models\backend\Member::find()->alias('a')
                                        ->select('a.id,a.nickname,a.head_portrait');
                                } else if ($v['from_type'] == $role_member) {
                                    $model = Member::find()->alias('a');
//                                        ->select('a.id,a.nickname,a.remark,a.head_portrait,b.name as level')
//                                        ->join('LEFT JOIN', 'dj_seller_level b', 'a.level_id=b.id');
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
                            $send_data['username'] = $to_info[$_id] ? isset($to_info[$_id]['remark']) ? $to_info[$_id]['remark'] : $to_info[$_id]['nickname'] : $to_info[$_id]['nickname'];
                            $send_data['id'] = $room_id;
                            $send_data['avatar'] = ImageHelper::defaultHeaderPortrait($to_info[$_id]['head_portrait']);
//                            $send_data['level'] = isset($to_info[$_id]['level']) ? $to_info[$_id]['level'] : '';
                            $send_data['level'] = '会员';
                        }
                        Gateway::sendToUid('manager_' . $id, Json::encode($send_data));
                    }
                }
            }
        }

        // 私聊消息
        $table_name = ChatLog::tableName();
        $innerJoin2 = "(SELECT id FROM {$table_name} b WHERE ( `status` = 1 ) AND to_id = {$id} and to_type = {$role_manager} and created_at between {$disconnect_time} and {$time} order by id desc limit 20) b using (id)";
        $sql2 = "SELECT a.id,a.from_id,a.from_type,a.to_id,a.to_type,a.content,a.created_at,a.msg_type FROM {$table_name} a inner join {$innerJoin2} order by id";
        $command2 = $connection->createCommand($sql2);
        $log2 = $command2->queryAll();
        if ($log2) {
            foreach ($log2 as $k => $v) {
                $role = $v['from_type'] == $role_manager ? 'manager_' : 'member_';
                $send_data = [];
                $send_data['cid'] = $v['id'];
                $send_data['time'] = $v['created_at'];
                $send_data['type'] = 'text';
                $send_data['from_id'] = $role . $v['from_id'];
                $send_data['content'] = $v['content'];
                $send_data['msg_type'] = $v['msg_type'];
                if ($v['from_type'] == $role_manager && $v['from_id'] == $id) {
                    // 我发出的
                    continue;
                } else {
                    $_id = $v['from_type'] . '_' . $v['from_id'];
                    if (!isset($to_info[$_id])) {
                        if ($v['from_type'] == $role_manager) {
                            $model = \common\models\backend\Member::find()->alias('a')
                                ->select('a.id,a.nickname,a.remark,a.head_portrait');
                        } else if ($v['from_type'] == $role_member) {
                            $model = Member::find()->alias('a');
//                                ->select('a.id,a.nickname,a.remark,a.head_portrait,b.name as level')
//                                ->join('LEFT JOIN', 'dj_seller_level b', 'a.level_id=b.id');
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
                    $send_data['username'] = $to_info[$_id]['remark'] ?: $to_info[$_id]['nickname'];
                    $send_data['id'] = $role . $v['from_id'];
                    $send_data['avatar'] = ImageHelper::defaultHeaderPortrait($to_info[$_id]['head_portrait']);
//                    $send_data['level'] = isset($to_info[$_id]['level']) ? $to_info[$_id]['level'] : '';
                    $send_data['level'] = '会员';
                }
                Gateway::sendToUid('manager_' . $id, Json::encode($send_data));
            }
        }
    }

    /**
     * @人带昵称搜索条件
     * @param $name
     * @return array
     */
    public function actionAtOne($name, $id)
    {
        $where = [];
        $limit = 2;
        if ($name) {
            $where = ['like', 'nickname', $name];
            $limit = 20;
        }
        $member = Member::find()
            ->select('concat("member_",`id`) as id,nickname,head_portrait as avatar')
            ->where(['status' => Member::STATUS_ACTIVE])
            ->andWhere(new \yii\db\Expression('FIND_IN_SET(:room_ids,room_ids)'))
            ->addParams([':room_ids' => $id])
            ->andWhere($where)
            ->limit($limit)
            ->asArray()
            ->all();
        $manager = \common\models\backend\Member::find()
            ->select('concat("manager_",`id`) as id,nickname,head_portrait as avatar')
            ->where(['status' => \common\models\backend\Member::STATUS_ACTIVE, 'is_contact' => 1])
            ->andWhere(new \yii\db\Expression('FIND_IN_SET(:room_ids,room_ids)'))
            ->addParams([':room_ids' => $id])
            ->andWhere($where)
            ->andWhere(['<>', 'id', \Yii::$app->user->identity->getId()])
            ->limit($limit)
            ->asArray()
            ->all();
        $list = array_merge($member, $manager);
        return ResultHelper::json(0, 'ok', $list);
    }

    /**
     *  屏蔽消息
     * @return array
     */
    public function actionShieldMessage()
    {
        $msg_id = \Yii::$app->request->post('msg_id');
        $room_id = \Yii::$app->request->post('room_id');
        ChatLog::$room_id = $room_id;
        $msg = ChatLog::findOne(['id' => $msg_id, 'status' => ChatLog::STATUS_NORMAL]);
        if ($msg) {
            Gateway::sendToGroup($room_id, Json::encode([
                'id' => $room_id,
                'type' => 'cmd',
                'group_type' => 'group',
                'time' => time(),
                'order' => 'shield_message',
                'msg_id' => $msg->id,
            ]));
            $msg->status = ChatLog::STATUS_SHIELD;
            $msg->save();
            return ResultHelper::json(200, '操作成功');
        }
        return ResultHelper::json(400, '操作失败');
    }

    /**
     *  获取我关联的房间信息
     */
    public function actionGetLinkRoom()
    {
        $identity = \Yii::$app->user->identity;
        $return_data = [];
        if ($identity->room_ids) {
            $return_data = Room::find()->where(['in', 'id', $identity->room_ids])->andWhere(['in', 'status', [1, 2]])->select('id,name,remark')->asArray()->all();
        }
        return ResultHelper::json(200, 'ok', $return_data);
    }

    /**
     *  查找会员
     * @return array
     */
    public function actionFindMember()
    {
        $identity = Yii::$app->user->identity;
        if ($identity->getId() != 1) {
            $b_member = Member::find()->where(['b_id' => $identity->getId()])->select(['id'])->asArray()->one();
            $b_ids = Member::getChildrenIds($b_member['id']);
            $b_ids[] = $b_member['id'];
            $where = ['in', 'pid', $b_ids];
        } else {
            $where = [];
        }
        $name = \Yii::$app->request->post('name');
        $members = Member::find()
            ->where(['like', 'mobile', $name])
            ->andWhere($where)
            ->select(['id', 'head_portrait', 'mobile', 'online_status'])
            ->asArray()
            ->limit(10)
            ->all();
        $return_data = [];
        if ($members) {
            foreach ($members as $k => $member) {
                $return_data[$k]['id'] = $member['id'];
                $return_data[$k]['avatar'] = ImageHelper::defaultHeaderPortrait($member['head_portrait']);
                $return_data[$k]['username'] = $member['mobile'];
                $return_data[$k]['sign'] = "";
                $return_data[$k]['status'] = $member['online_status'] ? 'online' : 'offline';
                $return_data[$k]['group_id'] = 0;
            }
        }
        return ResultHelper::json(200, 'ok', $return_data);
    }
}

<?php

namespace common\models\tea;
use common\models\base\BaseModel;
use common\models\member\Member;
use Yii;
/**
 * This is the model class for table "rf_level".
 *
 * @property string $id
 * @property string $content 内容
 * @property int $from_id 发送人id
 * @property int $from_type 发送人类型，1后台人员，2会员
 * @property int $to_id 接收人id
 * @property int $to_type 接收人类型，1后台人员，2会员
 * @property int $status 1正常，2撤回，3屏蔽
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $manager_id 管理员id
 * @property int $is_read 0未读，1已读
 * @property int $msg_type 消息类型，1文本，2图片，3红包，4语音
 */
class ChatLog extends BaseModel
{
    const ROLE_TYPE_MANAGER = 1;
    const ROLE_TYPE_MEMBER = 2;
    const UNREAD = 0;
    const READ = 1;
    const TYPE_TEXT = 1;
    const TYPE_IMG = 2;
    const TYPE_GROUP = 'group';
    const TYPE_FRIEND = 'friend';
    const STATUS_NORMAL = 1;
    const STATUS_REVOKE = 2;
    const STATUS_SHIELD = 3;
    const MSG_TEXT = 1;
    const MSG_IMG = 2;

    public static $msgTypeExplain = [
        self::MSG_TEXT => '文本',
        self::MSG_IMG => '图片',
    ];
    public static $isReadExplain = [
        self::UNREAD => '未读',
        self::READ => '已读',
    ];
    public static $statusExplain = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_REVOKE => '撤回',
        self::STATUS_SHIELD => '屏蔽',
    ];
    public static $toTypeExplain = [
        self::ROLE_TYPE_MANAGER => '后台',
        self::ROLE_TYPE_MEMBER => '会员',
    ];
    public static $fromTypeExplain = [
        self::ROLE_TYPE_MANAGER => '后台',
        self::ROLE_TYPE_MEMBER => '会员',
    ];

    public static $room_id = '';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        if (self::$room_id) {
            return 'rf_chat_log_' . self::$room_id;
        }else{
            return 'rf_chat_log_0';
        }
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'content' => '内容',
            'from_id' => '发送人',
            'from_type' => '发送人类型',
            'to_id' => '接收人',
            'to_type' => '接收人类型',
            'status' => '状态',
            'created_at' => '发送时间',
            'updated_at' => '更新时间',
            'is_read' => '已读未读',
            'msg_type' => '消息类型',
        ];
    }

    /**
     *  保存消息记录
     * @param $content
     * @param $from_type
     * @param int $to
     * @param int $to_type
     * @param int $msg_type
     * @return string
     */
    public static function saveChatLog($content, $from_type, $to = 0, $to_type = 0, $msg_type = 1)
    {
        $identity = \Yii::$app->user->identity;
        if (\Yii::$app->request->getBaseUrl() == '/api') {
            $identity = $identity->member;
        }
        $model = new self();
        $model->content = $content;
        $model->from_id = $identity->getId();
        $model->from_type = $from_type;
        $model->to_id = $to;
        $model->to_type = $to_type;
        $model->msg_type = $msg_type;
        if ($to == 0) {
            $model->is_read = self::READ;
        }
        $model->save();
        return $model->id;
    }

    /**
     *  设置消息为已读
     * @param $from_id
     * @param $from_type
     * @param int $to_type
     */
    public static function setMsgRead($from_id, $from_type, $to_type = 0)
    {
        if ($to_type != 0) {
            $condition = ['and',
                ['from_id' => $from_id],
                ['from_type' => $from_type],
                ['to_type' => $to_type]
            ];
            self::updateAll(['is_read' => self::READ], $condition);
        }
    }


    /**
     *  获取接收人信息(管理员)
     * @return \yii\db\ActiveQuery
     */
    public function getReceiverManager()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id' => 'to_id']);
    }

    /**
     *  获取接收人信息(会员)
     * @return \yii\db\ActiveQuery
     */
    public function getReceiverMember()
    {
        return $this->hasOne(Member::class, ['id' => 'to_id']);
    }


    /**
     *  获取发送人信息(管理员)
     * @return \yii\db\ActiveQuery
     */
    public function getSenderManager()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id' => 'from_id']);
    }

    /**
     *  获取接收人信息(会员)
     * @return \yii\db\ActiveQuery
     */
    public function getSenderMember()
    {
        return $this->hasOne(Member::class, ['id' => 'from_id']);
    }
}
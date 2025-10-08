<?php

namespace common\models\tea;

use common\helpers\GatewayInit;
use common\models\base\User;
use common\models\member\Member;
use GatewayClient\Gateway;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "dk_room".
 *
 * @property int $id
 * @property string $name 群聊名
 * @property int $created_at 创建时间
 * @property string $remark 备注
 * @property string $avatar 头像
 * @property int $status 状态
 */
class Room extends \yii\db\ActiveRecord
{

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
        ];
    }

    const STATUS_DISABLED = 0;
    const STATUS_NORMAL = 1;
    const STATUS_GAG = 2;

    /**
     * @var array
     */
    public static $statusExplain = [
        self::STATUS_DISABLED => '禁用',
        self::STATUS_NORMAL => '正常',
        self::STATUS_GAG => '禁言',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'dk_room';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'status'], 'integer'],
            [['name', 'remark', 'avatar'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => '群聊名',
            'created_at' => '创建时间',
            'remark' => '备注',
            'avatar' => '头像',
            'status' => '状态',
        ];
    }


    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $this->createTable($this->id);
            $backend_member = \common\models\backend\Member::findOne(['id' => 1]);
            $room_ids = !empty($backend_member->room_ids) ? $backend_member->room_ids : [];
            $room_ids[] = $this->id;
            $backend_member->room_ids = $room_ids;
            $backend_member->save();
            //socket加群
            GatewayInit::initBase();
            $client_id = Gateway::getClientIdByUid("manager_1");
            if (!empty($client_id)) {
                Gateway::joinGroup($client_id[0], $this->id);
            }
        }
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     *  一对多获取房间会员
     * @return \yii\db\ActiveQuery
     */
    public function getMember()
    {
        return $this->hasMany(Member::class, ['room_id' => 'id']);
    }

    /**
     *  获取在线会员
     * @return \yii\db\ActiveQuery
     */
    public function getOnlineMember()
    {
        return $this->getMember()->where(['online_status' => User::ONLINE]);
    }

    /**
     *  创建聊天记录表
     * @param $room_id
     * @return int
     * @throws \yii\db\Exception
     */
    private function createTable($room_id)
    {
        $table_name = Yii::$app->db->tablePrefix . 'chat_log_' . $room_id;
        $sql = "CREATE TABLE `{$table_name}` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `content` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT '' COMMENT '内容',
  `from_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '发送人id',
  `from_type` tinyint(2) UNSIGNED NULL DEFAULT 1 COMMENT '发送人类型，1后台人员，2会员',
  `to_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '接收人id',
  `to_type` tinyint(2) UNSIGNED NULL DEFAULT 1 COMMENT '接收人类型，1后台人员，2会员',
  `status` tinyint(2) UNSIGNED NULL DEFAULT 1 COMMENT '1正常，2撤回，3屏蔽',
  `created_at` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '创建时间',
  `updated_at` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '更新时间',
  `manager_id` int(10) UNSIGNED NULL DEFAULT 0 COMMENT '管理员id',
  `is_read` tinyint(2) UNSIGNED NULL DEFAULT 0 COMMENT '0未读，1已读',
  `msg_type` tinyint(2) UNSIGNED NULL DEFAULT 1 COMMENT '消息类型，1文本，2图片',
  PRIMARY KEY (`id`),
  INDEX `from_index`(`from_id`,`from_type`) USING BTREE,
  INDEX `to_index`(`to_id`,`to_type`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_bin;";
        $con = Yii::$app->db;
        $command = $con->createCommand($sql);
        return $command->execute();
    }
}

<?php

namespace common\models\member;

use common\enums\CacheEnum;
use common\helpers\DateHelper;
use common\helpers\ImageHelper;
use common\helpers\StringHelper;
use common\models\api\AccessToken;
use common\models\common\Statistics;
use common\models\dj\Orders;
use common\models\dj\SellerLevel;
use common\models\forms\CreditsLogForm;
use common\models\tea\LastContact;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use common\helpers\HashidsHelper;
use common\enums\StatusEnum;
use common\models\base\User;
use common\helpers\RegularHelper;
use common\traits\Tree;

/**
 * This is the model class for table "{{%member}}".
 *
 * @property int $id 主键
 * @property string $merchant_id 商户id
 * @property string $username 帐号
 * @property string $password_hash 密码
 * @property string $auth_key 授权令牌
 * @property string $password_reset_token 密码重置令牌
 * @property int $type 类别[1:普通会员;10管理员]
 * @property string $nickname 昵称
 * @property string $realname 真实姓名
 * @property string $head_portrait 头像
 * @property string $promo_code 推广码
 * @property int $current_level 当前级别
 * @property int $gender 性别[0:未知;1:男;2:女]
 * @property string $qq qq
 * @property string $email 邮箱
 * @property string $birthday 生日
 * @property string $visit_count 访问次数
 * @property string $home_phone 家庭号码
 * @property string $mobile 手机号码
 * @property int $role 权限
 * @property int $last_time 最后一次登录时间
 * @property string $last_ip 最后一次登录ip
 * @property int $province_id 省
 * @property int $city_id 城市
 * @property int $area_id 地区
 * @property string $pid 上级id
 * @property string $tree 树
 * @property string $level 级别
 * @property int $status 状态[-1:删除;0:禁用;1启用]
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 * @property string $remark 备注
 * @property string $register_ip 注册IP
 * @property int $drive 机型
 * @property int $sign_status 签到状态
 * @property int $sign_days 签到累计天数
 * @property string $identification_number 身份证号码
 * @property string $phone_identifier 手机唯一标识符
 * @property string $safety_password_hash 安全码
 * @property int $lottery_number 每日可抽奖次数
 * @property int $free_lottery_number 每日免费抽奖次数
 * @property int $investment_time 投资时间
 * @property int $investment_status 投资状态
 * @property int $online_status 在线状态
 * @property int $register_type 注册类型
 * @property int $realname_status 实名状态
 * @property string $principal 本金
 * @property string $register_url 注册链接
 * @property int $withdraw_switch 提现开关
 * @property int $return_recommend
 * @property int $return_sign_recommend
 * @property int $return_buy_recommend
 * @property string $withdraw_money
 * @property string $recharge_money
 * @property string $room_ids
 * @property int $b_id
 * @property int $vip_level
 * @property int $credit_score
 * @property int $automatic_delivery_switch
 * @property int $push_flow_switch
 * @property int $is_virtual
 */
class Member extends User
{
    use Tree;

    const STATUS_GAG = 0;

    public $safety_password;

    public static $sign_array = [1 => "已签", 0 => "未签",];
    public static $virtual_array = [0 => "真实用户", 1 => "虚拟用户",];

//    public static $typeExplain = [0 => '买家账号', 1 => "卖家账号", 2 => "代理账号", 3 => "虚拟账号"];
//    public static $typeExplain = [0 => '买家账号', 1 => "卖家账号", 2 => "代理账号"];
    public static $typeExplain = [1 => "会员", 2 => "代理账号"];

    public static $registerTypeExplain = [0 => "平台注册", 1 => "广点通1", 2 => "广点通2", 11 => "抖音1", 12 => "抖音2", 13 => "抖音3", 21 => "快手1"];

    public static $online_status_array = [1 => "在线", 0 => "离线",];

//    public static $realname_status_array = [1 => "已实名", 0 => "未实名",];
    public static $realname_status_array = [1 => "已认证", 0 => "未认证",];

    public static $investment_status_array = [0 => "从未购买", 1 => "在购用户", 2 => "未购用户"];

    public static $status_color_array = [0 => "danger", 1 => "success", 2 => "warning"];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%member}}';
    }


    public static $driveExplain = [
        1 => '安卓',
        2 => 'IOS',
        3 => 'WEB'
    ];

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['safety_password'], 'string', 'min' => 6, 'max' => 6],
            [['mobile', 'password_hash'], 'required', 'on' => ['backendCreate']],
            [['password_hash'], 'string', 'min' => 6, 'on' => ['backendCreate']],
            [['mobile'], 'unique', 'filter' => function (ActiveQuery $query) {
                return $query->andWhere(['>=', 'status', StatusEnum::DISABLED]);
            }, 'on' => ['backendCreate']],
            [['id', 'credit_score', 'automatic_delivery_switch', 'vip_level', 'lottery_number', 'free_lottery_number', 'current_level', 'level', 'merchant_id', 'type', 'gender', 'visit_count', 'role', 'last_time', 'province_id', 'city_id', 'area_id', 'pid', 'status', 'created_at', 'updated_at', 'drive', 'sign_days', 'sign_status', 'investment_time', 'investment_status', 'online_status', 'register_type', 'realname_status', 'withdraw_switch', 'return_recommend', 'return_sign_recommend',
                'return_buy_recommend', 'is_virtual', 'push_flow_switch'], 'integer'],
            [['birthday', 'room_ids', 'b_id'], 'safe'],
            [['remark', 'safety_password_hash', 'register_url'], 'string', 'max' => 255],
            [['username', 'qq', 'home_phone'], 'string', 'max' => 20],
            [['mobile'], 'string', 'max' => 30],
            [['password_hash', 'password_reset_token', 'head_portrait'], 'string', 'max' => 150],
            [['phone_identifier'], 'string', 'max' => 200],
            [['auth_key'], 'string', 'max' => 32],
            [['nickname', 'realname', 'promo_code'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 60],
            [['identification_number', 'last_ip', 'register_ip'], 'string', 'max' => 50],
            [['tree'], 'string', 'max' => 2000],
            [['b_id'], 'unique', 'message' => '该后台代理账号已被使用'],
            [['identification_number'], 'unique', 'message' => '该证件号码已被使用'],
//            ['mobile', 'match', 'pattern' => RegularHelper::mobile(), 'message' => '不是一个有效的手机号码'],
            [['mobile'], 'unique', 'filter' => function (ActiveQuery $query) {
                return $query
                    ->andWhere(['>=', 'status', StatusEnum::DISABLED])
                    ->andFilterWhere(['merchant_id' => Yii::$app->services->merchant->getMerchantId()]);
            }],
            [['mobile'], 'trim'],
            [['principal', 'recharge_money', 'withdraw_money'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => 'Merchant ID',
            'username' => '手机号码',
            'password_hash' => '密码',
            'auth_key' => '授权登录key',
            'password_reset_token' => '密码重置token',
            'type' => '类型',
            'nickname' => '昵称',
            'realname' => '真实姓名',
            'head_portrait' => '头像',
            'current_level' => '当前级别',
            'gender' => '性别',
            'qq' => 'QQ',
            'email' => '邮箱',
            'birthday' => '生日',
            'visit_count' => '登录总次数',
            'home_phone' => '家庭号码',
            'mobile' => '账号',
            'role' => '权限',
            'last_time' => '最后一次登录时间',
            'last_ip' => '最后一次登录ip',
            'province_id' => 'Province ID',
            'city_id' => 'City ID',
            'area_id' => 'Area ID',
            'pid' => '上级id',
            'level' => '级别',
            'promo_code' => '推广码',
            'tree' => '树',
            'status' => '状态',
            'created_at' => '创建时间',
            'updated_at' => '修改时间',
            'remark' => '备注',
            'drive' => '机型',
            'sign_days' => '签到天数',
            'sign_status' => '签到状态',
            'identification_number' => '证件号码',
            'phone_identifier' => '手机标识符',
            'lottery_number' => '每日可抽奖次数',
            'free_lottery_number' => '免费抽奖次数',
            'safety_password_hash' => '安全码',
            'safety_password' => '安全密码',
            'investment_time' => '购买时间',
            'investment_status' => '购买状态',
            'online_status' => '在线状态',
            'register_type' => '注册类型',
            'realname_status' => '实名状态',
            'register_ip' => '注册IP',
            'principal' => '本金',
            'register_url' => '注册链接',
            'withdraw_switch' => '提现开关',
            'return_recommend' => '返佣状态',
            'return_sign_recommend' => '签到返佣状态',
            'return_buy_recommend' => '购买返佣状态',
            'recharge_money' => '充值金额',
            'withdraw_money' => '提现金额',
            'b_id' => '后台代理',
            'vip_level' => 'VIP等级',
            'credit_score' => '信用分',
            'automatic_delivery_switch' => '自动发货',
            'is_virtual' => '虚拟状态',
            'push_flow_switch' => '推流状态',
        ];
    }

    /**
     * 场景
     *
     * @return array
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['backendCreate'] = ['mobile', 'password_hash', 'type', 'b_id', 'pid'];

        return $scenarios;
    }

    /**
     * 关联账号
     */
    public function getAccount()
    {
        return $this->hasOne(Account::class, ['member_id' => 'id']);
    }

    /**
     * 关联级别
     */
    public function getMemberLevel()
    {
        return $this->hasOne(Level::class, ['level' => 'current_level']);
    }

    /**
     * 关联级别
     */
    public function getSellerLevel()
    {
        return $this->hasOne(SellerLevel::class, ['level' => 'vip_level']);
    }

    /**
     * 获取下级人数
     * @param $number
     * @param $members
     * @return int|mixed
     */
    public static function getMemberNumber($number, $members)
    {
        if (!empty($members)) {
            foreach ($members as $member) {
                $number += 1;
                $children_all = Member::find()->select(['id'])->where(['pid' => $member['id']])->asArray()->all();
                if (empty($children_all)) {
                    continue;
                } else {
                    $number = self::getMemberNumber($number, $children_all);
                }
            }
        }
        return $number;
    }

    /**
     * 关联第三方绑定
     */
    public function getAuth()
    {
        return $this->hasMany(Auth::class, ['member_id' => 'id'])->where(['status' => StatusEnum::ENABLED]);
    }

    /**
     * @param bool $insert
     * @return bool
     * @throws \yii\base\Exception
     */
    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $this->auth_key = Yii::$app->security->generateRandomString();
//            $this->nickname = StringHelper::random(5) . '_' . substr($this->mobile, -4);
//            $this->nickname = StringHelper::random(5) . '_' . substr($this->mobile, 4);
            $this->nickname = StringHelper::random(6);
            $this->register_ip = Yii::$app->request->getUserIP();
            $this->free_lottery_number = Yii::$app->debris->config('free_lottery_number') ?? 0;
            $this->lottery_number = Yii::$app->debris->config('lottery_number') ?? 0;
            $this->promo_code = self::getInviteCode(6);
            // 初始化安全码
//            $this->safety_password_hash = Yii::$app->security->generatePasswordHash(mb_substr($this->mobile, -6));
            $this->safety_password_hash = Yii::$app->security->generatePasswordHash("123456");
            // 邀请码设置成手机号
//            $this->promo_code = $this->mobile;
            // 注册链接
            $this->register_url = Yii::$app->request->hostName;
        }
        if ($this->room_ids && is_array($this->room_ids)) {
            $this->room_ids = implode(',', $this->room_ids);
        }

        // 处理上下级关系
        $this->autoUpdateTree();

        return parent::beforeSave($insert);
    }

    public function afterFind()
    {
        if ($this->room_ids) {
            $this->room_ids = explode(',', $this->room_ids);
        }
        parent::afterFind();
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {
            $account = new Account();
            $account->member_id = $this->id;
            $account->merchant_id = $this->merchant_id;
            $account->save();
//            empty($this->promo_code) && Member::updateAll(['promo_code' => HashidsHelper::encode($this->id)], ['id' => $this->id]);

            if ($this->type == 1) {
                // 加入统计表 获取最上级用户ID
                $first_member = self::getParentsFirst(Member::findOne($this->id));
                $b_id = $first_member['b_id'] ?? 0;
                Statistics::updateRegisterMember(date("Y-m-d"), $b_id);
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * 获取最上级
     * @param $this_member
     * @return array
     */
    public static function getParentsFirst($this_member)
    {
        if ($this_member['pid']) {
            $p_member = Member::find()->select(['id', 'pid', 'b_id'])->where(['id' => $this_member['pid']])->asArray()->one();
            return self::getParentsFirst($p_member);
        }
        return $this_member;
    }

    /**
     * 递归获取值
     * @param $number
     * @return string
     */
    public static function getInviteCode($number)
    {
        $promo_code = self::make_coupon_card($number);
        if (self::find()->select(['id'])->where(['promo_code' => $promo_code])->exists()) {
            return self::getInviteCode($number);
        } else {
            return $promo_code;
        }
    }

    /**
     * 生成随机数
     * @param $length
     * @return string
     * @author 原创脉冲
     */
    public static function make_coupon_card($length)
    {
        $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand = $code[rand(0, 25)]
            . strtoupper(dechex(date('m')))
            . date('d') . substr(time(), -5)
            . substr(microtime(), 2, 5)
            . sprintf('%02d', rand(0, 99));
        for (
            $a = md5($rand, true),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < $length;
            $g = ord($a[$f]),
            $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F],
            $f++
        ) ;
        return $d;
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        $merchant_id = Yii::$app->services->merchant->getId();

        return [
            [
                'class' => TimestampBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
            ],
            [
                'class' => BlameableBehavior::class,
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['merchant_id'],
                ],
                'value' => !empty($merchant_id) ? $merchant_id : 0,
            ]
        ];
    }

    public function getParent()
    {
        return $this->hasOne(self::class, ['id' => 'pid']);
    }

    /**
     * 获取上级
     * @param $member
     * @return array
     */
    public static function getParents($member)
    {
        $parents[] = $member;
        if ($member['pid']) {
            self::findParent($member['pid'], $parents);
        }
        return $parents;
    }

    /**
     *  递归获取上级
     * @param $id
     * @param $parents
     */
    private static function findParent($id, &$parents)
    {
        $parent = self::find()->with(['account' => function ($query) {
            return $query->select('user_money');
        }])->select('id,mobile as title,pid,status')->where(['id' => $id])->asArray()->one();
        if ($parent) {
            $parent['title'] .= '(余额:' . $parent['account']['user_money'] . ')';
            $parents[] = $parent;
            if ($parent['pid']) {
                self::findParent($parent['pid'], $parents);
            }
        }
    }


    /**
     * 获取用户信息
     * @param $member_id
     * @return array|null|ActiveRecord
     */
    public static function getMemberInfoByMemberId($member_id, $lang = "cn")
    {
        $memberInfo = Member::find()
            ->where(['id' => $member_id])
            ->select(['id', 'mobile', 'automatic_delivery_switch', 'push_flow_switch', 'nickname', 'realname', 'head_portrait', 'current_level', 'promo_code', 'sign_days', 'identification_number', 'created_at', 'type', 'vip_level', 'credit_score', 'register_type','username'])
            ->with([
                'account' => function ($query) {
                    $query->select([
                        'user_money',
                        'user_integral',
                        'recommend_number',
                        'usdt_link',
                        'alipay_account',
                        'alipay_user_name',
                        'investment_income',
                        'investment_all_money',
                        'investment_number',
                        'recommend_money',
                        'recommend_number',
                        'contract_profit',
                        'non_contractual_profit',
                        'platform_account',
                        'platform_name',
                        'gcash_name',
                        'gcash_phone',
                        'maya_name',
                        'maya_phone',
                    ]);
                },
                'memberLevel' => function ($query) {
                    $query->select([
                        'name',
                    ]);
                },
                'card',
//                'realName',
//                'signIns' => function ($query) {
//                    $query->select([
//                        'id',
//                        'member_id',
//                        'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d\') as created_at',
//                    ])
//                        ->where(['>', 'created_at', strtotime(date('Y-m-d', strtotime("-6 day")))]);
//                }
            ])
            ->asArray()
            ->one();
//        $today = DateHelper::today();
//        $memberInfo['today_recommend_number'] = self::find()
//                ->where(['pid' => $member_id])
//                ->andWhere(['between', 'created_at', $today['start'], $today['end']])
//                ->count() ?? 0;
        // 拉取公告
//        Yii::$app->services->memberNotify->pullAnnounce($member_id, $memberInfo['created_at']);
//        Yii::$app->services->memberNotify->pullAnnounce($member_id);
        // 是否有站内信未阅读
        $notify = NotifyMember::find()
            ->select(['id', 'notify_id'])
            ->where(['member_id' => $member_id, 'is_read' => StatusEnum::DISABLED])
            ->with(['notifySend' => function ($query) {
                $query->select(['id', 'title']);
            }])
            ->orderBy(['created_at' => SORT_DESC])
            ->asArray()
            ->one();
        if (!empty($notify)) {
            $memberInfo['notify'] = ['notify_id' => $notify['id'], 'title' => $notify['notifySend']['title']];
        }
        return $memberInfo;
    }

    /**
     * @param $access_token
     * @return string
     */
    protected function getCacheKey($access_token)
    {
        return CacheEnum::getPrefix('apiAccessToken') . $access_token;
    }

    /**
     * 关联昨天的签到记录
     * @author 原创脉冲
     */
    public function getYesterdaySign()
    {
        // 首先拿到昨天的时间戳
        $yesterday = DateHelper::yesterday();
//        return $this->hasOne(CreditsLog::class, ['member_id' => 'id'])
//            ->where(['between', 'created_at', $yesterday['start'], $yesterday['end']])
//            ->andWhere(['pay_type' => CreditsLog::SIGN_TYPE]);
        return $this->hasOne(SignIn::class, ['member_id' => 'id'])
            ->where(['between', 'created_at', $yesterday['start'], $yesterday['end']]);
    }

    /**
     * 关联上级推荐人
     */
    public function getRecommendMember()
    {
        return $this->hasOne(Member::class, ['id' => 'pid']);
    }

    /**
     * 关联代理后台
     */
    public function getBMember()
    {
        return $this->hasOne(\common\models\backend\Member::class, ['id' => 'b_id']);
    }

    /**
     * 今日已获收益
     * @return ActiveQuery
     */
    public function getTodayIncome()
    {
        $today = DateHelper::today();
        return $this->hasMany(CreditsLog::class, ['member_id' => 'id'])
            ->where(['between', 'created_at', $today['start'], $today['end']])
            ->andWhere(['pay_type' => CreditsLog::INCOME_TYPE, 'credit_type' => 'user_money']);
    }

    /**
     * 获取银行卡列表
     * @return ActiveQuery
     */
    public function getCard()
    {
        return $this->hasMany(MemberCard::class, ['member_id' => 'id']);
    }

    /**
     *  返回在线状态
     * @param $id
     * @return string
     */
    public static function getOnlineStatus($id)
    {
        $id = explode('_', $id);
        $status = self::find()->select('online_status')->where(['id' => $id[1]])->asArray()->one();
        return $status['online_status'] ? 'online' : 'offline';
    }

    /**
     *  获取layim所需会员格式数据
     * @param $identity
     * @param $room
     * @return array
     * @throws \yii\db\Exception
     */
    public static function memberLevelList($identity)
    {
        $room_group = [];
        $members = LastContact::find()
            ->select('uid,type,last_content,unread_count')
            ->with(['userInfo' => function ($q) {
                $q->select('id,mobile,remark,online_status,head_portrait');
            }])
            ->where(['mid' => $identity->id])
            ->orderBy('last_time desc')
            ->asArray()
            ->all();
        if ($members) {
            foreach ($members as $key => $value) {
                $room_group[$key]['id'] = 'member_' . $value['uid'];
                $room_group[$key]['username'] = $value['userInfo']['remark'] ?: $value['userInfo']['mobile'];
                $room_group[$key]['status'] = $value['userInfo']['online_status'] ? 'online' : 'offline';
                $room_group[$key]['avatar'] = ImageHelper::defaultHeaderPortrait($value['userInfo']['head_portrait']);
                $room_group[$key]['roleType'] = 'member';
                $room_group[$key]['unReadCount'] = $value['unread_count'];
                $room_group[$key]['sign'] = $value['last_content'] ?: "";
                $room_group[$key]['level'] = $value['userInfo']['level'] ? $value['userInfo']['level']['name'] : '';
            }
        }
        return $room_group;
    }

    /**
     * 获取第几级会员ID
     * @param $this_member
     * @param $ids
     * @return array
     */
    public static function getParentsNumber($this_member, $number = 1, $ids = [])
    {
        if ($this_member['pid']) {
            $ids[] = $this_member['pid'];
            $p_member = Member::find()->select(['id', 'pid'])->where(['id' => $this_member['pid']])->asArray()->one();
            return self::getParentsNumber($p_member, $number, $ids);
        }
        return $ids[count($ids) - $number];
    }

    /**
     * 获取下级IDS
     * @param $id
     * @param $ids
     * @return array|mixed
     */
    public static function getChildrenIds($id, $ids = [])
    {
        $childrenIds = Member::find()->where(['pid' => $id])->select(['id'])->all();
        foreach ($childrenIds as $childrenId) {
            $ids[] = $childrenId['id'];
            $ids = self::getChildrenIds($childrenId['id'], $ids);
        }
        return $ids;
    }

    public function getSignIns()
    {
        return $this->hasMany(SignIn::class, ['member_id' => 'id']);
    }

    public function getRealName()
    {
        return $this->hasOne(RealnameAudit::class, ['member_id' => 'id'])->orderBy(['id' => SORT_DESC]);
    }

    public function getOrders()
    {
        return $this->hasMany(Orders::class, ['member_id' => 'id']);
    }


    public static function getEmail($number)
    {
        $sz = range("0", "9");
        $zm = range("a", "z");
        $dx = range("A", "Z");
        $all = array_merge($sz, $zm);
        $all = array_merge($all, $dx);
        $email_array = ['gmail', 'hotmail', 'yahoo', 'outlook', 'live', 'qq', 'foxmail', 'sina', '163', '126', 'live', 'aol', 'icloud', 'protonmail', 'zohomail'];
        $email = $email_array[array_rand($email_array)];
        $result = implode("", array_rand(array_flip($all), $number)) . "@" . $email . ".com";
        if (Member::find()->where(['mobile' => $result])->exists()) {
            self::getEmail($number);
        }
        return $result;
    }
}

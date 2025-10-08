<?php
// +----------------------------------------------------------------------------------------
// | 原创项目
// +----------------------------------------------------------------------------------------
// | 版权所有 原创脉冲工作室
// +----------------------------------------------------------------------------------------
// |  联系方式：
// |  QQ：123546
// |  skype：123546
// |  Telegram：@123546
// +----------------------------------------------------------------------------------------
// | 开发团队:原创脉冲
// +----------------------------------------------------------------------------------------

namespace api\modules\v1\forms\member;


use common\models\member\Account;
use yii\base\Model;
use Yii;

class AccountForm extends Model
{
    public $alipay_account;
    public $alipay_account_url;
    public $alipay_user_name;
    public $wechat_account;
    public $wechat_account_url;
    public $scenario_type;
    public $bank_card;
    public $bank_address;
    public $usdt_link;


    public static $scenario_type_array = [
        'wechat_account',
        'alipay_account',
        'wechat_account_url',
        'alipay_account_url',
        'bank_card',
        'usdt_link',
    ];


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['scenario_type'], 'required'],
//            [['alipay_account', 'alipay_user_name'], 'required', 'on' => 'alipay_account'],
            [['alipay_account'], 'required', 'on' => 'alipay_account'],
            [['wechat_account'], 'required', 'on' => 'wechat_account'],
            [['bank_card', 'bank_address'], 'required', 'on' => 'bank_card'],
            [['wechat_account_url'], 'required', 'on' => 'wechat_account_url'],
            [['usdt_link'], 'required', 'on' => 'usdt_link'],
            [['alipay_account_url'], 'required', 'on' => 'alipay_account_url'],
            [['alipay_account', 'alipay_user_name', 'wechat_account'], 'string', 'max' => 50],
            [['wechat_account_url', 'alipay_account_url', 'bank_card', 'bank_address'], 'string', 'max' => 255],
            [
                ['alipay_account'],
                'unique',
                'targetClass' => Account::class,
                'targetAttribute' => 'alipay_account',
                'message' => '该支付宝账号已被绑定。',
                'on' => 'alipay_account',
            ],
            [
                ['wechat_account'],
                'unique',
                'targetClass' => Account::class,
                'targetAttribute' => 'wechat_account',
                'message' => '该微信号已被绑定。',
                'on' => 'wechat_account'
            ],
            [
                ['usdt_link'],
                'unique',
                'targetClass' => Account::class,
                'targetAttribute' => 'usdt_link',
                'message' => '该USDT地址已被绑定。',
                'on' => 'usdt_link'
            ],
            [
                ['bank_card'],
                'unique',
                'targetClass' => Account::class,
                'targetAttribute' => 'bank_card',
                'message' => '该银行卡已被绑定。',
                'on' => 'bank_card'
            ],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'alipay_account' => '支付宝账号',
            'alipay_user_name' => '支付宝账户名',
            'wechat_account' => '微信账号',
            'wechat_account_url' => '微信收款码',
            'scenario_type' => '类型',
            'bank_card' => '银行卡',
            'bank_address' => '开户行',
            'usdt_link' => 'USDT地址',
        ];
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 16:48
 */

namespace api\modules\v1\controllers\common;


use api\controllers\OnAuthController;
use common\helpers\ArrayHelper;
use common\models\member\Member;
use common\models\member\WithdrawBill;
use Yii;
use yii\db\Expression;
use yii\helpers\Json;

class ConfigController extends OnAuthController
{
    public $modelClass = '';
    // 不用进行登录验证的方法
    protected $authOptional = ['index'];
    // 不用进行签名验证的方法
    protected $signOptional = ['index'];

    /**
     * @return array|\yii\data\ActiveDataProvider
     */
    public function actionIndex()
    {
        $field = Yii::$app->request->get('field');
        $field = explode(',', $field);
        // 只能查询的字段
        $field = array_intersect($field, array_keys([
            // 基础配置
            'lottery_description' => '摇奖说明',
            'socket_link' => 'socket地址',
            'web_site_title' => '站点标题(APP名称)',
            'web_logo' => '站点logo',
            'customer_service_link' => '在线客服',
            // 注册配置
            'sms_switch' => '短信注册开关，1开,0关',
            'promo_code_switch' => '邀请码注册开关，1开,0关',
            // 提现配置
            'minimum_withdraw_amount' => '最低提现额度',
            'can_withdraw_amount' => '提现金额列表',
            'withdraw_method' => '提现方式,1:微信收款码,2:微信红包,3:转账支付宝,4:支付宝收款码,5:转账银行卡',
            // 充值配置
            'minimum_recharge_amount' => '最低充值额度',
            'can_recharge_amount' => '充值金额列表',
            'recharge_method' => '充值方式,2 => "支付宝WAP",10 => "支付宝扫码",11 => "转账银行卡1",12 => "微信扫码",13 => "转账银行卡2",',
            // 支付配置
            'wechat_scan_code_description' => '微信扫码充值说明',
            'wechat_scan_code_image' => '微信扫码充值图片',
            'scan_code_description' => '支付宝扫码充值说明',
            'scan_code_image' => '支付宝扫码充值图片',
            'transfer_description' => '转账充值说明',
            'transfer_description2' => '转账充值说明2',
            'payee' => '收款人',
            'payee2' => '收款人2',
            'receiving_account' => '收款账号',
            'receiving_account2' => '收款账号2',
            'bank_name' => '开户行',
            'bank_name2' => '开户行2',
            'wechat_help' => '微信扫码充值帮助',
            'alipay_help' => '支付宝扫码充值帮助',
            'transfer_help' => '方式一手机银行转账到银行卡帮助',
            'alipay_transfer_help' => '方式一支付宝转账到银行卡帮助',
            'wechat_transfer_help' => '方式一手机银行转账到银行卡帮助',
            'transfer_help2' => '方式二微信转账到银行卡帮助',
            'alipay_transfer_help2' => '方式二支付宝转账到银行卡帮助',
            'wechat_transfer_help2' => '方式二微信转账到银行卡帮助',
            // 协议
            'user_agreement' => '用户协议',
            'privacy_agreement' => '隐私协议',
            'invitation_rules' => '邀请规则',
            //APP 配置
            'commission_one' => '返佣一级',
            'commission_two' => '返佣二级',
            'marquee_placard' => '跑马灯公告',
            'pop_ups_placard_switch' => '弹窗公告开关',
            'pop_ups_placard' => '弹窗公告',
            // APP 版本配置
            'app_name' => 'APP名称',
            'present_version_number' => '当前版本号',
            'app_download_link' => 'APP下载地址',
            'app_upload_url' => 'APP下载地址二维码',
            'app_update_content' => 'APP更新内容',
            // 会员配置-》基础配置
            'verify_code_switch' => '登录图形验证码开关，1开,0关',
            // APP配置 基础配置
            'chaye_video_image' => '首页视频封面图',
            'chaye_video' => '首页视频',
            'hide_inside_switch' => '内部隐藏内容开关',
            'app_download_index' => '下载APP页面参数',
            'pop_ups_link' => '弹窗公告跳转内部链接',
            'share_background_image' => '分享图片',
            'show_wechat_scan_code_experience' => "展示经验值(大于等于)微信收款码",
            'show_wechat_scan_code_experience_max' => "展示经验值(小于)微信收款码",
            'show_scan_code_experience' => "展示经验值(大于等于)支付宝收款码",
            'show_scan_code_experience_max' => "展示经验值(小于)支付宝收款码",
            'show_transfer_experience' => "展示经验值(大于等于)转账银行卡方式一",
            'show_transfer_experience_max' => "展示经验值(小于)转账银行卡方式一",
            'show_transfer_experience2' => "展示经验值(大于等于)转账银行卡方式二",
            'show_transfer_experience2_max' => "展示经验值(小于)转账银行卡方式二",
            'jk_switch' => "集卡活动",
            'business_description' => '转账充值说明',
            'business_payee' => '收款人',
            'business_account' => '收款账号',
            'business_bank_name' => '开户行',
            'business_show_experience' => '展示经验值(大于等于)',
            'business_show_experience_max' => '展示经验值(小于)',
            // 新手指南
            'how_to_register' => '如何注册',
            'how_to_recharge' => '如何充值',
            'novice_academy' => '如何投资',
            'common_problem' => '常见问题',
            // 帮助中心
            'help_center' => '联系我们',
            'make_money' => '推广赚钱',
            // 外壳资讯
            'invite_friends' => '邀请好友',
            'questions_and_answers' => '碳中和问答',
            'focus' => '聚焦双碳',
            'recyclables' => '可回收物',
            'Kitchen_waste' => '厨余垃圾',
            'hazardous_waste' => '有害垃圾',
            'other_garbage' => '其他垃圾',
            'sign_days_go_investment' => '签到天数跳转',
            'member_register_day_new_disappear' => '注册天数跳转',
            'my_company_name' => '公司名称',
            'lotter_detail' => '活动详情',
            'lottery_switch' => '摇奖开关',
            // 支付宝转账
            'alipay_transfer_description' => '支付宝转账说明',
            'alipay_transfer_account' => '支付宝账号',
            'alipay_transfer_username' => '支付宝收款人',
            'alipay_transfer_experience' => '展示经验值(大于等于)',
            'alipay_transfer_experience_max' => '展示经验值(小于)',
            // 支付宝在线转账
            'alipay_online_transfer_link' => '转账跳转链接',
            'alipay_online_transfer_experience' => '展示经验值(大于等于)',
            'alipay_online_transfer_experience_max' => '展示经验值(小于)',
            // 微信H5
            'wechat_experience' => '展示经验值(大于等于)',
            'wechat_experience_max' => '展示经验值(小于)',

            // 备注
            'wechat_remark' => '微信WAP',
            'alipay_remark' => '支付宝WAP',
            'scan_code_remark' => '支付宝扫码',
            'wechat_scan_code_remark' => '微信扫码',
            'transfer_remark' => '转账银行卡方式一',
            'transfer2_remark' => '转账银行卡方式二',
            'business_remark' => '对公转账',
            'alipay_transfer_remark' => '支付宝转账',
            'alipay_online_transfer_remark' => '支付宝在线转账',
            'app_download_link2' => '有壳下载地址',
            'invitation_link' => '有壳下载地址',
            'unbind_bank_card' => '会员解绑银行卡开关',
            // 银行卡入款3
            'transfer3_remark' => '备注',
            'transfer_description3' => '转账充值说明',
            'payee3' => '收款人',
            'receiving_account3' => '收款账号',
            'bank_name3' => '开户行',
            'show_transfer_experience3' => '展示经验值(大于等于)',
            'show_transfer_experience3_max' => '展示经验值(小于)',
            'corporate_integrity' => '',
            'party_building' => '',
            'task_center' => '',
            'min_exchange_integral' => '最低兑换积分数量',
            'exchange_rate' => '积分兑换红包汇率',
            'exchange_title' => '积分兑换红包标题',
            'exchange_banner' => '积分兑换红包封面图',
            'usdt_trc20_link' => 'USDT-TRC20转账地址',
            'usdt_exchange_rate_withdraw' => '提现汇率',
//            'usdt_exchange_rate_recharge'=>'充值汇率',
            'short_plays_video_url' => '短剧视频域名',
            'short_plays_img_url' => '短剧图片域名',
            'currency_unit' => '平台货币单位',
            'return_goods_time' => '退货时间',
            'automatic_delivery_time' => '自动发货时间',
            'platform_exchange_rate' => '平台币与当地币汇率',
            'promotion_url' => '推流域名',
            'jump_announcement' => '跳动公告',
        ]));
        $result = [];
        $allConfig = Yii::$app->debris->configAll();
        foreach ($field as $item) {
            if ($item == "withdraw_method" || $item == "recharge_method") {
                $result[$item] = $allConfig[$item] ? Json::decode($allConfig[$item]) : '';
            } elseif ($item == "can_withdraw_amount" || $item == "can_recharge_amount") {
                $result[$item] = $allConfig[$item] ? explode("/", $allConfig[$item]) : '';
            } elseif ($item == "jump_announcement") {
                if ($allConfig[$item]) {
                    $jump_announcement = ArrayHelper::map(Json::decode($allConfig[$item]), 'sort', 'content');
                    ksort($jump_announcement);
                    $result[$item] = $jump_announcement;
                } else {
                    $result[$item] = "";
                }
            } else {
                $result[$item] = $allConfig[$item] ?? '';
            }
        }

        if (Yii::$app->params['thisAppEnglishName'] == "task") {
            // 真实5个
            $withdraw_model = WithdrawBill::find()
                ->select(['id', 'member_id', 'withdraw_money'])
                ->where(['status' => 1])
                ->andWhere(['>', 'withdraw_money', 400])
                ->with([
                    'member' => function ($query) {
                        $query->select(['id', 'mobile']);
                    }
                ])
                ->orderBy(new Expression('rand()'))
                ->limit(5)
                ->asArray()
                ->all();
            // 虚拟5个
            $result_model = [];
            for ($i = 0; $i < 5; $i++) {
                $email = Member::getEmail(10);
                $result_model[$i]['member']['mobile'] = $email;
                $result_model[$i]['withdraw_money'] = rand(100, 1000);
            }
            $result_model = array_merge($withdraw_model, $result_model);
            $en = "";
            $cn = "";
            $ph = "";
            foreach ($result_model as $v) {
                $cn .= substr_replace($v['member']['mobile'], "***", 1, 3) . "用户提现：" . $v['withdraw_money'] . "金额已到账。";
                $en .= "👉Congratulations (" . substr_replace($v['member']['mobile'], "***", 1, 3) . ") for withdrawing (" . $v['withdraw_money'] . ").";
                $ph .= "👉Binabati kita (" . substr_replace($v['member']['mobile'], "***", 1, 3) . ") sa pag-withdraw ng (" . $v['withdraw_money'] . ").";
            }
            $models = "[{\"title\":\"Pilipinas\",\"lang\":\"ph\",\"content\":\"$ph\"},{\"title\":\"English\",\"lang\":\"en\",\"content\":\"$en\"},{\"title\":\"中文\",\"lang\":\"cn\",\"content\":\"$cn\"}]";
            $result['marquee_placard'] = $models;
        }

        return $result;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/9
 * Time: 23:30
 */

namespace console\controllers;


use common\models\tea\RegisterIp;
use yii\console\Controller;
use Yii;
use yii\helpers\Console;

class MysqlController extends Controller
{
    /**
     * 清除用户数据
     * @throws \yii\db\Exception
     */
    public function actionMember()
    {
        $truncate_sql = "
        truncate table task_code;
        truncate table task_order;
        truncate table base_member_login_log;
        truncate table base_notify;
        truncate table base_notify_member;
        truncate table base_opinion_list;
        truncate table base_recharge_bill;
        truncate table base_statistics;
        truncate table base_withdraw_bill;
        truncate table rf_api_access_token;
        truncate table rf_backend_notify;
        truncate table rf_backend_notify_member;
        truncate table rf_backend_notify_pull_time;
        truncate table rf_backend_notify_subscription_config;
        truncate table rf_common_action_log;
        truncate table rf_common_attachment;
        truncate table rf_common_ip_blacklist;
        truncate table rf_common_log;
        truncate table rf_common_pay_log;
        truncate table rf_common_report_log;
        truncate table rf_common_sms_log;
        truncate table rf_member;
        truncate table rf_member_account;
        truncate table rf_member_card;
        truncate table rf_member_credits_log;
        truncate table t_activity_card_list;
        truncate table t_answer_list;
        truncate table t_coupon_list;
        truncate table t_coupon_member;
        truncate table t_gdt_token;
        truncate table t_investment_bill;
        truncate table t_red_envelope;
        truncate table t_sign_goods_bill;
        truncate table rf_words;
        truncate table rf_chat_log_0;
        truncate table dk_bill;
        truncate table dk_detail;
        truncate table dk_last_contact;
        truncate table dk_notify;
        truncate table dj_buy_level_list;
        truncate table dj_like_list;
        truncate table dj_collect_list;
        truncate table dj_orders;
        truncate table dj_recently_viewed;
        truncate table dj_seller_available_list;
        truncate table base_realname_audit;
        truncate table base_sign_in;
        ";
        Yii::$app->db->createCommand($truncate_sql)->execute();
        Console::stdout('数据表初始化成功');
        exit();
    }



    /**
     * 初始化数据库截断表
     * @throws \yii\db\Exception
     */
    public function actionIndex()
    {
        $truncate_sql = "
        truncate table task_code;
        truncate table task_project;
        truncate table task_order;
        truncate table base_article_details;
        truncate table base_article_details_translations;
        truncate table base_img_details;
        truncate table base_img_details_translations;
        truncate table base_member_login_log;
        truncate table base_notify;
        truncate table base_notify_member;
        truncate table base_opinion_list;
        truncate table base_recharge_bill;
        truncate table base_statistics;
        truncate table base_withdraw_bill;
        truncate table rf_api_access_token;
        truncate table rf_backend_notify;
        truncate table rf_backend_notify_member;
        truncate table rf_backend_notify_pull_time;
        truncate table rf_backend_notify_subscription_config;
        truncate table rf_common_action_log;
        truncate table rf_common_attachment;
        truncate table rf_common_ip_blacklist;
        truncate table rf_common_log;
        truncate table rf_common_pay_log;
        truncate table rf_common_report_log;
        truncate table rf_common_sms_log;
        truncate table rf_member;
        truncate table rf_member_account;
        truncate table rf_member_card;
        truncate table rf_member_credits_log;
        truncate table t_activity_card_list;
        truncate table t_answer_list;
        truncate table t_coupon_list;
        truncate table t_coupon_member;
        truncate table t_gdt_token;
        truncate table t_investment_bill;
        truncate table t_investment_project;
        truncate table t_red_envelope;
        truncate table t_sign_goods_bill;
        truncate table t_sign_goods_list;
        truncate table t_web_list;
        truncate table rf_words;
        truncate table rf_chat_log_0;
        truncate table dk_bill;
        truncate table dk_detail;
        truncate table dk_last_contact;
        truncate table dk_notify;
        truncate table dk_project;
        truncate table dk_room;
        truncate table dj_buy_level_list;
        truncate table dj_like_list;
        truncate table dj_collect_list;
        truncate table dj_orders;
        truncate table dj_recently_viewed;
        truncate table dj_seller_available_list;
        truncate table base_realname_audit;
        truncate table base_sign_in;
        truncate table dj_short_plays_detail;
        truncate table dj_short_plays_detail_translations;
        truncate table dj_short_plays_list;
        truncate table dj_short_plays_list_translations;
        truncate table dj_laber_list;
        truncate table dj_laber_list_translations;
        ";
        Yii::$app->db->createCommand($truncate_sql)->execute();
        Console::stdout('数据表初始化成功');
        exit();
    }


    /**
     * 初始化数据库截断表 日志
     * @throws \yii\db\Exception
     */
    public function actionLog()
    {
        $truncate_sql = "
        truncate table rf_backend_notify;
        truncate table rf_backend_notify_member;
        truncate table rf_backend_notify_pull_time;
        truncate table rf_backend_notify_subscription_config;
        truncate table rf_common_action_log;
        truncate table rf_common_log;
        truncate table rf_common_report_log;
        ";
        Yii::$app->db->createCommand($truncate_sql)->execute();
        Console::stdout('数据表日志初始化成功');
        exit();
    }
}
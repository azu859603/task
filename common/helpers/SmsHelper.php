<?php

namespace common\helpers;
use Yii;

class SmsHelper
{
    /**
     * 达信通
     * @param $mobile
     * @param $templateContent
     * @return mixed
     */
    public static function daxintong($mobile,$templateContent){
        $daxintong_userid = Yii::$app->debris->config('daxintong_userid');
        $daxintong_account = Yii::$app->debris->config('daxintong_account');
        $daxintong_password = Yii::$app->debris->config('daxintong_password');
        $get_data = [
            'userid'=>$daxintong_userid,
            'account'=>$daxintong_account,
            'password'=>$daxintong_password,
            'mobile'=>$mobile,
            'content'=>$templateContent,
            'sendTime'=>"",
            'action'=>"send",
            'extno'=>"",
        ];
        $url = "http://47.111.147.42:8888/sms.aspx?".http_build_query($get_data);
        return CommonPluginHelper::curl_get($url);
    }


    /**
     * 短信宝
     */
    public static function duanxinbao($mobile, $templateContent)
    {
        $duanxinbao_account = Yii::$app->debris->config('duanxinbao_account');
        $duanxinbao_password = Yii::$app->debris->config('duanxinbao_password');
        $get_data = [
            'u' => $duanxinbao_account,
            'p' => md5($duanxinbao_password),
            'm' => $mobile,
            'c' => $templateContent,
        ];
        $sendurl = "https://api.smsbao.com/sms?" . http_build_query($get_data);
        return CommonPluginHelper::curl_get($sendurl);
    }
}
<?php

namespace common\helpers;

use common\models\tea\GdtToken;

class GdtHelper
{
    /**
     * 广点通，广告接入 https://developers.e.qq.com/docs/api/user_data/user_action/user_actions_add?version=1.1&_preview=1
     * @param $action_type "REGISTER  ACTIVATE_APP"
     * @param string $hash_imei "机型ID"
     * @return mixed
     */
    public static function eqq_user_actions_add($access_token, $account_id, $user_action_set_id, $action_type, $hash_imei = '')
    {
        $url = 'https://api.e.qq.com/v1.1/user_actions/add';
        $common_parameters = array(
            'access_token' => $access_token,
            'timestamp' => time(),
            'nonce' => md5(uniqid('', true))
        );
        $parameters = array(
            'account_id' => $account_id,
            'user_action_set_id' => $user_action_set_id,
            'actions' =>
                array(
                    array(
                        'action_time' => time(),
                        'action_type' => $action_type,
                        'user_id' => array('hash_imei' => $hash_imei),
                    ),
                ),
        );
        $parameters = json_encode($parameters);
        $request_url = $url . '?' . http_build_query($common_parameters);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type:application/json"
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }


    /**
     * 通过 Authorization Code 获取 Access Token 或刷新 Access Token
     * https://developers.e.qq.com/docs/api/authorize/oauth/oauth_token?version=1.1&_preview=1
     */
    public static function eqq_oauth_token($client_id, $client_secret, $grant_type, $redirect_uri, $authorization_code = null, $refresh_token = null)
    {
        $url = 'https://api.e.qq.com/oauth/token';
        $common_parameters = array(
            'timestamp' => time(),
            'nonce' => md5(uniqid('', true))
        );
        $parameters = array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => $grant_type,
            'redirect_uri' => $redirect_uri,
        );
        if ($grant_type == 'authorization_code') {
            $parameters['authorization_code'] = $authorization_code;
        } else {
            $parameters['refresh_token'] = $refresh_token;
        }
        $parameters = array_merge($common_parameters, $parameters);
        foreach ($parameters as $key => $value) {
            if (!is_string($value)) {
                $parameters[$key] = json_encode($value);
            }
        }
        $request_url = $url . '?' . http_build_query($parameters);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $request_url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl);
        if (curl_error($curl)) {
            $error_msg = curl_error($curl);
            $error_no = curl_errno($curl);
            curl_close($curl);
            throw new \Exception($error_msg, $error_no);
        }
        curl_close($curl);
        return json_decode($response, true);
    }
}
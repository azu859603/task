<?php


namespace common\helpers;


use GatewayClient\Gateway;

class GatewayInit
{

    public static function initBase()
    {
        Gateway::$registerAddress = \Yii::$app->params['gatewayRegisterAddressBase'];
    }
}
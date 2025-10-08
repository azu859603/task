<?php

namespace frontend\controllers;

use yii\web\Controller;
use common\traits\PayNotify;

/**
 * 支付回调
 *
 * Class NotifyController
 * @package frontend\controllers
 * @author 原创脉冲
 */
class NotifyController extends Controller
{
    use PayNotify;

    /**
     * 关闭csrf
     *
     * @var bool
     */
    public $enableCsrfValidation = false;
}
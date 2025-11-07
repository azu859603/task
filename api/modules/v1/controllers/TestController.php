<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 3:52
 */

namespace api\modules\v1\controllers;


use api\controllers\OnAuthController;
use common\helpers\CommonPluginHelper;
use common\helpers\DateHelper;
use common\helpers\RedisHelper;
use common\models\common\Languages;
use common\models\dj\Orders;
use common\models\dj\SellerAvailableList;
use common\models\dj\ShortPlaysList;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\member\MemberCard;
use common\models\member\RedEnvelope;
use common\models\task\Order;
use common\models\tea\CouponMember;
use common\models\tea\InvestmentBill;
use common\models\tea\QuestionsList;
use Yii;
use yii\db\Expression;

/**
 * 测试方法
 * Class TestController
 * @package api\modules\v1\controllers
 */
class TestController extends OnAuthController
{
    public $modelClass = '';

    protected $authOptional = ['index', 'create'];

    // 不用进行签名验证的方法
    protected $signOptional = ['index', 'create'];

    public function actionIndex()
    {
//        $a = Order::find()->where(['member_id' => 1, 'status' => 2])->count();
//        var_dump($a);exit;
//        $seller_member = Member::find()->where(['id'=>1547])->one();
//        Yii::$app->services->memberLevel->updateSellerLevel($seller_member);
        exit('完成');
        var_dump($this->getEmail(rand(3, 8)));
        exit;
        var_dump(date('Y-m-d', strtotime("-6 day")));
        exit;
        var_dump(md5(123456));
        exit;

        $host = "https://vdo.weseehort.com";
        $url = "/movie/auto/67ef851c3c6ad62d6956c37f.m3u8";
        $start = strpos($url, '/auto');
        $end = strpos($url, '.m3u8');
        $str = substr($url, $start, $end - 6);
        $key = "abcd123456";
        $time = CommonPluginHelper::msectime() + (60 * 1000 * 60);
        $counts = 2;
        $str2 = $str . "&counts=$counts&timestamp=" . $time . $key;
        $sign = md5($str2);
//        $return_url = htmlentities($host . $url . "?counts=$counts&timestamp=" . $time . "&key=" . $sign);
        $return_url = $host . $url . "?counts=$counts&timestamp=" . $time . "&key=" . $sign;
        var_dump($return_url);
        exit;
        return $return_url;
    }

    public function getEmail($number)
    {
        $sz = range("0", "9");
        $zm = range("a", "z");
        $all = array_merge($sz, $zm);
        $result = implode("", array_rand(array_flip($all), $number)) . "@gmail.com";
        if (Member::find()->where(['mobile' => $result])->exists()) {
            $this->getEmail($number);
        }
        return $result;
    }

    public function actionCreate()
    {
        Yii::$app->cache->flush();
    }
}

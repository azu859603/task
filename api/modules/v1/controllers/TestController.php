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
        // 使用示例
        try {
            $apiKey = '';
            $imageAI = new AdvancedOpenAIImage($apiKey);

            // 生成图片URL
            $result = $imageAI->generateImageUrl(
                '一只穿着宇航服的柴犬在太空中漂浮，地球在背景中',
                'large',
                1
            );

            $imageUrl = $result['data'][0]['url'];
            echo "图片URL: " . $imageUrl;

            // 下载图片
            // $imageAI->downloadImage($imageUrl, 'my_image.png');

        } catch (\Exception $e) {
            echo "错误: " . $e->getMessage();
        }
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

<?php

namespace addons\RfArticle\merchant\controllers;

use Yii;
use common\traits\MerchantCurd;
use addons\RfArticle\common\models\ArticleSingle;

/**
 * 单页管理
 *
 * Class ArticleSingleController
 * @package addons\RfArticle\merchant\controllers
 * @author 原创脉冲
 */
class ArticleSingleController extends BaseController
{
    use MerchantCurd;

    /**
     * @var ArticleSingle
     */
    public $modelClass = ArticleSingle::class;
}
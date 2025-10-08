<?php

namespace addons\RfDevTool\backend\controllers;

/**
 * Class PhpInfoController
 * @package addons\RfDevTool\backend\controllers
 * @author 原创脉冲
 */
class PhpInfoController extends BaseController
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index', [

        ]);
    }
}
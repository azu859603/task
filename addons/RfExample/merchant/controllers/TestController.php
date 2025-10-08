<?php

namespace addons\RfExample\merchant\controllers;

/**
 * Class TestController
 * @package addons\RfExample\merchant\controllers
 * @author 原创脉冲
 */
class TestController extends BaseController
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index', [

        ]);
    }

    /**
     * @return string
     */
    public function actionUpdate()
    {
        return $this->render('update', [

        ]);
    }
}
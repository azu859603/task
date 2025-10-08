<?php

namespace addons\RfExample\merchant\controllers\test;

use addons\RfExample\merchant\controllers\BaseController;

/**
 * Class TestController
 * @package addons\RfExample\merchant\controllers\test
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
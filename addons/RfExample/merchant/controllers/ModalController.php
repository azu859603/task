<?php

namespace addons\RfExample\merchant\controllers;

use addons\RfExample\common\models\Curd;

/**
 * Class ModalController
 * @package addons\RfExample\merchant\controllers
 * @author 原创脉冲
 */
class ModalController extends BaseController
{
    /**
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @return string
     */
    public function actionView($type)
    {
        return $this->renderAjax($type, [
            'model' => new Curd(),
        ]);
    }
}
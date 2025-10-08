<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2019/11/24
 * Time: 2:08
 */

namespace api\modules\v1\controllers\common;

use api\controllers\OnAuthController;
use api\modules\v1\forms\common\ImgForm;
use common\enums\StatusEnum;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use Yii;
use yii\data\ActiveDataProvider;

class LanguagesController extends OnAuthController
{
    public $modelClass = Languages::class;

    protected $authOptional = ['index'];


    public function actionIndex()
    {
        $models = Languages::find()
            ->where(['status' => 1])
            ->orderBy(['sort' => SORT_ASC])
            ->asArray()
            ->all();
        foreach ($models as $k => $v) {
            $models[$k]['img'] = "/flags/" . $v['code'] . ".png";
        }
        return $models;
    }

    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'create', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
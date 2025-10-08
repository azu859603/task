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
use common\models\common\ImgDetails;
use common\models\common\Languages;
use Yii;
use yii\data\ActiveDataProvider;

class ImgDetailsController extends OnAuthController
{
    public $modelClass = ImgDetails::class;

    protected $authOptional = ['index'];


    public function actionIndex()
    {
        $form = new ImgForm();
        $form->attributes = Yii::$app->request->get();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }

        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);

        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select(['id', 'content', 'jump_url', 'jump_type'])
                ->where(['status' => StatusEnum::ENABLED, 'pid' => $form->pid])
                ->orderBy('sort asc, id desc')
                ->with(['translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                }])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
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
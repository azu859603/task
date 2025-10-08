<?php

namespace api\modules\v1\controllers\common;

use api\modules\v1\forms\common\ArticleForm;
use common\helpers\ResultHelper;
use common\models\common\ArticleDetails;
use common\models\common\Languages;
use yii\data\ActiveDataProvider;
use common\enums\StatusEnum;
use api\controllers\OnAuthController;
use Yii;

/**
 * 文章接口
 *
 * Class ArticleController
 * @package addons\RfArticle\api\controllers
 * @property \yii\db\ActiveRecord|\yii\base\Model $modelClass;
 * @author 原创脉冲
 */
class ArticleDetailsController extends OnAuthController
{
    public $modelClass = ArticleDetails::class;

    /**
     * 不用进行登录验证的方法
     * 例如： ['index', 'update', 'create', 'view', 'delete']
     * 默认全部需要验证
     *
     * @var array
     */
    protected $authOptional = ['index', 'detail'];

    /**
     * 首页
     *
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $form = new ArticleForm();
        $form->attributes = Yii::$app->request->get();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['status' => StatusEnum::ENABLED, 'pid' => $form->pid])
                ->select(['id', 'title', 'banner', 'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at'])
                ->orderBy('sort asc, created_at desc')
                ->with(['translation'=>function($query)use($lang){
                    $query->where(['lang'=>$lang]);
                }])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }


    /**
     * 文章详情
     */
    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        if (!isset($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID不能为空");
        }
        return ArticleDetails::getModelById($id,$lang);
    }

    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'delete', 'create', 'update'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
<?php

namespace api\modules\v1\controllers\common;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\models\common\ArticleCategory;
use common\models\common\Languages;
use yii\data\ActiveDataProvider;
use Yii;

/**
 * 文章分类
 *
 * Class ArticleCateController
 * @package addons\RfArticle\api\controllers
 * @author 原创脉冲
 */
class ArticleCategoryController extends OnAuthController
{
    public $modelClass = ArticleCategory::class;

    /**
     * 不用进行登录验证的方法
     * 例如： ['index', 'update', 'create', 'view', 'delete']
     * 默认全部需要验证
     *
     * @var array
     */
    protected $authOptional = ['index'];


    /**
     * 首页
     *
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select(['id', 'title', 'banner', 'type'])
                ->where(['status' => StatusEnum::ENABLED])
                ->with(['translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                }])
                ->orderBy('sort asc, id desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
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
        if (in_array($action, ['delete', 'create', 'update', 'view'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
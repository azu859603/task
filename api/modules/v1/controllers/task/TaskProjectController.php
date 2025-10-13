<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:44
 */

namespace api\modules\v1\controllers\task;


use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\task\LaberList;
use common\models\task\Project;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\Json;

class TaskProjectController extends OnAuthController
{
    public $modelClass = Project::class;

    // 不用进行登录验证的方法
    protected $authOptional = ['index', 'list'];

    /**
     * 分类列表
     */
    public function actionList()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);

        $model = LaberList::find()
            ->where(['status' => 1])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->with([
                'translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                },
            ])
            ->asArray()
            ->all();
        return $model;
    }


    /**
     * 任务列表
     */
    public function actionIndex()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $pid = Yii::$app->request->get('pid');
        if (empty($pid)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "父级不能为空");
        }
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['status' => StatusEnum::ENABLED, 'pid' => $pid])
                ->with([
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }
                ])
                ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    public function actionDetail()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $id = Yii::$app->request->get('id');
        return $this->modelClass::find()
            ->where(['id' => $id])
            ->with([
                'translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                }
            ])
            ->one();
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
        if (in_array($action, ['create', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
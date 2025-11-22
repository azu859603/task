<?php

namespace backend\modules\task\controllers;

use common\helpers\AdvancedOpenAIImage;
use common\helpers\OpenAICopywriter;
use common\models\common\Languages;
use Yii;
use common\models\task\AiContent;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * AiContent
 *
 * Class AiContentController
 * @package backend\modules\task\controllers
 */
class AiContentController extends BaseController
{
    use Curd;

    /**
     * @var AiContent
     */
    public $modelClass = AiContent::class;


    /**
     * 首页
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
            $model = AiContent::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('AiContent'));
            $post = ['AiContent' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);

            $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
            $lang = Yii::$app->request->get('lang', $default_lang);

            $dataProvider->query
                ->with([
                    'project' => function ($query) use ($lang) {
                        $query->with([
                            'translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            },
                        ]);
                    },
                ]);

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }


    /**
     * ajax编辑/创建
     * @return mixed
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if($model->type == 1){ // 图片
                $model->content = AdvancedOpenAIImage::get($model->ai_content);
            }else{
                $model->content = OpenAICopywriter::get($model->ai_content);
            }

            return $model->save()
                ? $this->message("操作成功", $this->redirect(Yii::$app->request->referrer))
                : $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
        }


        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);

        $taskModels = \common\models\task\Project::find()
            ->with(['translation' => function ($query) use ($lang) {
                $query->where(['lang' => $lang]);
            }])
            ->asArray()
            ->all();
        $taskModel = [];
        foreach ($taskModels as $k => $v) {
            $id = $v['id'];
            $taskModel[$id] = $v['translation']['title'];
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'taskModel' => $taskModel
        ]);
    }

}

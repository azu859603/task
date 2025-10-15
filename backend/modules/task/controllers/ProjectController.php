<?php

namespace backend\modules\task\controllers;

use common\models\task\ProjectTranslations;
use Yii;
use common\models\task\Project;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
* Project
*
* Class ProjectController
* @package backend\modules\task\controllers
*/
class ProjectController extends BaseController
{
    use Curd;

    /**
    * @var Project
    */
    public $modelClass = Project::class;


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
            $model = Project::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Project'));
            $post = ['Project' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                isset($posted['status']) && $output = ['1' => '启用', '0' => '禁用'][$model->status];
                isset($posted['code_switch']) && $output = ['1' => '启用', '0' => '禁用'][$model->code_switch];
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

            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
            $lang = Yii::$app->request->get('lang', $default_lang);

            $dataProvider->query
                ->with([
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }]);




            $categorys = \common\models\task\LaberList::find()
                ->with(['translation' => function ($query)use ($lang) {
                    $query->where(['lang' => $lang]);
                }])
                ->asArray()
                ->all();
            $category = [];
            foreach ($categorys as $k => $v) {
                $id = $v['id'];
                $category[$id] = $v['translation']['title'];
            }
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'category' => $category,
            ]);
        }
    }

    /**
     * 编辑/创建
     *
     * @return mixed
     */
    public function actionEdit()
    {
        $id = Yii::$app->request->get('id', null);
//        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $model = $this->findModel($id);
        $model_translations = ProjectTranslations::find()->where(['lang' => $lang, 'pid' => $id])->one();
        if (empty($model_translations)) {
            $model_translations = new ProjectTranslations();
            $model_translations->lang = $lang;
        }
        if ($model->load(Yii::$app->request->post()) && $model_translations->load(Yii::$app->request->post())) {
            $model->remain_number = $model->all_number;
            if ($model->save()) {
                $model_translations->pid = $model->id;
                if ($model_translations->save()) {
                    return $this->message("操作成功", $this->redirect(['index']));
                }
                return $this->message($this->getError($model_translations), $this->redirect(Yii::$app->request->referrer), 'error');
            } else {
                return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->render($this->action->id, [
            'model' => $model,
            'model_translations' => $model_translations,
            'lang' => $lang,
        ]);
    }
}

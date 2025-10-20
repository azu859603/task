<?php

namespace backend\modules\common\controllers;

use common\models\common\ArticleDetailsTranslations;
use common\models\common\Languages;
use Yii;
use common\models\common\ArticleDetails;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * ArticleDetails
 *
 * Class ArticleDetailsController
 * @package backend\modules\common\controllers
 */
class ArticleDetailsController extends BaseController
{
    use Curd;

    /**
     * @var ArticleDetails
     */
    public $modelClass = ArticleDetails::class;


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
            $model = ArticleDetails::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('ArticleDetails'));
            $post = ['ArticleDetails' => $posted];
            if ($model->load($post) && $model->save()) {
                $output = $model->$attribute;
                if ($attribute == 'pid') {
                    $datas = \yii\helpers\ArrayHelper::map(\common\models\common\ArticleCategory::find()->asArray()->all(), 'id', 'title');
                    $output = $datas[$model->$attribute];
                }
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
                    'sort' => SORT_ASC,
                    'created_at' => SORT_DESC,
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);

//            $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "en";
            $lang = Yii::$app->request->get('lang', $default_lang);

            $dataProvider->query
                ->with([
                    'manager',
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }]);
            $pid = Yii::$app->request->get('pid');
            if (!empty($pid)) {
                $dataProvider->query->andWhere(['pid' => $pid]);
            }

            $articleCategorys = \common\models\common\ArticleCategory::find()
                ->with(['translation' => function ($query)use ($lang) {
                    $query->where(['lang' => $lang]);
                }])
                ->asArray()
                ->all();
            $articleCategory = [];
            foreach ($articleCategorys as $k => $v) {
                $id = $v['id'];
                $articleCategory[$id] = $v['translation']['title'];
            }
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'articleCategory' => $articleCategory,
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
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "en";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $model = $this->findModel($id);
        $model_translations = ArticleDetailsTranslations::find()->where(['lang' => $lang, 'pid' => $id])->one();
        if (empty($model_translations)) {
            $model_translations = new ArticleDetailsTranslations();
            $model_translations->lang = $lang;
        }
        if ($model->load(Yii::$app->request->post()) && $model_translations->load(Yii::$app->request->post())) {
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

<?php

namespace backend\modules\common\controllers;

use common\models\common\ImgDetailsTranslations;
use common\models\common\Languages;
use Yii;
use common\models\common\ImgDetails;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * ImgDetails
 *
 * Class ImgDetailsController
 * @package backend\modules\common\controllers
 */
class ImgDetailsController extends BaseController
{
    use Curd;

    /**
     * @var ImgDetails
     */
    public $modelClass = ImgDetails::class;


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
            $model = ImgDetails::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('ImgDetails'));
            $post = ['ImgDetails' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                if ($attribute == 'pid') {
                    $datas = \yii\helpers\ArrayHelper::map(\common\models\common\ImgCategory::find()->asArray()->all(), 'id', 'title');
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
            $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
            $lang = Yii::$app->request->get('lang', $default_lang);

            $dataProvider->query
                ->with([
                    'manager',
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }
                ]);

            $pid = Yii::$app->request->get('pid');
            if (!empty($pid)) {
                $dataProvider->query->andWhere(['pid' => $pid]);
            }
            $imgCategory = \yii\helpers\ArrayHelper::map(\common\models\common\ImgCategory::find()->asArray()->all(), 'id', 'title');
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'imgCategory' => $imgCategory,
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
        $model_translations = ImgDetailsTranslations::find()->where(['lang' => $lang, 'pid' => $id])->one();
        if (empty($model_translations)) {
            $model_translations = new ImgDetailsTranslations();
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

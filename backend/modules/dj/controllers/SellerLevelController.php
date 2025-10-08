<?php

namespace backend\modules\dj\controllers;

use common\models\common\Languages;
use common\models\dj\SellerLevelTranslations;
use Yii;
use common\models\dj\SellerLevel;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * SellerLevel
 *
 * Class SellerLevelController
 * @package backend\modules\dj\controllers
 */
class SellerLevelController extends BaseController
{
    use Curd;

    /**
     * @var SellerLevel
     */
    public $modelClass = SellerLevel::class;


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
            $model = SellerLevel::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('SellerLevel'));
            $post = ['SellerLevel' => $posted];
            if ($model->load($post) && $model->save(false)) {
                $output = $model->$attribute;
                isset($posted['status']) && $output = ['1' => '启用', '0' => '禁用'][$model->status];
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
                    'level' => SORT_ASC
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
                    'translation' => function ($query) use ($lang) {
                        $query->where(['lang' => $lang]);
                    }]);

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
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
        $model_translations = SellerLevelTranslations::find()->where(['lang' => $lang, 'pid' => $id])->one();
        if (empty($model_translations)) {
            $model_translations = new SellerLevelTranslations();
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

<?php

namespace backend\modules\dj\controllers;

use common\models\common\Languages;
use Yii;
use common\models\dj\RecentlyViewed;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * RecentlyViewed
 *
 * Class RecentlyViewedController
 * @package backend\modules\dj\controllers
 */
class RecentlyViewedController extends BaseController
{
    use Curd;

    /**
     * @var RecentlyViewed
     */
    public $modelClass = RecentlyViewed::class;


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
            $model = RecentlyViewed::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('RecentlyViewed'));
            $post = ['RecentlyViewed' => $posted];
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
                'relations' => ['member' => ['mobile']],
                'defaultOrder' => [
                    'id' => SORT_DESC
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
                    'shortPlaysDetail' => function ($query) use ($lang) {
                        $query->with([
                            'shortPlaysList' => function ($query) use ($lang) {
                                $query->with([
                                    'translation' => function ($query) use ($lang) {
                                        $query->where(['lang' => $lang]);
                                    },
                                ]);
                            },
                            'translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            },
                        ]);
                    }
                ]);
            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }
}

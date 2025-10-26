<?php

namespace backend\modules\task\controllers;

use common\helpers\RedisHelper;
use common\models\common\Languages;
use common\models\member\Member;
use common\models\task\Project;
use Yii;
use common\models\task\Order;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
 * Order
 *
 * Class OrderController
 * @package backend\modules\task\controllers
 */
class OrderController extends BaseController
{
    use Curd;

    /**
     * @var Order
     */
    public $modelClass = Order::class;


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
            $model = Order::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Order'));
            $post = ['Order' => $posted];
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
                'relations' => ['member' => ['mobile'], 'project' => ['cid'], 'manager' => 'username'],
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

//            $backend_id = Yii::$app->user->identity->getId();
//            if ($backend_id != 1) {
//                $a_id = Yii::$app->user->identity->aMember->id;
//                $childrenIds = Member::getChildrenIds($a_id);
//                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
//            }


            $laber_categorys = \common\models\task\LaberList::find()
                ->with(['translation' => function ($query) use ($lang) {
                    $query->where(['lang' => $lang]);
                }])
                ->asArray()
                ->all();
            $laber_category = [];
            foreach ($laber_categorys as $k => $v) {
                $id = $v['id'];
                $laber_category[$id] = $v['translation']['title'];
            }
            $category = \yii\helpers\ArrayHelper::map(\common\models\tea\CategoryList::find()->asArray()->all(), 'id', 'title');

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'laber_category' => $laber_category,
                'category' => $category,
            ]);
        }
    }

    /**
     * 审核提现订单
     * @param $id
     * @param $status
     * @param string $remark
     * @return mixed
     */
    public function actionCheck($id, $status, $remark = "")
    {
        RedisHelper::verify($id, $this->action->id);
        $model = Order::find()->where(['id' => $id, 'status' => 1])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->updated_by = Yii::$app->user->getId();
        $model->status = $status;
        $model->remark = $remark;
        $model->save(false);
        if ($status == 2) {
            Yii::$app->services->actionLog->create('order/check', '通过任务');
        } else {
            Yii::$app->services->actionLog->create('order/check', '驳回任务');
        }
        return $this->message("审核成功！", $this->redirect(Yii::$app->request->referrer));
    }

    public function actionNoPass()
    {
        $id = Yii::$app->request->get('id');
        $status = Yii::$app->request->get('status');
        $model = Order::findOne($id);
        $model->status = $status;
        if ($model->load(Yii::$app->request->post())) {
            return $this->redirect(['check', 'id' => $id, 'status' => $model->status, 'remark' => $model->remark]);
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    /**
     * 查看账户信息
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionView()
    {
        $id = Yii::$app->request->get('id');
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $model = Project::find()->where(['id' => $id])->with([
            'translation' => function ($query) use ($lang) {
                $query->where(['lang' => $lang]);
            },
        ])->one();
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}

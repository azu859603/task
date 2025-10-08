<?php

namespace backend\modules\tea\controllers;

use backend\modules\tea\forms\ImportForm;
use common\helpers\ExcelHelper;
use Yii;
use common\models\tea\SignGoodsBill;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\web\UploadedFile;

/**
 * SignGoodsBill
 *
 * Class SignGoodsBillController
 * @package backend\modules\tea\controllers
 */
class SignGoodsBillController extends BaseController
{
    use Curd;

    /**
     * @var SignGoodsBill
     */
    public $modelClass = SignGoodsBill::class;


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
            $model = SignGoodsBill::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('SignGoodsBill'));
            $post = ['SignGoodsBill' => $posted];
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
                'relations' => ['list' => ['title']],
                'partialMatchAttributes' => ['list.title'], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query->with(['member', 'list']);

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }

    public function actionShip($id)
    {
        $model = SignGoodsBill::find()->where(['id' => $id, 'status' => 1])->one();
        if (empty($model)) {
            return $this->message("该商品无法点击发货！", $this->redirect(Yii::$app->request->referrer),'error');
        }
        if ($model->load(Yii::$app->request->post())) {
            if (empty($model->remark)) {
                return $this->message("填上快递单号才能发货哦！", $this->redirect(Yii::$app->request->referrer),'error');
            }
            $model->status = 2;
            $model->ship_time = time();
            $model->save();
            return $this->message("操作成功！", $this->redirect(Yii::$app->request->referrer));
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 导出
     */
    public function actionExport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $ids = explode(",", Yii::$app->request->get('ids'));
        $model = SignGoodsBill::find()
            ->where(['id' => $ids])
            ->select(['id', 'g_id', 'sn', 'member_remark', 'remark', 'get_username', 'get_mobile'])
            ->with(['list' => function ($query) {
                $query->select(['id', 'title']);
            }])
            ->asArray()
            ->all();
        $header = [
            ['ID', 'id'],
            ['订单号', 'sn'],
            ['商品名称', 'list.title'],
            ['收件人', 'get_username'],
            ['联系电话', 'get_mobile'],
            ['收货地址', 'member_remark'],
            ['快递单号', 'remark'],
        ];
        return ExcelHelper::exportData($model, $header);
    }

    /**
     * 导入
     * @return mixed|string
     */
    public function actionImport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        if (Yii::$app->request->isPost) {
            try {
                $file = $_FILES['excelFile'];
                $data = ExcelHelper::import($file['tmp_name'], 2);
            } catch (\Exception $e) {
                return $this->message($e->getMessage(), $this->redirect(Yii::$app->request->referrer), 'error');
            }
            foreach ($data as $v) {
                $model = SignGoodsBill::findOne(['sn' => $v[1]]);
                if (empty($model)) {
                    continue;
                }
                $model->remark = $v[6];
                $model->status = 2;
                $model->ship_time = time();
                $model->save();
            }
            return $this->message("操作成功！", $this->redirect(Yii::$app->request->referrer));
        }
        return $this->renderAjax($this->action->id, [
        ]);
    }
}

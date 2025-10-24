<?php

namespace backend\modules\task\controllers;

use backend\modules\task\forms\ImportForm;
use common\models\member\Member;
use Yii;
use common\models\task\TaskCode;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\web\Response;

/**
 * TaskCode
 *
 * Class TaskCodeController
 * @package backend\modules\task\controllers
 */
class TaskCodeController extends BaseController
{
    use Curd;

    /**
     * @var TaskCode
     */
    public $modelClass = TaskCode::class;


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
            $model = TaskCode::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('TaskCode'));
            $post = ['TaskCode' => $posted];
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

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }

    /**
     * 导入
     *
     * @return mixed|string|Response
     * @throws \yii\base\ExitException
     */
    public function actionImport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new ImportForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if (empty($model->file)) {
                return $this->message("文件必须上传！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $data = file_get_contents(Yii::getAlias('@root') . '/web' . $model->file);
            $number_list = explode("\r\n", $data);
            if (empty($number_list)) {
                return $this->message("暂无数据！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $db = Yii::$app->db;
            foreach ($number_list as $k=>$v){
                $insert_data['code'] = $v;
                $sql = $db->createCommand()->insert('task_code', $insert_data)->getRawSql();
                $sql = str_replace("INSERT", "INSERT IGNORE", $sql);
                $db->createCommand($sql)->execute();
            }
            Yii::$app->services->actionLog->create('task-code/import', '导入活动码');
            return $this->message("操作成功", $this->redirect(['index']));
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
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
            if($model->save()){
                Yii::$app->services->actionLog->create('task-code/ajax-edit', '新增/编辑活动码');
                return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
            }else{
                return$this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 删除
     *
     * @param $id
     * @return mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        if ($this->findModel($id)->delete()) {
            Yii::$app->services->actionLog->create('task-code/delete', '删除活动码');
            return $this->message("删除成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->message("删除失败", $this->redirect(Yii::$app->request->referrer), 'error');
    }


}

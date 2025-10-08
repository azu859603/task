<?php

namespace backend\modules\member\controllers;

use backend\modules\member\forms\SendLotterForm;
use common\enums\StatusEnum;
use common\models\member\Member;
use Yii;
use common\models\member\RedEnvelope;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\web\UploadedFile;

/**
 * RedEnvelope
 *
 * Class RedEnvelopeController
 * @package backend\modules\member\controllers
 */
class RedEnvelopeController extends BaseController
{
    use Curd;

    /**
     * @var RedEnvelope
     */
    public $modelClass = RedEnvelope::class;


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
            $model = RedEnvelope::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('RedEnvelope'));
            $post = ['RedEnvelope' => $posted];
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

            $dataProvider->query->andWhere(['type' => 1]);

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
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        if ($id = Yii::$app->request->get('id')) {
            $model = RedEnvelope::findOne($id);
        } else {
            $model = new RedEnvelope();
            $model->scenario = 'noAll';
        }
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($id) {
                $model->save();
            } else {
                // 批量加
                $member_ids = $model->member_id;
                $rows = [];
                $fields = ['member_id', 'title', 'money', 'created_at'];
                foreach ($member_ids as $v) {
                    $rows[] = [$v, $model['title'], $model['money'], time()];
                }
                !empty($rows) && Yii::$app->db->createCommand()->batchInsert(RedEnvelope::tableName(), $fields, $rows)->execute();

            }
            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public function actionAll()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new RedEnvelope();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->level == 0) { // 全部
                $where_array = [];
            } else {
                $where_array = ['current_level' => $model->level];
            }
            $member_ids = Member::find()->where($where_array)->select(['id'])->column();
            if (empty($member_ids)) {
                return $this->message("暂无会员！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $rows = [];
            $fields = ['member_id', 'title', 'money', 'created_at'];
            foreach ($member_ids as $v) {
                $rows[] = [$v, $model['title'], $model['money'], time()];
            }
            !empty($rows) && Yii::$app->db->createCommand()->batchInsert(RedEnvelope::tableName(), $fields, $rows)->execute();
            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    public function actionSendLotter()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new SendLotterForm();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->level == 0) { // 全部
                Member::updateAllCounters(['free_lottery_number' => $model->number]);
            } else {
                Member::updateAllCounters(['free_lottery_number' => $model->number], ['current_level' => $model->level]);
            }
            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 导入会员账号赠送
     * @return mixed|string
     * @throws \yii\base\ExitException
     * @throws \yii\db\Exception
     */
    public function actionImport()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $model = new RedEnvelope();
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if (empty($model->file)) {
                return $this->message("文件必须上传！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $data = file_get_contents(Yii::getAlias('@root') . '/web' . $model->file);
            $number_list = explode("\r\n", $data);
            $member_ids = Member::find()->where(['in', 'mobile', $number_list])->select(['id'])->column();
            if (empty($member_ids)) {
                return $this->message("暂无会员！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            $rows = [];
            $fields = ['member_id', 'title', 'money', 'created_at'];
            foreach ($member_ids as $v) {
                $rows[] = [$v, $model['title'], $model['money'], time()];
            }
            !empty($rows) && Yii::$app->db->createCommand()->batchInsert(RedEnvelope::tableName(), $fields, $rows)->execute();
            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}

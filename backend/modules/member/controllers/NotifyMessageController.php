<?php

namespace backend\modules\member\controllers;

use backend\modules\member\forms\ImportForm;
use common\helpers\ResultHelper;
use common\models\member\Member;
use common\traits\Curd;
use Yii;
use yii\web\Response;
use common\enums\StatusEnum;
use common\models\base\SearchModel;
use common\models\member\Notify;
use backend\modules\member\forms\NotifyMessageForm;
use backend\controllers\BaseController;
use yii\web\UploadedFile;

/**
 * Class NotifyMessageController
 * @package backend\modules\sys\controllers
 * @author 哈哈
 */
class NotifyMessageController extends BaseController
{
    use Curd;

    /**
     * @var \yii\db\ActiveRecord
     */
    public $modelClass = Notify::class;

    /**
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['content'], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);
        $dataProvider->query
            ->with(['messageMember'])
            ->andWhere(['>=', 'status', StatusEnum::DISABLED])
            ->andWhere(['type' => Notify::TYPE_MESSAGE, 'sender_id' => Yii::$app->user->id]);

        return $this->render($this->action->id, [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * 编辑/创建
     *
     * @return mixed|string|Response
     * @throws \yii\base\ExitException
     */
    public function actionAjaxEdit()
    {
        // 解除内存限制
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $request = Yii::$app->request;
        if ($id = Yii::$app->request->get('id')) {
            $model = Notify::findOne($id);
        } else {
            $model = new NotifyMessageForm();
        }
        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load($request->post())) {
            if ($id) {
                $model->save();
            } else {
                Yii::$app->services->memberNotify->createMessage($model->title, $model->content, Yii::$app->user->id, $model->toManagerId,time());
            }
            return $this->redirect(['index']);
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'id' => $id,
        ]);
    }

    /**
     * 编辑/创建
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
            $member_ids = Member::find()->where(['in', 'mobile', $number_list])->select(['id'])->column();
            if (empty($member_ids)) {
                return $this->message("暂无会员！", $this->redirect(Yii::$app->request->referrer), 'error');
            }
            return Yii::$app->services->memberNotify->createMessage($model->title, $model->content, Yii::$app->user->id, $member_ids,time())
                ? $this->redirect(['index'])
                : $this->message('创建失败', $this->redirect(['index']), 'error');
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}
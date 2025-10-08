<?php

namespace backend\modules\tea\controllers;

use common\helpers\GatewayInit;
use common\helpers\RedisHelper;
use common\models\tea\Notify;
use GatewayClient\Gateway;
use Yii;
use common\models\tea\Detail;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\helpers\Json;

/**
* Detail
*
* Class DetailController
* @package backend\modules\tea\controllers
*/
class DetailController extends BaseController
{
    use Curd;

    /**
    * @var Detail
    */
    public $modelClass = Detail::class;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        GatewayInit::initBase();
        return true;
    }


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
            $model = Detail::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Detail'));
            $post = ['Detail' => $posted];
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

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }

    public function actionPass($id)
    {
        RedisHelper::verify($id, $this->action->id);
        $model = Detail::find()->where(['id' => $id, 'status' => 0])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status =1;
        $model->save();
        // 写入通知
        $dk_notify_model = new Notify();
        $dk_notify_model->member_id = $model->member_id;
        $content = "项目：".$model->bill->project->title."，打卡审核已通过审核！";
        $dk_notify_model->content = $content;
        $dk_notify_model->type=2;
        $dk_notify_model->save();
        //操作socket
        $identity = \Yii::$app->user->identity;
        Gateway::sendToUid("member_".$model->member_id, Json::encode([
            'id' => 'manager_' . $identity->getId(),
            'cid' => $model->id,
            'from_id' => 'manager_' . $identity->getId(),
            'avatar' => '',
            'type' => 'message',
            'content' => $content,
            'group_type' => 'dk_notify',
            'time' => time(),
            'msg_type' => 1,
            'nickname' => "打卡审核",
        ]));
        return $this->message("操作成功！", $this->redirect(Yii::$app->request->referrer));
    }

    public function actionReject($id)
    {
        RedisHelper::verify($id, $this->action->id);
        $model = Detail::find()->where(['id' => $id, 'status' => 0])->one();
        if (empty($model)) {
            return $this->message("该条记录已被操作！", $this->redirect(Yii::$app->request->referrer), 'error');
        }
        $model->status =2;
        $model->save();
        // 写入通知
        $dk_notify_model = new Notify();
        $dk_notify_model->member_id = $model->member_id;
        $content = "项目：".$model->bill->project->title."，打卡审核未通过审核！";
        $dk_notify_model->content = $content;
        $dk_notify_model->type=2;
        $dk_notify_model->save();
        //操作socket
        $identity = \Yii::$app->user->identity;
        Gateway::sendToUid("member_".$model->member_id, Json::encode([
            'id' => 'manager_' . $identity->getId(),
            'cid' => $model->id,
            'from_id' => 'manager_' . $identity->getId(),
            'avatar' => '',
            'type' => 'message',
            'content' => $content,
            'group_type' => 'dk_notify',
            'time' => time(),
            'msg_type' => 1,
            'nickname' => "打卡审核",
        ]));
        return $this->message("操作成功！", $this->redirect(Yii::$app->request->referrer));
    }
}

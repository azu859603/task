<?php

namespace backend\modules\tea\controllers;

use common\helpers\GatewayInit;
use common\helpers\ImageHelper;
use GatewayClient\Gateway;
use Yii;
use common\models\tea\Notify;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;
use yii\helpers\Json;

/**
* Notify
*
* Class NotifyController
* @package backend\modules\tea\controllers
*/
class NotifyController extends BaseController
{
    use Curd;

    /**
    * @var Notify
    */
    public $modelClass = Notify::class;

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
            $model = Notify::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('Notify'));
            $post = ['Notify' => $posted];
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
                $identity = \Yii::$app->user->identity;
                Gateway::sendToGroup('notify', Json::encode([
                    'id' => 'manager_' . $identity->getId(),
                    'cid' => $model->id,
                    'from_id' => 'manager_' . $identity->getId(),
                    'avatar' => '',
                    'type' => 'message',
                    'content' => $model['content'],
                    'group_type' => 'notify',
                    'time' => time(),
                    'msg_type' => 1,
                    'nickname' => "系统公告",
                ]));
                return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
            }else{
                return $this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}

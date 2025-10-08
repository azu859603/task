<?php

namespace backend\modules\tea\controllers;

use backend\controllers\BaseController;
use backend\modules\tea\forms\ChangeForm;
use common\enums\StatusEnum;
use common\helpers\ArrayHelper;
use common\helpers\GatewayInit;
use common\models\member\Member;
use common\models\tea\ChatLog;
use GatewayClient\Gateway;
use Yii;
use common\models\tea\Room;
use common\traits\Curd;
use common\models\base\SearchModel;
use yii\helpers\Json;

/**
 * Room
 *
 * Class RoomController
 * @package backend\modules\room\controllers
 */
class RoomController extends BaseController
{
    use Curd;

    /**
     * @var Room
     */
    public $modelClass = Room::class;


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
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        $searchModel = new SearchModel([
            'model' => $this->modelClass,
            'scenario' => 'default',
            'partialMatchAttributes' => ['name'], // 模糊查询
            'defaultOrder' => [
                'id' => SORT_DESC
            ],
            'pageSize' => $this->pageSize
        ]);

        $dataProvider = $searchModel
            ->search(Yii::$app->request->queryParams);

        $dataProvider->query
            ->andWhere(['>=', 'status', StatusEnum::DISABLED]);
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
            'statusExplain' => Room::$statusExplain,
        ]);
    }

    /**
     *  编辑房间状态
     * @return string
     */
    public function actionChangeStatus()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');
            $model = Room::findOne(['id' => $id]);
            $output = '';
            $posted = current($_POST['Room']);
            $post = ['Room' => $posted];
            if ($model->load($post)) {
                $model->save();
                if (isset($posted['status'])) {
                    $output = Room::$statusExplain[$model->status];
                    if ($model->status == Room::STATUS_GAG) {
                        $content = '本房间已禁言';
                    } elseif ($model->status == Room::STATUS_DISABLED) {
                        $content = '本房间已禁用';
                    } else {
                        $content = '房间解除限制';
                    }
                    Gateway::sendToGroup($model->id, Json::encode([
                        'id' => $model->id,
                        'type' => 'cmd',
                        'order' => 'room_status_change',
                        'data' => $model->status,
                        'content' => $content,
                        'room_name' => $model->name,
                        'group_type' => 'group'
                    ]));
                    if ($model->status == Room::STATUS_DISABLED) {
                        // 房间禁用，踢出所有用户
                        Gateway::sendToGroup($model->id, Json::encode([
                            'id' => $model->id,
                            'type' => 'cmd',
                            'order' => 'logout',
                        ]));
                    }
                }
            }
            $out = Json::encode(['output' => $output]);
            return $out;
        }
    }

    /**
     *  列表编辑
     * @return string
     */
    public function actionTableEdit()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');
            $model = Room::findOne(['id' => $id]);
            $output = '';
            $posted = current($_POST['Room']);
            $post = ['Room' => $posted];
            if ($model->load($post)) {
                $model->save();
                isset($posted['notice']) && $output = $model->notice;
            }
            $out = Json::encode(['output' => $output]);
            return $out;
        }
    }


    /**
     * 详情
     * @param $id
     * @return string
     */
    public function actionView($id)
    {
        $model = Member::find()
            ->select(['mobile'])
            ->where(new \yii\db\Expression('FIND_IN_SET(:room_ids,room_ids)'))
            ->addParams([':room_ids' => $id])
            ->asArray()
            ->column();
        $model = array_chunk($model, 3);

        $b_model = \common\models\backend\Member::find()
            ->select(['username'])
            ->where(new \yii\db\Expression('FIND_IN_SET(:room_ids,room_ids)'))
            ->addParams([':room_ids' => $id])
            ->asArray()
            ->column();
        $b_model = array_chunk($b_model, 3);
        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'b_model' => $b_model,
        ]);
    }

    public function actionAdd()
    {
        $id = Yii::$app->request->get('id');
        $model = new ChangeForm();
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if($model['type'] == 1){
                $member_id = $model['member_id'];
                $member = Member::find()->where(['id' => $member_id])->one();
                $u_id = "member_".$member_id;
            }else{
                $member_id = $model['b_id'];
                $member = \common\models\backend\Member::find()->where(['id' => $member_id])->one();
                $u_id = "manage_".$member_id;
            }
            if (!empty($member)) {
                $room_ids = !empty($member->room_ids) ? $member->room_ids : [];
                if (in_array($id, $room_ids)) {
                    return $this->message("操作失败，该成员已在群组里", $this->redirect(Yii::$app->request->referrer), 'error');
                }
                $room_ids[] = $id;
                $member->room_ids = $room_ids;
                $member->save(false);
                // 操作wokerman
                $client_id = Gateway::getClientIdByUid($u_id);
                if (!empty($client_id)) {
                    Gateway::joinGroup($client_id[0], $id);
                    // 通知用户已进群
                    $room = Room::findOne($id);
                    ChatLog::$room_id = $id;
                    $last_content = ChatLog::find()->where(['status' => 1])->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])->asArray()->one();
                    Gateway::sendToUid($u_id, Json::encode([
                        'id' => $room->id,
                        'name' => $room->name,
                        'avatar' => $room->avatar,
                        'last_content' => $last_content['content'],
                        'time' => $last_content['created_at'],
                        'type' => $last_content['msg_type'],
                        'group_type' => 'join_group',
                    ]));
                }
            }
            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }


    public function actionSub()
    {
        $id = Yii::$app->request->get('id');
        $model = new ChangeForm();
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            $member_id = $model['member_id'];
            $member = Member::find()->where(['id' => $member_id])->one();
            if (!empty($member)) {
                $room_ids = !empty($member->room_ids) ? $member->room_ids : [];
                if (!in_array($id, $room_ids)) {
                    return $this->message("操作失败，该成员不在群组里", $this->redirect(Yii::$app->request->referrer), 'error');
                }
                $room_ids = array_diff($room_ids,[$id]);
                $member->room_ids = !empty($room_ids) ? $room_ids : "";
                $member->save();
                // 操作wokerman
                $client_id = Gateway::getClientIdByUid("member_".$member_id);
                if (!empty($client_id)) {
                    Gateway::leaveGroup($client_id[0], $id);
                    Gateway::sendToUid("member_".$member_id, Json::encode([
                        'id' => $id,
                        'group_type' => 'leave_group',
                    ]));
                }
            }
            return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
        }
        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }
}

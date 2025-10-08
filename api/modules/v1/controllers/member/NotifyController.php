<?php


namespace api\modules\v1\controllers\member;


use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\member\Member;
use common\models\member\NotifyMember;
use yii\data\ActiveDataProvider;
use Yii;

class NotifyController extends OnAuthController
{
    public $modelClass = NotifyMember::class;


    public function actionIndex()
    {
        // 首先拉取消息列表
//        // 拉取公告
//        $model = Member::find()->where(['id' => $this->memberId])->select(['created_at'])->asArray()->one();
//        Yii::$app->services->memberNotify->pullAnnounce($this->memberId, $model['created_at']);
        Yii::$app->services->memberNotify->pullAnnounce($this->memberId);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'is_read',
                    'notify_id',
                ])
                ->where(['member_id' => $this->memberId, 'status' => StatusEnum::ENABLED])
                ->andWhere(['<=', 'created_at', time()])
                ->with(['notify' => function ($query) {
                    $query->select(['id', 'title', 'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',]);
                }])
                ->orderBy('created_at desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 标记全部已读
     * @return array|mixed
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionRead()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        Yii::$app->services->memberNotify->readAll($this->memberId);
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "操作成功");
    }


    /**
     * 详情
     * @return array|mixed|\yii\db\ActiveRecord|null
     */
    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        if (empty($id) || empty(($model = NotifyMember::find()
                ->select([
                    'id',
                    'is_read',
                    'notify_id',
                ])
//                ->where(['id' => $id, 'member_id' => $this->memberId, 'status' => StatusEnum::ENABLED])
                ->where(['id' => $id, 'member_id' => $this->memberId])
                ->with(['notify' => function ($query) {
                    $query->select(['id', 'title', 'content', 'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',]);
                }])
                ->asArray()
                ->one()
            ))) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "找不到该公告");
        }
        // 设置公告为已读
        Yii::$app->services->memberNotify->read($this->memberId, [$model['notify_id']]);
        return $model;
    }

    /**
     * 权限验证
     *
     * @param string $action 当前的方法
     * @param null $model 当前的模型类
     * @param array $params $_GET变量
     * @throws \yii\web\BadRequestHttpException
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['create', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
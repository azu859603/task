<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/8
 * Time: 2:44
 */

namespace api\modules\v1\controllers\task;


use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\helpers\BcHelper;
use common\helpers\DateHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\Languages;
use common\models\common\Statistics;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\task\LaberList;
use common\models\task\Order;
use common\models\task\Project;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\Json;

class TaskOrderController extends OnAuthController
{
    public $modelClass = Order::class;


    /**
     * 我的任务列表
     */
    public function actionIndex()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $status = Yii::$app->request->get('status');
        if (isset($status)) {
            $statusWhere = ['status' => $status];
        } else {
            $statusWhere = [];
        }
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['member_id' => $this->memberId])
                ->andFilterWhere($statusWhere)
                ->with([
                    'project' => function ($query) use ($lang) {
                        $query->with([
                            'translation' => function ($query) use ($lang) {
                                $query->where(['lang' => $lang]);
                            }
                        ]);
                    }
                ])
                ->orderBy(['created_at' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }


    /**
     * 领取任务
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);

        $id = Yii::$app->request->post('id');
        $project = Project::find()->where(['id' => $id, 'status' => StatusEnum::ENABLED])->andWhere(['>', 'remain_number', 0])->one();
        if (empty($project)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '任务数量不足');
        }
        $memberInfo = Member::findOne($this->memberId);
        if ($project->vip_level > $memberInfo->current_level) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '当前等级无法领取改任务');
        }
        $today = DateHelper::today();
        if (Order::find()->where(['member_id' => $this->memberId, 'pid' => $id])->andWhere(['between', 'created_at', $today['start'], $today['end']])->count() >= $project->limit_number) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该任务领取次数超过限制');
        }
        if(Order::find()->where(['member_id' => $this->memberId, 'pid' => $id])->count() >= $project->member_limit_number){
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该任务领取次数超过限制');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $project->remain_number -= 1;
            $project->save();

            $order = new Order();
            $order->member_id = $this->memberId;
            $order->pid = $id;
            $order->cid = $project->pid;
            $order->created_at = time();
            $order->money = $project->money;
            $order->save();

            // 加入统计表
            if ($memberInfo['type'] == 1) {
                // 加入统计表 获取最上级用户ID
                $first_member = Member::getParentsFirst($memberInfo);
                $b_id = $first_member['b_id'] ?? 0;
                Statistics::updateGetTask(date("Y-m-d"), $b_id);
            }

            $transaction->commit();
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "领取成功");
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ResultHelper::json(ResultHelper::ERROR_CODE, $e->getMessage());
        }
    }

    /**
     * 提交任务
     */
    public function actionPush()
    {
        RedisHelper::verify($this->memberId, $this->action->id);

        $id = Yii::$app->request->post('id');
        $push_number = Yii::$app->debris->backendConfig('push_number') ?? 1;
        $order = Order::find()
            ->where(['id' => $id])
            ->andWhere(['<', 'push_number', $push_number])
            ->andWhere(['or', ['status' => 0], ['status' => 3]])
            ->one();
        if (empty($order)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '该订单无法提交任务');
        }

        $images_list = Yii::$app->request->post('images_list');
        if (empty($images_list) && !is_array($images_list)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, '截图必须提交');
        }
        $video_url = Yii::$app->request->post('video_url');
        $order->push_number += 1;
        $order->images_list = $images_list;
        $order->video_url = $video_url;
        $order->status = 1;
        $order->save();
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, "提交成功，请等待管理员审核");
    }


    /**
     * 任务详情
     */
    public function actionDetail()
    {
        $default_lang_model = Languages::find()->select(['code'])->where(['is_default' => 1])->one();
        $default_lang = !empty($default_lang_model) ? $default_lang_model['code'] : "cn";
        $lang = Yii::$app->request->get('lang', $default_lang);
        $id = Yii::$app->request->get('id');
        return $this->modelClass::find()
            ->where(['id' => $id])
            ->with([
                'project' => function ($query) use ($lang) {
                    $query->with([
                        'translation' => function ($query) use ($lang) {
                            $query->where(['lang' => $lang]);
                        }
                    ]);
                }
            ])
            ->asArray()
            ->one();
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
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
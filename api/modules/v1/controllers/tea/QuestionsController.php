<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\helpers\DateHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\forms\CreditsLogForm;
use common\models\member\CreditsLog;
use common\models\member\Member;
use common\models\tea\AnswerList;
use common\models\tea\QuestionsList;
use Yii;

class QuestionsController extends OnAuthController
{
    public $modelClass = QuestionsList::class;

    /**
     * 问题
     * @return array|QuestionsList|\yii\data\ActiveDataProvider|\yii\db\ActiveRecord|null
     */
    public function actionIndex()
    {
        // 首先判断今日是否答题
        $today = DateHelper::today();
        $answer = AnswerList::find()
            ->where(['member_id' => $this->memberId])
            ->andWhere(['between', 'created_at', $today['start'], $today['end']])
            ->asArray()
            ->one();
        if (!empty($answer)) {
            $model = QuestionsList::find()
                ->where(['id' => $answer['q_id']])
                ->select(['id', 'title', 'content', 'answer', 'type'])
                ->asArray()
                ->one();
            $model['todayAnswer'] = $answer['answer'];
            return $model;
        }
        $answer_list = AnswerList::find()->select(['q_id'])->where(['member_id' => $this->memberId])->column();
        // 如果没有答题
        return QuestionsList::find()
            ->select(['id', 'title', 'content', 'type'])
            ->where(['not in', 'id', $answer_list])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->one();
    }


    /**
     * 答题
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);

        // 首先判断今日是否答题
        $today = DateHelper::today();
        if (AnswerList::find()->where(['member_id' => $this->memberId])->andWhere(['between', 'created_at', $today['start'], $today['end']])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "今天您已经答过题啦！");
        }
        $q_id = Yii::$app->request->post('q_id');
        if (empty($questions = QuestionsList::find()->where(['id' => $q_id])->select(['answer'])->asArray()->one())) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "题目不能为空！");
        }
        // 判断该题目是否已经答过
        if (AnswerList::find()->where(['q_id' => $q_id, 'member_id' => $this->memberId])->exists()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "该题目您已经作答！");
        }
        $model = new AnswerList();
        $model->attributes = Yii::$app->request->post();
        if (!$model->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($model));
        }
        $model->save();
        // 判断答案
        if ($model->answer != $questions['answer']) {
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, "回答错误！", ['answer' => $questions['answer'], 'is_true' => 0]);
        }
        $memberInfo = Member::find()->where(['id' => $this->memberId])->with(['memberLevel'])->one();
        if ($memberInfo->memberLevel->q_a_number > 0) {
            Yii::$app->services->memberCreditsLog->incrInt(new CreditsLogForm([
                'member' => $memberInfo,
                'pay_type' => CreditsLog::Q_A,
                'num' => $memberInfo->memberLevel->q_a_number,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【系统】回答正确赠送积分",
            ]));
            $message = "获得" . $memberInfo->memberLevel->q_a_number . "点积分";
            $type = 1;
        }
        if ($memberInfo->memberLevel->q_a_money > 0) {
            Yii::$app->services->memberCreditsLog->incrMoney(new CreditsLogForm([
                'member' => $memberInfo,
                'pay_type' => CreditsLog::Q_A,
                'num' => $memberInfo->memberLevel->q_a_money,
                'credit_group' => CreditsLog::CREDIT_GROUP_MEMBER,
                'remark' => "【系统】回答正确赠送奖金",
            ]));
            $message = "获得" . $memberInfo->memberLevel->q_a_money . "元奖金";
            $type = 2;
        }
        return ResultHelper::json(ResultHelper::SUCCESS_CODE, $message, ['answer' => $questions['answer'], 'is_true' => 1, 'type' => $type]);
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
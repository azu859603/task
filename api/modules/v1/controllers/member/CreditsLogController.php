<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/9
 * Time: 14:09
 */

namespace api\modules\v1\controllers\member;


use api\controllers\OnAuthController;
use api\modules\v1\forms\member\CreditsLogForm;
use common\helpers\DateHelper;
use common\helpers\ResultHelper;
use common\models\member\CreditsLog;
use Yii;

class CreditsLogController extends OnAuthController
{
    public $modelClass = CreditsLog::class;

    protected $authOptional = ['list'];

    public function actionIndex()
    {
        $form = new CreditsLogForm();
        $form->attributes = Yii::$app->request->get();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }
        $return_data = CreditsLog::getLists($this->memberId, $form->page, $form->pay_type, $form->credit_type, $this->pageSize);
        return $return_data;
    }

    public function actionList()
    {
        $type = Yii::$app->request->get('type');
        $today = DateHelper::today();
        if ($type == "7day") {
            $time['start'] = $today['start'] - (86400 * 7);
            $time['end'] = $today['end'];
        } elseif ($type == "30day") {
            $time['start'] = $today['start'] - (86400 * 30);
            $time['end'] = $today['end'];
        } else {
            $time = $today;
        }
        return CreditsLog::find()->select(['member_id', 'sum(`num`) as `income`'])
            ->where(['pay_type' => CreditsLog::INCOME_TYPE])
            ->andWhere(['between', 'created_at', $time['start'], $time['end']])
            ->groupBy(['member_id'])
            ->asArray()
            ->with(['member' => function ($query) {
                $query->select(['id', 'head_portrait', 'nickname']);
            }])
            ->orderBy(['income' => SORT_DESC])
            ->limit(5)
            ->all();
    }

    /**
     * 权限验证
     * @param string $action
     * @param null $model
     * @param array $params
     * @throws \yii\web\BadRequestHttpException
     * @author 原创脉冲
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // 方法名称
        if (in_array($action, ['view', 'update', 'create', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
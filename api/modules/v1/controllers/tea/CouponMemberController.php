<?php

namespace api\modules\v1\controllers\tea;

use api\controllers\OnAuthController;
use common\models\tea\CouponMember;
use Yii;
use yii\data\ActiveDataProvider;

class CouponMemberController extends OnAuthController
{
    public $modelClass = CouponMember::class;


    public function actionIndex()
    {
        $type = Yii::$app->request->get('type');
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['member_id' => $this->memberId, 'type' => $type])
                ->select([
                    'id',
                    'c_id',
                    'FROM_UNIXTIME(`stop_time`,\'%Y-%m-%d %H:%i:%s\') as stop_time',
                    'status',
                ])
                ->with(['coupon' => function ($query) {
                    $query->select(['id', 'number', 'max']);
                }])
                ->orderBy('id desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
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
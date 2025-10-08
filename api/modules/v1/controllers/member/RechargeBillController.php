<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/9
 * Time: 18:33
 */

namespace api\modules\v1\controllers\member;


use api\controllers\OnAuthController;
use common\models\member\RechargeBill;
use common\models\member\RechargeDetail;
use yii\data\ActiveDataProvider;
use Yii;

class RechargeBillController extends OnAuthController
{
    public $modelClass = RechargeBill::class;


    protected $authOptional = ['list'];

    public function actionList()
    {
        return RechargeDetail::find()
            ->where(['status' => 1])
            ->with(['category'=>function($query){
                $query->select(['id','type']);
            }])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
            ->asArray()
            ->all();
    }

    /**
     * 充值订单列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $status = Yii::$app->request->get('status', 1);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['member_id' => $this->memberId, 'status' => $status])
                ->orderBy('created_at desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
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
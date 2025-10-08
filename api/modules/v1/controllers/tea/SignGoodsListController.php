<?php


namespace api\modules\v1\controllers\tea;


use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\models\tea\SignGoodsList;
use yii\data\ActiveDataProvider;
use Yii;

class SignGoodsListController extends OnAuthController
{

    public $modelClass = SignGoodsList::class;


    protected $authOptional = ['index', 'detail'];

    public function actionIndex()
    {
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'banner', // 封面图
                    'title', // 标题
                    'sign_day', // 连续签到天数
                    'total_amount',  // 总共数量
                    'remaining_amount', // 剩余数量D
                    'type', // 类型
                ])
                ->where(['status' => StatusEnum::ENABLED])
                ->orderBy(['sort' => SORT_ASC, 'created_at' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        return SignGoodsList::find()
            ->where(['id' => $id, 'status' => StatusEnum::ENABLED])
            ->select([
                'id',
                'type', // 类型
                'banner', // 封面图
                'title', // 标题
                'content', // 详情(富文本)
                'sign_day', // 连续签到天数
                'total_amount', // 总共数量
                'remaining_amount', // 剩余数量
                'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at', // 发布时间
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
        if (in_array($action, ['create', 'view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
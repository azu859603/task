<?php


namespace api\modules\v1\controllers\tea;


use api\controllers\OnAuthController;
use common\helpers\ResultHelper;
use common\models\tea\InvestmentProject;
use yii\data\ActiveDataProvider;
use Yii;

class InvestmentProjectController extends OnAuthController
{
    public $modelClass = InvestmentProject::class;


    protected $authOptional = ['index', 'detail', 'list'];


    /**
     * 项目列表
     * @return ActiveDataProvider
     */
    public function actionList()
    {
//        $type = Yii::$app->request->get('type', 1);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'category', // 类型
                    'title', // 标题
                    'project_img',// 图片
                    'schedule', // 进度百分比
                    'least_amount', // 起投金额
                    'deadline', // 天数
                    'income', // 收益率
                    'status', // 状态
                    'gift_amount', // 红包金额
                    'gift_method', // 赠送类型
                    'lottery_number', // 抽奖次数
                    'spike_type', // 秒杀类型
                    'prize_type', // 奖品赠送
                    'gift_instruction', // 红包赠送说明
                    'lottery_instruction', // 抽奖赠送说明
                    'prize_instruction', // 奖品赠送说明
                    'all_investment_amount', // 项目规模
                    'limit_times', // 限投次数
                    'spike_start_time', // 秒杀开始时间
                    'spike_stop_time', // 秒杀结束时间
                    'return_method', // 返现类型
                    'return_instruction', // 返现说明
                    'describe', // 项目描述
                    'vip_level', // VIP等级
                ])
                ->where(['project_status' => 1, 'home_show_switch' => 1])
                ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }


    /**
     * 项目列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
//        $type = Yii::$app->request->get('type', 1);
        return new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select([
                    'id',
                    'category', // 类型
                    'title', // 标题
                    'project_img',// 图片
                    'schedule', // 进度百分比
                    'least_amount', // 起投金额
                    'deadline', // 天数
                    'income', // 收益率
                    'status', // 状态
                    'gift_amount', // 红包金额
                    'gift_method', // 赠送类型
                    'lottery_number', // 抽奖次数
                    'spike_type', // 秒杀类型
                    'prize_type', // 奖品赠送
                    'gift_instruction', // 红包赠送说明
                    'lottery_instruction', // 抽奖赠送说明
                    'prize_instruction', // 奖品赠送说明
                    'all_investment_amount', // 项目规模
                    'limit_times', // 限投次数
                    'spike_start_time', // 秒杀开始时间
                    'spike_stop_time', // 秒杀结束时间
                    'return_method', // 返现类型
                    'return_instruction', // 返现说明
                    'describe', // 项目描述
                    'vip_level', // VIP等级
                ])
                ->where(['project_status' => 1])
                ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }


    /**
     * 项目详情
     */
    public function actionDetail()
    {
        $id = Yii::$app->request->get('id');
        if (empty($id)) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "ID必须填写！");
        }

        $model = InvestmentProject::find()
            ->select([
                'id',
                'category', // 类型
                'title', // 标题
                'project_img', // 图片
                'least_amount', // 起够价格
                'income', // 收益率
                'deadline', // 天数
                'schedule', // 进度百分比
                'status', // 状态
                'gift_amount', // 红包金额
                'gift_method', // 赠送类型
                'lottery_number', // 抽奖次数
                'spike_type', // 秒杀类型
                'prize_type', // 奖品赠送
                'gift_instruction', // 红包赠送说明
                'lottery_instruction', // 抽奖赠送说明
                'prize_instruction', // 奖品赠送说明
                'all_investment_amount', // 项目规模
                'limit_times', // 限投次数
                'project_detail', // 项目详情
                'spike_start_time', // 秒杀开始时间
                'spike_stop_time', // 秒杀结束时间
                'return_method', // 返现类型
                'return_instruction', // 返现说明
                'commission_one', // 一级返佣
                'commission_two', // 二级返佣
                'vip_level', // VIP等级
                'send_gift_switch', // 赠送礼品
            ])
            ->where(['id' => $id, 'project_status' => 1])
            ->asArray()
            ->one();
        $model['guarantee'] = Yii::$app->debris->config('guarantee');
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
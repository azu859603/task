<?php

namespace api\modules\v1\controllers\common;

use api\controllers\OnAuthController;
use common\enums\StatusEnum;
use common\models\common\WebList;
use yii\data\ActiveDataProvider;
use Yii;

class WebListController extends OnAuthController
{
    public $modelClass = WebList::class;
    // 不用进行登录验证的方法
    protected $authOptional = ['index'];
    // 不用进行签名验证的方法
    protected $signOptional = ['index'];

    public function actionIndex()
    {
        $model = new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->where(['status' => StatusEnum::ENABLED])
                ->orderBy(['sort' => SORT_ASC, 'id' => SORT_DESC])
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
        $web_list = [];
        foreach ($model->getModels() as $k => $v) {
            $web_list[$k]['web_url'] = $v['web_url'];
            $web_list[$k]['time'] = rand(20, 60);
        }
        $data['list'] = $web_list;
        $data['my_company_name'] = Yii::$app->debris->config('my_company_name');
        $data['customer_service_link'] = Yii::$app->debris->config('customer_service_link');
        $data['web_logo'] = Yii::$app->debris->config('web_logo');
        $data['app_download_link'] = Yii::$app->debris->config('app_download_link');
        return $data;

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
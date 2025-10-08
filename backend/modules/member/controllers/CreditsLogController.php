<?php

namespace backend\modules\member\controllers;

use common\models\member\Member;
use common\traits\Curd;
use Yii;
use common\models\base\SearchModel;
use common\models\member\CreditsLog;
use common\enums\StatusEnum;
use backend\controllers\BaseController;
use yii\web\Response;

/**
 * Class CreditsLogController
 * @package backend\modules\member\controllers
 * @author 原创脉冲
 */
class CreditsLogController extends BaseController
{
    use Curd;

    public $modelClass = CreditsLog::class;

    /**
     * 消费日志
     * @return array|string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = CreditsLog::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('CreditsLog'));
            $post = ['CreditsLog' => $posted];
            if ($model->load($post) && $model->save()) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => CreditsLog::class,
                'scenario' => 'default',
                'partialMatchAttributes' => ['realname', 'mobile', 'member_id'], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query
                ->andWhere(['>=', 'status', StatusEnum::DISABLED])
                ->andWhere(['credit_type' => CreditsLog::CREDIT_TYPE_CONSUME_MONEY])
                ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
                ->with('member');

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'title' => '消费日志'
            ]);
        }
    }

    /**
     * 余额日志
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionMoney()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = CreditsLog::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('CreditsLog'));
            $post = ['CreditsLog' => $posted];
            if ($model->load($post) && $model->save()) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => CreditsLog::class,
                'scenario' => 'default',
                'partialMatchAttributes' => ['realname', 'mobile', 'member_id'], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query
                ->andWhere(['>=', 'status', StatusEnum::DISABLED])
//                ->andWhere(['in', 'credit_type', [CreditsLog::CREDIT_TYPE_USER_MONEY, CreditsLog::CREDIT_TYPE_GIVE_MONEY]])
                ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
                ->with('member');

            $backend_id = Yii::$app->user->identity->getId();
            if ($backend_id != 1) {
                $a_id = Yii::$app->user->identity->aMember->id;
                $childrenIds = Member::getChildrenIds($a_id);
                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
            }

            $sum_num = $dataProvider->query->sum('num')??0;

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
//                'title' => '余额日志',
                'title' => '流水日志',
                'sum_num' => $sum_num,
            ]);
        }
    }

    /**
     * 积分日志
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionIntegral()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = CreditsLog::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('CreditsLog'));
            $post = ['CreditsLog' => $posted];
            if ($model->load($post) && $model->save()) {
                $output = $model->$attribute;
            } else {
                //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => CreditsLog::class,
                'scenario' => 'default',
                'partialMatchAttributes' => ['realname', 'mobile', 'member_id'], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);
            $dataProvider->query
                ->andWhere(['>=', 'status', StatusEnum::DISABLED])
                ->andWhere(['in', 'credit_type', [CreditsLog::CREDIT_TYPE_USER_INTEGRAL, CreditsLog::CREDIT_TYPE_GIVE_INTEGRAL]])
                ->andFilterWhere(['merchant_id' => $this->getMerchantId()])
                ->with('member');

            $sum_num = $dataProvider->query->sum('num')??0;

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
                'title' => '积分日志',
                'sum_num' => $sum_num,
            ]);
        }
    }
}
<?php

namespace backend\modules\member\controllers;

use common\models\member\Member;
use Yii;
use common\models\member\MemberCard;
use common\traits\Curd;
use common\models\base\SearchModel;
use backend\controllers\BaseController;

/**
* MemberCard
*
* Class MemberCardController
* @package backend\modules\member\controllers
*/
class MemberCardController extends BaseController
{
    use Curd;

    /**
    * @var MemberCard
    */
    public $modelClass = MemberCard::class;


    /**
    * 首页
    * @return array|string
    * @throws \yii\web\NotFoundHttpException
    */
    public function actionIndex()
    {
        if (Yii::$app->request->post('hasEditable')) {
            $id = Yii::$app->request->post('editableKey');//获取ID
            Yii::$app->response->format = yii\web\Response::FORMAT_JSON;
            $model = MemberCard::findOne($id);
            $output = '';
            $message = '';
            //由于传递的数据是二维数组，将其转为一维
            $attribute = Yii::$app->request->post('editableAttribute');//获取名称
            $posted = current(Yii::$app->request->post('MemberCard'));
            $post = ['MemberCard' => $posted];
            if ($model->load($post) && $model->save()) {
                Yii::$app->services->actionLog->create('member-card/index', '修改银行卡信息');
                $output = $model->$attribute;
            } else {
            //由于本插件不会自动捕捉model的error，所以需要放在$message中展示出来
                $message = $model->getFirstError($attribute);
            }
            return ['output' => $output, 'message' => $message];
        } else {
            $searchModel = new SearchModel([
                'model' => $this->modelClass,
                'scenario' => 'default',
                'partialMatchAttributes' => [], // 模糊查询
                'defaultOrder' => [
                    'id' => SORT_DESC
                ],
                'pageSize' => $this->pageSize
            ]);

            $dataProvider = $searchModel
                ->search(Yii::$app->request->queryParams);

//            $backend_id = Yii::$app->user->identity->getId();
//            if ($backend_id != 1) {
//                $a_id = Yii::$app->user->identity->aMember->id;
//                $childrenIds = Member::getChildrenIds($a_id);
//                $dataProvider->query->andFilterWhere(['in', 'member_id', $childrenIds]);
//            }

            return $this->render('index', [
                'dataProvider' => $dataProvider,
                'searchModel' => $searchModel,
            ]);
        }
    }

    /**
     * ajax编辑/创建
     * @return mixed
     */
    public function actionAjaxEdit()
    {
        $id = Yii::$app->request->get('id');
        $model = $this->findModel($id);

        // ajax 校验
        $this->activeFormValidate($model);
        if ($model->load(Yii::$app->request->post())) {
            if($model->save()){
                Yii::$app->services->actionLog->create('member-card/ajax-edit', '新增银行卡信息');
                return $this->message("操作成功", $this->redirect(Yii::$app->request->referrer));
            }else{
                return$this->message($this->getError($model), $this->redirect(Yii::$app->request->referrer), 'error');
            }
        }

        return $this->renderAjax($this->action->id, [
            'model' => $model,
        ]);
    }

    /**
     * 删除
     *
     * @param $id
     * @return mixed
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete($id)
    {
        if ($this->findModel($id)->delete()) {
            Yii::$app->services->actionLog->create('member-card/delete', '删除银行卡信息');
            return $this->message("删除成功", $this->redirect(Yii::$app->request->referrer));
        }

        return $this->message("删除失败", $this->redirect(Yii::$app->request->referrer), 'error');
    }
}

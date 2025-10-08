<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/17
 * Time: 0:57
 */

namespace api\modules\v1\controllers\common;


use api\controllers\OnAuthController;
use api\modules\v1\forms\common\OpinionListForm;
use common\helpers\ArrayHelper;
use common\helpers\RedisHelper;
use common\helpers\ResultHelper;
use common\models\common\OpinionList;
use Yii;
use yii\data\ActiveDataProvider;

class OpinionListController extends OnAuthController
{
    public $modelClass = OpinionList::class;


    /**
     * 列表
     * @return array|ActiveDataProvider
     */
    public function actionIndex()
    {
        $model = new ActiveDataProvider([
            'query' => $this->modelClass::find()
                ->select(['id', 'content', 'img_list', 'remark', 'FROM_UNIXTIME(`created_at`,\'%Y-%m-%d %H:%i:%s\') as created_at',])
                ->where(['member_id' => $this->memberId])
                ->orderBy('created_at desc')
                ->asArray(),
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
        $model = $model->getModels();
        foreach ($model as $k => $v) {
            if (empty($v['img_list'])) {
                $model[$k]['img_list'] = [];
            } else {
                $model[$k]['img_list'] = unserialize($v['img_list']);
            }
        }
        return $model;
    }


    /**
     * 提交意见反馈
     * @return array|mixed|\yii\db\ActiveRecord
     * @throws \yii\web\UnprocessableEntityHttpException
     */
    public function actionCreate()
    {
        RedisHelper::verify($this->memberId, $this->action->id);
        $form = new OpinionListForm();
        $form->attributes = Yii::$app->request->post();
        if (!$form->validate()) {
            return ResultHelper::json(ResultHelper::ERROR_CODE, $this->getError($form));
        }
        $model = new OpinionList();
        $opinionList = ArrayHelper::toArray($form);
        if (!empty($opinionList['img_list'][0])) {
            $opinionList['img_list'] = serialize($opinionList['img_list']);
        } else {
            unset($opinionList['img_list']);
        }
        $model->attributes = $opinionList;
        if ($model->save()) {
            return ResultHelper::json(ResultHelper::SUCCESS_CODE, '提交成功');
        } else {
            return ResultHelper::json(ResultHelper::ERROR_CODE, "提交失败");
        }

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
        if (in_array($action, ['view', 'update', 'delete'])) {
            throw new \yii\web\BadRequestHttpException('权限不足');
        }
    }
}
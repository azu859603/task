<?php
/**
 * Created by PhpStorm.
 * User: Serlon
 * Date: 2020/7/10
 * Time: 14:07
 */

namespace backend\modules\member\controllers;

use common\models\member\Account;
use common\models\member\WithdrawBill;
use Yii;

use backend\controllers\BaseController;

class AccountController extends BaseController
{
    /**
     * 查看账户信息
     *
     * @return mixed|string|\yii\web\Response
     * @throws \yii\base\ExitException
     */
    public function actionView()
    {
        $id = Yii::$app->request->get('id');
        $withdraw = WithdrawBill::find()->where(['id' => $id])->with(['card'])->asArray()->one();
        $model = Account::find()->where(['member_id' => $withdraw['member_id']])->with(['member'])->one();
        $type = $withdraw['type'];
        $withdraw_money = $withdraw['withdraw_money'];
        $bank_card = !empty($withdraw['card']['bank_card']) ? $withdraw['card']['bank_card'] : "已删除";
        $bank_address = !empty($withdraw['card']['bank_address']) ? $withdraw['card']['bank_address'] : "已删除";
        $username = !empty($withdraw['card']['bank_address']) ? $withdraw['card']['username'] : "已删除";

        return $this->renderAjax($this->action->id, [
            'model' => $model,
            'type' => $type,
            'withdraw_money' => $withdraw_money,
            'bank_card' => $bank_card,
            'bank_address' => $bank_address,
            'username' => $username,
        ]);
    }
}
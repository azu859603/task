<?php

use common\helpers\Url;
use common\helpers\Html;
use common\enums\StatusEnum;

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <h4 class="modal-title">会员信息</h4>
</div>
<div class="modal-body">
    <table class="table table-hover text-center">
        <tbody>
        <tr>
            <td>会员账号</td>
            <td>
                <?= $model->mobile; ?>
            </td>
        </tr>
        <tr>
            <td>昵称</td>
            <td>
                <?= $model->nickname; ?>
            </td>
        </tr>
        <tr>
            <td>真实姓名</td>
            <td>
                <?= !empty($model->realname) ? $model->realname : "(暂无)"; ?>
            </td>
        </tr>

        <tr>
            <td>注册时间</td>
            <td>
                <?= \common\helpers\DateHelper::dateTime($model->created_at); ?>
            </td>
        </tr>

        <tr>
            <td>上次购买时间</td>
            <td>
                <?php if ($model->investment_time == 0) {
                    echo "(从未购买)";
                } else {
                    echo \common\helpers\DateHelper::dateTime($model->investment_time);
                } ?>
            </td>
        </tr>

        <tr>
            <td>预存余额</td>
            <td>
                <?= $model->account->user_money; ?>
            </td>
        </tr>

        <tr>
            <td>钱包余额</td>
            <td>
                <?= $model->account->can_withdraw_money; ?>
            </td>
        </tr>

        <tr>
            <td>充值金额</td>
            <td>
                <?= $recharge_money; ?>
            </td>
        </tr>

        <tr>
            <td>提现金额</td>
            <td>
                <?= $withdraw_money; ?>
            </td>
        </tr>

        <tr>
            <td>本金(充值金额-提现金额)</td>
            <td>
                <?= $model->principal; ?>
            </td>
        </tr>

        <tr>
            <td>推荐人数</td>
            <td>
                <?= $model->account->recommend_number; ?>
            </td>
        </tr>

        <tr>
            <td>推荐佣金</td>
            <td>
                <?= $model->account->recommend_money; ?>
            </td>
        </tr>
        <tr>
            <td>会员等级</td>
            <td>
                <?= $model->memberLevel->name; ?>
            </td>
        </tr>
        <tr>
            <td>备注</td>
            <td>
                <?= !empty($model->remark) ? $model->remark : "(暂无)"; ?>
            </td>
        </tr>
        <tr>
            <td>身份证号码</td>
            <td>
                <?= !empty($model->identification_number) ? $model->identification_number : "(暂无)"; ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
</div>
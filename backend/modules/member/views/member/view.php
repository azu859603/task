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
            <td>注册时间</td>
            <td>
                <?= \common\helpers\DateHelper::dateTime($model->created_at); ?>
            </td>
        </tr>





        <tr>
            <td>钱包余额</td>
            <td>
                <?= $model->account->user_money; ?>
            </td>
        </tr>



        <tr>
            <td>提现金额</td>
            <td>
                <?= $withdraw_money; ?>
            </td>
        </tr>



        <tr>
            <td>推荐人数</td>
            <td>
                <?= $model->account->recommend_number; ?>
            </td>
        </tr>


        <tr>
            <td>备注</td>
            <td>
                <?= !empty($model->remark) ? $model->remark : "(暂无)"; ?>
            </td>
        </tr>

        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
</div>
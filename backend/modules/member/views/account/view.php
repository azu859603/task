<?php

use common\helpers\Url;
use common\helpers\Html;
use common\enums\StatusEnum;

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <h4 class="modal-title">账户信息</h4>
</div>
<div class="modal-body">
    <table class="table table-hover text-center">
        <tbody>
        <tr>
            <td>会员账号</td>
            <td>
                <?= $model->member->mobile; ?>
            </td>
        </tr>
        <tr>
            <td>金额</td>
            <td>
                <span class="demoInput"><?= Html::encode($withdraw_money); ?></span>
            </td>
        </tr>
        <?php if ($type == \common\models\member\WithdrawBill::ALIPAY_ACCOUNT) { ?>
            <tr>
                <td>支付宝账号</td>
                <td>
                    <span class="demoInput"><?= Html::encode($model->alipay_account); ?></span>
                    <button class="memberBtn" onclick="copy('<?= Html::encode($model->alipay_account); ?>')">复制</button>
                </td>
            </tr>
        <?php } else { ?>
            <tr>
                <td>姓名</td>
                <td>
                    <span class="demoInput"><?= Html::encode($username); ?></span>
                </td>
            </tr>
            <tr>
                <td>银行卡号</td>
                <td>
                    <span class="demoInput"><?= Html::encode($bank_card); ?></span>
                </td>
            </tr>
            <tr>
                <td>开户行</td>
                <td>
                    <span class="demoInput"><?= Html::encode($bank_address); ?></span>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
</div>
<style>
    .memberBtn {
        float: right;
    }
</style>
<script>
    function copy(text) {
        if (navigator.clipboard) {
            // clipboard api 复制
            navigator.clipboard.writeText(text);
        } else {
            var textarea = document.createElement('textarea');
            document.body.appendChild(textarea);
            // 隐藏此输入框
            textarea.style.position = 'fixed';
            textarea.style.clip = 'rect(0 0 0 0)';
            textarea.style.top = '10px';
            // 赋值
            textarea.value = text;
            // 选中
            textarea.select();
            // 复制
            document.execCommand('copy', true);
            // 移除输入框
            document.body.removeChild(textarea);
        }
    }
</script>
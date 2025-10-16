<?php

use common\helpers\Url;
use common\helpers\Html;
use common\enums\StatusEnum;

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <h4 class="modal-title">任务详情</h4>
</div>
<div class="modal-body">
    <table class="table table-hover text-center">
        <tbody>
        <tr>
            <td>任务ID</td>
            <td>
                <?= $model->id; ?>
            </td>
        </tr>
        <tr>
            <td>任务标题</td>
            <td>
                <?= $model->translation->title; ?>
            </td>
        </tr>
        <tr>
            <td>任务要求</td>
            <td>
                <?= $model->translation->content; ?>
            </td>
        </tr>

        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
</div>
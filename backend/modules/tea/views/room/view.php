<?php

use common\helpers\Url;
use common\helpers\Html;
use common\enums\StatusEnum;

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <h4 class="modal-title">群人员（<?=count($model)?>）  管理员（<?=count($b_model)?>）</h4>
</div>
<div class="modal-body">
    <table class="table table-hover text-center">
        <tbody>
        <tr><td>管理</td></tr>
        <?php
        foreach ($b_model as $v){
            echo " <tr>"."<td>$v[0]</td>"."<td>$v[1]</td>"."<td>$v[2]</td>"."</tr>";
        }
        ?>
        <tr><td>会员</td></tr>
        <?php
        foreach ($model as $v){
           echo " <tr>"."<td>$v[0]</td>"."<td>$v[1]</td>"."<td>$v[2]</td>"."</tr>";
        }
        ?>

        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
</div>
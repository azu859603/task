<?php

use common\helpers\Url;
use common\helpers\Html;
use common\enums\StatusEnum;

?>

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span>
    </button>
    <h4 class="modal-title">视频地址</h4>
</div>
<div class="modal-body">
    <table class="table table-hover text-center">
        <tbody>
        <tr>
            <td>视频地址</td>
            <td>
                <?= $video_url; ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
</div>
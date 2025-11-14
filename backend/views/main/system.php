<?php

use common\helpers\Url;
use common\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\daterange\DateRangePicker;
use kartik\grid\GridView;

$addon = <<< HTML
<span class="input-group-addon">
    <i class="glyphicon glyphicon-calendar"></i>
</span>
HTML;

$this->title = '统计';
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<?= Html::jsFile('@web/resources/plugins/echarts/echarts-all.js') ?>

<style>
    .info-box-number {
        font-size: 20px;
    }
</style>
<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-cash green"></i> <?= $today['get_task_number'] ?? 0; ?>/<?= $today['over_task_number'] ?? 0; ?></span>
                <span class="info-box-text">今日领取任务数/今日完成任务数</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-stats-bars green"></i> <?= $today['commission_money'] ?? 0; ?></span>
                <span class="info-box-text">今日佣金额</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-arrow-graph-down-right red"></i> <?= $today['withdraw_money'] ?? 0; ?>/<?= $today['withdraw_number'] ?? 0; ?></span>
                <span class="info-box-text">今日提现(金额)/（人数）</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-arrow-graph-up-right green"></i> <?= $today['register_member'] ?? 0; ?></span>
                <span class="info-box-text">今日注册数</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-cash green"></i> <?= $to_month['get_task_number'] ?? 0; ?>/<?= $to_month['over_task_number'] ?? 0; ?></span>
                <span class="info-box-text">本月领取任务数/本月完成任务数</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-stats-bars green"></i> <?= $to_month['commission_money'] ?? 0; ?></span>
                <span class="info-box-text">本月佣金额</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-arrow-graph-down-right red"></i> <?= $to_month['withdraw_money'] ?? 0; ?></span>
                <span class="info-box-text">本月提现</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-arrow-graph-up-right green"></i> <?= $to_month['register_member'] ?? 0; ?></span>
                <span class="info-box-text">本月注册数</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-cash green"></i> <?= $all_day['get_task_number'] ?? 0; ?>/<?= $all_day['over_task_number'] ?? 0; ?></span>
                <span class="info-box-text">累计领取任务数/累计完成任务数</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-stats-bars green"></i> <?= $all_day['commission_money'] ?? 0; ?></span>
                <span class="info-box-text">累积佣金额</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-arrow-graph-down-right red"></i> <?= $all_day['withdraw_money'] ?? 0; ?></span>
                <span class="info-box-text">累积提现</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
            <div class="info-box-content p-md">
                <span class="info-box-number"><i
                            class="ion ion-arrow-graph-up-right green"></i> <?= $all_day['register_member'] ?? 0; ?></span>
                <span class="info-box-text">累积注册数</span>
            </div>
        </div>
    </div>
</div>




<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header">
                <i class="fa fa-fw fa-sun-o"></i>
                <h3 class="box-title">日期统计</h3>
            </div>
            <div class="box-body table-responsive">
                <div class="col-sm-12 normalPaddingJustV">
                    <?php $form = ActiveForm::begin([
                        'action' => Url::to(['/main/system']),
                        'method' => 'get'
                    ]); ?>
                    <div class="row">
                        <div class="col-sm-4 p-r-no-away">
                            <div class="input-group drp-container">
                                <?= DateRangePicker::widget([
                                    'name' => 'queryDate',
                                    'value' => $from_date . '-' . $to_date,
                                    'readonly' => 'readonly',
                                    'useWithAddon' => true,
                                    'convertFormat' => true,
                                    'startAttribute' => 'from_date',
                                    'endAttribute' => 'to_date',
                                    'startInputOptions' => ['value' => $from_date],
                                    'endInputOptions' => ['value' => $to_date],
                                    'pluginOptions' => [
                                        'locale' => ['format' => 'Y-m-d'],
                                    ]
                                ]) . $addon; ?>
                            </div>
                        </div>

                        <div class="col-sm-4 p-r-no-away">
                            <div class="input-group drp-container">
                                <?= kartik\select2\Select2::widget([
                                    'name' => 'memberId',
                                    'value' => $memberId,
                                    'data' => $memberIds,
                                ]) ?>
                            </div>
                        </div>

                        <div class="col-sm-3 p-l-no-away">
                            <div class="input-group m-b">
                                <?= Html::tag('span',
                                    '<button class="btn btn-white"><i class="fa fa-search"></i> 搜索</button>',
                                    ['class' => 'input-group-btn']) ?>
                            </div>
                        </div>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
                <div class="row my-3">
                    <div class="col-md-12">
                        <div class="card r-0 shadow">
                            <div class="table-responsive">
                                <form>
                                    <table class="kv-grid-table table table-hover table-bordered table-striped kv-table-wrap">
                                        <thead>
                                        <tr class="no-b">
                                            <th>日期</th>
                                            <th>注册数</th>
                                            <th>领取任务数</th>
                                            <th>领取任务人数</th>
                                            <th>完成任务数</th>
                                            <th>提现金额</th>
                                            <th>佣金额</th>
                                            <th>代理</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($models as $k1 => $v1): ?>
                                            <tr>
                                                <td><?= $v1['date'] ?></td>
                                                <td><?= $v1['register_member'] ?></td>
                                                <td><?= $v1['get_task_number'] ?></td>
                                                <td><?= $v1['get_task_people'] ?></td>
                                                <td><?= $v1['over_task_number'] ?></td>
                                                <td><?= $v1['withdraw_money'] ?></td>
                                                <td><?= $v1['commission_money'] ?></td>
                                                <td><?= $v1['manager']['username']??"无" ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

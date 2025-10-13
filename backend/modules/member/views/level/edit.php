<?php

use yii\widgets\ActiveForm;
use common\enums\GenderEnum;
use common\enums\StatusEnum;

$this->title = '编辑';
$this->params['breadcrumbs'][] = ['label' => '等级信息', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<div class="row">
    <div class="col-lg-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">基本信息</h3>
            </div>
            <?php $form = ActiveForm::begin([
                'fieldConfig' => [
                    'template' => "<div class='col-sm-2 text-right'>{label}</div><div class='col-sm-10'>{input}{hint}{error}</div>",
                ],
            ]); ?>
            <div class="box-body">
                <?php if ($model->isNewRecord || ($model->level != 0 && !$model->isNewRecord)) { ?>
                    <?= $form->field($model, 'level')->widget(\kartik\select2\Select2::class, [
                        'data' => \common\enums\MemberLevelEnum::getMap(),
                        'options' => ['placeholder' => '请选择'],
                        'pluginOptions' => [
                            'allowClear' => true
                        ],
                    ])->hint("*数字越大等级越高", ['style' => 'color:red']); ?>
                <?php } ?>
                <?= $form->field($model, 'name')->textInput() ?>
                <?= $form->field($model, 'handling_fees_percentage')->textInput()->hint("*单位：百分比", ['style' => 'color:red']); ?>
                <div class="form-group field-goods-fictitious_view">
                    <div class="col-sm-2 text-right">
                        <label class="control-label" for="goods-fictitious_view">升级条件</label>
                    </div>
                    <div class="col-sm-10 specification">
                        <div class="form-inline">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        累计经验满
                                    </label>
                                </div>
                                <input type="number" name="Level[experience]" value="<?= $model->experience ?>"
                                       step="0.01" min="0" class="form-control">
                                <label class="small" style="color: red;"> *设置会员等级所需要的累计经验且必须大于等于0</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer text-center">
                <button class="btn btn-primary" type="submit">保存</button>
                <span class="btn btn-white" onclick="history.go(-1)">返回</span>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
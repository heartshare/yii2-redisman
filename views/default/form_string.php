<p>
    <?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
        [
            'action' => ['/redisman/default/update']
        ]
    )?>
    <input type="hidden" name="key" value="<?=$model->key?>">
<div class="one">
    <?php
    echo $form->field($model, 'value')->widget(
        \lav45\aceEditor\AceEditorWidget::className(), [

            'mode' => 'text',
            'fontSize' => 15,
            'height' => 200,

        ]
    );
    ?>
</div>
<div class="one">
    <button class="ui blue icon button submit"><i class="save icon"></i><?= Yii::t('app', 'Replace') ?>
    </button>
</div>
<?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end() ?>
</p>
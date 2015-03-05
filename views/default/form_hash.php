<?php
use insolita\redisman\Redisman;

/**
 * @var \yii\web\View                                    $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\Redisman                      $module
 * @var \insolita\redisman\models\RedisItem              $model
 */
?>
<div class="ui top attached tabular menu">
    <div class="active item" data-tab="tabedit"><?=Redisman::t('redisman','Edit')?></div>
    <div class="item" data-tab="tabappend"><?=Redisman::t('redisman','Append')?></div>
</div>
<div class="ui bottom attached active tab segment"  data-tab="tabedit">
    <p>
        <?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
            [
                'action' => ['/redisman/default/update']
            ]
        )?>
        <input type="hidden" name="RedisItem[key]" value="<?=$model->key?>">
    <div class="one">
        <?php
        echo \Zelenin\yii\SemanticUI\widgets\GridView::widget([
                'dataProvider'=>$model->formatvalue,
                 'columns'=>[
                     'field',
                     [
                         'attribute'=>'value',
                         'format'=>'raw',
                         'value'=>function($data)use($model){
                             return '<input type="text" name="RedisItem[formatvalue]['.$data['field'].']" value="'.$data['score'].'">';
                         }
                     ]
                 ],
                [
                    'class'=>'yii\grid\ActionColumn',
                    'template'=>'{remove}',
                    'buttons'=>[
                        'remove'=>function($url,$data)use($model){
                            return \yii\helpers\Html::a('<i class="icon save"></i>',['/redisman/default/remfield', 'key'=>$model->key,'field'=>$data['field']]);
                        }
                    ]
                ]
            ])
        ?>
    </div>
    <div class="one">
        <button class="ui blue icon button submit"><i class="save icon"></i><?= Yii::t('app', 'Update') ?>
        </button>
    </div>
    <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end() ?>
    </p></div>

<div class="ui bottom attached  tab segment"  data-tab="tabappend">
    <p>
        <?php $form = \Zelenin\yii\SemanticUI\widgets\ActiveForm::begin(
            [
                'action' => ['/redisman/default/update']
            ]
        )?>
        <input type="hidden" name="RedisItem[key]" value="<?=$model->key?>">
    <div class="one">
        <table class="ui table bordered">
            <thead><th><?=Redisman::t('redisman','field')?></th><th><?=Redisman::t('redisman','value')?></th></thead>
            <?php for($i=0; $i<=10;$i++):?>
                <tr>
                    <td><input type="text" name="RedisItem[formatvalue][][field]" value=""></td>
                    <td>'<input type="text" name="RedisItem[formatvalue][][value]" value="">'</td>
                </tr>
            <?php endfor?>
        </table>
    </div>
    <div class="one">
        <button class="ui blue icon button submit"><i class="save icon"></i><?= Yii::t('app', 'Append') ?>
        </button>
    </div>
    <?php \Zelenin\yii\SemanticUI\widgets\ActiveForm::end() ?>
    </p>
</div>
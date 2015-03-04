<?php
use yii\helpers\Html;
use Zelenin\yii\SemanticUI\widgets\GridView;

/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\Redisman $module
 * @var \yii\data\ArrayDataProvider $dataProvider
 */
$module=$this->context->module;
$this->title=$module->getCurrentName();
?>

<div class="ui orange segment">

    <?php
    echo GridView::widget([
            'dataProvider'=>$dataProvider,
            'columns'=>[
                [ 'class'=>'\Zelenin\yii\SemanticUI\widgets\CheckboxColumn'],
                'key'
                ,'type','size','ttl',
                [
                    'class'=>'\yii\grid\ActionColumn',
                    'template'=>'{view}  {delete}',
                    'buttons' => [
                        'view' => function ($url, $model) {
                            return  Html::a(
                                '<i class="icon circular inverted eye green"></i>',
                                \yii\helpers\Url::to(['view', 'key' => urlencode($model['key'])]),
                                ['data-pjax' => 0, 'data-modaler'=>true, 'title'=>Yii::t('app','View')]
                            );
                        },
                        'delete' => function ($url, $model) {
                            return  Html::a(
                                '<i class="icon circular small  trash red"></i>',
                                    \yii\helpers\Url::to(['delete', 'key' => urlencode($model['key'])]),
                                    [
                                        'data-pjax' => 0,
                                        'data-confirm' => 'Подтвердите действие', 'data-method' => 'post'
                                        , 'title'=>Yii::t('app','Delete')
                                    ]
                                );
                        },

                    ]
                ]
            ]
        ])?>
</div>
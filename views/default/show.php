<?php
use \insolita\redisman\RedismanModule;
use Zelenin\yii\SemanticUI\widgets\GridView;
use \yii\helpers\Html;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var array $info
 */
$module=$this->context->module;
$this->title=$module->getCurrentName();

?>

<div class="ui blue segment">
    <h1 class="ui header"> <?=$module->getCurrentName()?> </h1>

    <?php
    echo GridView::widget([
            'dataProvider'=>$dataProvider,
            'columns'=>['key','type','size','ttl']
        ])?>
</div>
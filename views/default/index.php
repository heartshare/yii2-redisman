<?php
use \insolita\redisman\RedismanModule;
use Zelenin\yii\SemanticUI\modules\Accordion;
use \yii\helpers\Html;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var array $info
 */
 $module=$this->context->module;

$this->title=$module->getCurrentName();
$infoformat=[];
foreach($info as $section=>$data){
    $content='';
    foreach($data as $key=>$val){
        $content.=Html::tag('tr', Html::tag('td', RedismanModule::t($key)). Html::tag('td', $val));
    }

    $infoformat[]=[
        'title'=>RedismanModule::t($section),
        'content'=>Html::tag('table',$content,['class'=>"ui definition table"])
    ];
}
?>

<h1 class="ui header"> <?=$module->getCurrentName()?> </h1>
<div class="ui blue segment">
<?php
echo Accordion::widget([
        'styled' => true,
        'fluid' => true,
        'contentOptions' => [
            'encode' => false
        ],
        'items' => $infoformat
    ]);?>
</div>
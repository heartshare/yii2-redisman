<?php
use yii\helpers\Html;
use Zelenin\yii\SemanticUI\widgets\DetailView;
use trntv\aceeditor\AceEditor;
use Zelenin\yii\SemanticUI\Elements;
use insolita\redisman\RedismanModule;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var string $key
 * @var \insolita\redisman\models\RedisItem $data
 */
$module=$this->context->module;
$this->title=$module->getCurrentName();
$dblist=$module->dbList();

?>

<div class="ui blue segment">
    <h1 class="ui header"> <?=$module->getCurrentName()?>
        <div class="sub header"><?=Html::encode($key)?></div>
    </h1>
    <div class="ui two column grid">
        <div class="column">
            <div class="ui raised segment">
                <a class="ui ribbon teal label">Label</a>
                <span><?=RedismanModule::t('Key Information')?></span>
                <div class="ui statistics">
                    <div class="statistic">
                        <div class="value">
                            <?=$data->size?>
                        </div>
                        <div class="label">
                            <?=$data->getAttributeLabel('size')?>
                        </div>
                    </div>
                    <div class="statistic">
                        <div class="value">
                            <i class="icon clock"></i>
                            <?=$data->ttl?>
                        </div>
                        <div class="label">
                            <?=$data->getAttributeLabel('ttl')?>
                        </div>
                    </div>
                    <div class="statistic">
                        <div class="value">
                            <div class="ui item tag blue label"><?=$data->type?></div>
                        </div>
                        <div class="label">
                            <?=$data->getAttributeLabel('type')?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="ui segment">
                <a class="ui right ribbon blue label"><?=RedismanModule::t('Value')?></a>
                <p>
                    <?php $form= \Zelenin\yii\SemanticUI\widgets\ActiveForm::widget([
                        'action'=>['/redisman/default/update','key'=>urlencode($key)]
                        ])?>
                    <?php echo AceEditor::widget([
                            'model'=>$data,
                            'attribut'=>'value',
                            'value'=>(($data->type==RedismanModule::REDIS_STRING)?$data->value:\yii\helpers\Json::encode($data->value)),
                            'mode'=>'json'

                        ])?>
                    <?php echo $form->field($data,'newttl')->inputText();?>
                    <?php echo Html::endForm()?>
                </p>

            </div>
        </div>
    </div>
    <?php
       echo \yii\helpers\VarDumper::dumpAsString($data);
    ?>
</div>
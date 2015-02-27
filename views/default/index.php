<?php
use \insolita\redisman\RedismanModule;

/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 * @var array $info
 */
 $module=$this->context->module;
?>

<table class="ui definition table">
    <thead>
    <tr><th></th>
        <th><?=RedismanModule::t('Value')?></th>
    </tr></thead>
    <tbody>
    <?php foreach($info as $k=>$v):?>
        <tr><td><b><?=$k;?>:</b></td><td><?=$v;?></td></tr>
    <?php endforeach;?>

    <tr><td><?=RedismanModule::t('Keys in DataBase')?>:</td><td><?=$module->getConnection()->dbsize();?></td></tr>

    </tbody>
</table>
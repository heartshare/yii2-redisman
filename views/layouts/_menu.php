<?php
use Zelenin\yii\SemanticUI\modules\Dropdown;
use \insolita\redisman\RedismanModule;
/**
 * @var \yii\web\View $this
 * @var \insolita\redisman\controllers\DefaultController $context
 * @var \insolita\redisman\RedismanModule $module
 */
$module=$this->context->module;
$dbselect=[];
for($i=0;$i<$module->getDbCount();$i++){
    $dbselect[$i]='Db №'.$i;
}
 ?>

<div class="ui vertical menu">
    <div class="header item">
        <?=RedismanModule::t('Connection Settings')?>
    </div>
    <?php echo Dropdown::widget([
            'name' => 'redisconnection',
            'selection' => $module->getCurrentConn(),
            'search' => false,
            'fluid' => false,
            'disabled' => false,
            'items' => $module->connectionList(),'defaultText' => RedismanModule::t('Choose connection'),
            'options'=>['id'=>'rediscon','class'=>'item']
        ]);?>
    <?php echo Dropdown::widget([
            'name' => 'redisdb',
            'selection' => $module->getCurrentDb(),
            'search' => false,
            'fluid' => true,
            'disabled' => true,
            'items' => $dbselect,'defaultText' => RedismanModule::t('Choose Database'),
            'options'=>['id'=>'redisdb','class'=>'item']
        ]);?>
    <div class="item">
        <div class="ui tiny green button" id="makeconnect"><?=RedismanModule::t('Connect')?></div>
    </div>
    <div class="item">
        <a><b>Your Profile</b></a>
        <div class="menu">
            <a class="item">Inbox</a>
            <a class="item">Activity</a>
            <a class="item">Groups</a>
        </div>
    </div>
</div>
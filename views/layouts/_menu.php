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

<div class="ui vertical menu fluid">
    <div class="header item">
        <?=RedismanModule::t('Connection Settings')?>
    </div>
     <div class="text item">
         <?=$this->render('_connectform')?>
     </div>
    <div class="header item">
        <?=RedismanModule::t('Search Key')?>
    </div>
    <div class="text item">
        <?=$this->render('_searchform')?>
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
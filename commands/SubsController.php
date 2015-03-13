<?php
/**
 * Created by PhpStorm.
 * User: Insolita
 * Date: 13.03.15
 * Time: 23:59
 */
namespace app\commands;

use insolita\redisman\components\PhpredisConnection;
use yii\base\Exception;
use yii\console\Controller;
use Yii;
class SubsController extends Controller {

    public function actionSubscribe($connection,$channel){
        /**
         * @var \insolita\redisman\Redisman $module
        **/
        $module=\Yii::$app->getModule('redisman');
        if($connection!=$module->getCurrentConn()){
            try{
                $module->setConnection($connection);
                if($module->getConnection() instanceof PhpredisConnection){
                    $module->executeCommand('SUBSCRIBE',[[$channel],[$this, 'subsCallback']]);
                }else{

                }

            }catch (Exception $e){

            }

        }
    }

    public function actionUnsub($channel){

    }

    public function subsCallback($redis, $chan, $msg){

        Yii::info('From '.$chan.' - '.$msg,'application.redisman.subs');
    }
} 
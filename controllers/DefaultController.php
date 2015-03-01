<?php
namespace insolita\redisman\controllers;


use insolita\redisman\models\ConnectionForm;
use insolita\redisman\RedismanModule;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\VarDumper;

class DefaultController extends \yii\web\Controller
{
    /**
     * @var \insolita\redisman\RedismanModule $module
     */
    public $module;

    private $_conn=null;

    public function init(){
        parent::init();
        $this->_conn=$this->module->getConnection();
    }

    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs'=>[
                'class' => VerbFilter::className(),
                'actions' => [
                    'switch' => ['post'],
                    'flushdb' => ['post'],
                ],
            ]
        ];
    }

    public function actionIndex()
    {
        $info=$this->module->dbInfo();
        return $this->render('index',['info'=>$info]);
    }

    public function actionShow($pattern=null){

    }

    public function actionCreate()
    {

    }

    public function actionUpdate()
    {

    }

    public function actionView()
    {

    }



    public function actionSwitch()
    {
        $model=new ConnectionForm();
        if($model->load(\Yii::$app->request->post()) && $model->validate()){
            \Yii::info(VarDumper::dumpAsString($model->getAttributes()));
            $this->module->setConnection($model->connection, $model->db);
            \Yii::$app->session->setFlash('success', RedismanModule::t('Switched to').$this->module->getCurrentName(),false);
        }else{
            \Yii::$app->session->setFlash('error', Html::errorSummary($model),false);
        }

        return $this->redirect(['index']);

    }

    public function actionSavedb(){
        $this->_conn->bgsave();
        if(\Yii::$app->request->isAjax){
            echo 'ok';
        }else{
            \Yii::$app->session->setFlash('success',RedismanModule::t('database saving run in background'));
            return $this->redirect(['index']);
        }
    }

    public function actionFlushdb(){
        $this->_conn->flushdb();
        if(\Yii::$app->request->isAjax){
            echo 'ok';
        }else{
            \Yii::$app->session->setFlash('success',RedismanModule::t('Clearind Database'));
            return $this->redirect(['index']);
        }
    }

} 
<?php
namespace insolita\redisman\controllers;


use insolita\redisman\models\ConnectionForm;
use insolita\redisman\models\RedisModel;
use insolita\redisman\Redisman;
use yii\filters\VerbFilter;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\VarDumper;

/**
 * Class DefaultController
 *
 * @package insolita\redisman\controllers
 */
class DefaultController extends \yii\web\Controller
{
    /**
     * @var \insolita\redisman\Redisman $module
     */
    public $module;


    /**
     * @return array
     */
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
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'switch' => ['post'],
                    'flushdb' => ['post'],
                    'search' => ['post'],
                    'dbload' => ['post'],
                ],
            ]
        ];
    }

    /**
     * @return string
     */
    public function actionIndex()
    {
        $info = $this->module->dbInfo();
        return $this->render('index', ['info' => $info]);
    }

    /**
     * @return string
     */
    public function actionShow()
    {
        $model = new RedisModel();
        $model->restoreFilter();
        $dataProvider = $model->search(\Yii::$app->request->getQueryParams());
        Url::remember(\Yii::$app->request->getUrl(), 'show');
        return $this->render('show', ['model' => $model, 'dataProvider' => $dataProvider]);
    }


    /**
     *
     */
    public function actionBulk()
    {
        //@TODO:$this
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\ErrorException
     */
    public function actionSwitch()
    {
        $model = new ConnectionForm();
        if ($model->load(\Yii::$app->request->post()) && $model->validate()) {
            \Yii::info(VarDumper::dumpAsString($model->getAttributes()));
            $this->module->setConnection($model->connection, $model->db);
            RedisModel::resetFilter();
            \Yii::$app->session->setFlash(
                'success', Redisman::t('redisman', 'Switched to') . $this->module->getCurrentName()
            );
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model));
        }

        return $this->redirect(['index']);

    }

    /**
     * @return \yii\web\Response
     */
    public function actionSearch()
    {
        $model = new RedisModel();
        if ($model->load(\Yii::$app->request->post()) && $model->storeFilter()) {
            \Yii::$app->session->setFlash('success', Redisman::t('redisman', 'Search query updated!'));
            return $this->redirect(['show']);
        } else {
            \Yii::$app->session->setFlash('error', Html::errorSummary($model));
            return $this->redirect(['index']);
        }
    }

    public function actionResetSearch()
    {
        RedisModel::resetFilter();
        return $this->redirect(['show']);
    }

    /**
     * @return \yii\web\Response
     */
    public function actionSavedb()
    {
        $this->module->dbSave();
        if (\Yii::$app->request->isAjax) {
            echo 'ok';
        } else {
            \Yii::$app->session->setFlash(
                'success', Redisman::t('redisman', 'database saving run in background')
            );
            return $this->redirect(['index']);
        }
    }

    /**
     * @return \yii\web\Response
     */
    public function actionFlushdb()
    {
        if($this->module->dbFlush()){
            \Yii::$app->session->setFlash('success', Redisman::t('redisman', 'Database is clear'));
        }else{
            \Yii::$app->session->setFlash('error',Redisman::t('redisman','Flushing this DB not allowed'), false);

        }
        return $this->redirect(['index']);
    }

    /**
     * @return bool|string
     */
    public function actionDbload()
    {
        $connect = \Yii::$app->request->post('connection');
        $totalDb = $this->module->totalDbCount();
        if (isset($totalDb[$connect])) {
            $dblist = '';
            for ($i = 0; $i < $totalDb[$connect]; $i++) {
                $dblist .= Html::tag('div', 'Db №' . $i, ['data-value' => $i, 'class' => 'item']);
            }
            return $dblist;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function actionResetAppCache()
    {
        //@TODO:$this
    }

} 
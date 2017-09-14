<?php
namespace futuretek\migrations\controllers;

use futuretek\migrations\Migration;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\web\Controller;

/**
 * Class Controller
 *
 * @package futuretek\options
 * @author  Lukáš Černý <lukas.cerny@futuretek.cz>, Petr Leo Compel <petr.compel@futuretek.cz>
 * @license http://www.futuretek.cz/license FTSLv1
 * @link    http://www.futuretek.cz
 */
class AdminController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'up' => ['post'],
                    'down' => ['post'],
                    'drop' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Option models.
     * @return mixed
     * @throws \Exception
     */
    public function actionIndex()
    {
        $migration = new Migration();
        $new = $migration->getNewMigrations();
        $applied = $migration->getAppliedMigrations();
        $newMigrations = new ArrayDataProvider([
            'allModels' => $new,
            'pagination' => [
                'pageSize' => -1,
            ],
        ]);
        $appliedMigrations = new ArrayDataProvider([
            'allModels' => $applied,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        return $this->render('@vendor/futuretek/yii2-migrations/views/index', [
            'newMigrations' => $newMigrations,
            'appliedMigrations' => $appliedMigrations,
            'hasNewMigrations' => count($new) > 0,
            'hasAppliedMigrations' => count($applied) > 0,
        ]);
    }

    /**
     * @return string|\yii\web\Response
     * @throws InvalidParamException
     */
    public function actionUp()
    {
        $migration = new Migration();
        $result = $migration->actionUp(Yii::$app->request->post('limit', 1));
        Yii::info($migration->getLog(), 'migrations');
        if ($result) {
            Yii::$app->session->setFlash('success', $migration->getLastStatus());
        } else {
            Yii::$app->session->setFlash('error', $migration->getLastStatus());
        }

        return $this->redirect(['index']);
    }

    /**
     * @return string|\yii\web\Response
     * @throws InvalidParamException
     */
    public function actionDown()
    {
        $migration = new Migration();
        $result = $migration->actionDown(Yii::$app->request->post('limit', 1));
        Yii::info($migration->getLog(), 'migrations');
        if ($result) {
            Yii::$app->session->setFlash('success', $migration->getLastStatus());
        } else {
            Yii::$app->session->setFlash('error', $migration->getLastStatus());
        }

        return $this->redirect(['index']);
    }

    /**
     * @return string|\yii\web\Response
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     * @throws InvalidParamException
     */
    public function actionDrop()
    {
        if (YII_ENV !== 'dev') {
            throw new InvalidConfigException(Yii::t('fts-migrations', 'This command is available only in development.'));
        }

        $result = Yii::$app->db->createCommand(
            'SET FOREIGN_KEY_CHECKS=0;
            SET @tables = NULL;
            SELECT GROUP_CONCAT(table_schema, ".", table_name) INTO @tables
                FROM information_schema.tables
                WHERE table_schema = "' . substr(Yii::$app->db->dsn, strpos(Yii::$app->db->dsn, 'dbname=') + 7) . '";
            SET @tables = CONCAT("DROP TABLE ", @tables);
            PREPARE stmt FROM @tables;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
            SET FOREIGN_KEY_CHECKS=1;'
        )->execute();

        if ($result === 0) {
            Yii::$app->session->setFlash('success', Yii::t('fts-migrations', 'All tables was successfully dropped.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('fts-migrations', 'Error while dropping tables.'));
        }

        return $this->redirect(['index']);
    }

    /**
     * @return string|\yii\web\Response
     * @throws InvalidConfigException
     * @throws \yii\db\Exception
     * @throws InvalidParamException
     */
    public function actionOptimize()
    {
        $result = Yii::$app->db->createCommand(
            'SET @tables = NULL;
            SELECT GROUP_CONCAT(table_schema, ".", table_name) INTO @tables
                FROM information_schema.tables
                WHERE table_schema = "' . substr(Yii::$app->db->dsn, strpos(Yii::$app->db->dsn, 'dbname=') + 7) . '";
            SET @tables = CONCAT("OPTIMIZE TABLE ", @tables);
            PREPARE stmt FROM @tables;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;'
        )->execute();

        if ($result === 0) {
            Yii::$app->session->setFlash('success', Yii::t('fts-migrations', 'All tables was successfully optimized.'));
        } else {
            Yii::$app->session->setFlash('error', Yii::t('fts-migrations', 'Error while optimizing tables.'));
        }

        return $this->redirect(['index']);
    }
}

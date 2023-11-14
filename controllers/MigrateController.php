<?php

namespace futuretek\migrations\controllers;

use futuretek\migrations\Migration;
use yii\console\controllers\MigrateController as YiiMigrateController;
use yii\console\ExitCode;

/**
 * Manages application migrations.
 *
 * A migration means a set of persistent changes to the application environment
 * that is shared among different developers. For example, in an application
 * backed by a database, a migration may refer to a set of changes to
 * the database, such as creating a new table, adding a new table column.
 *
 * This command provides support for tracking the migration history, upgrading
 * or downloading with migrations, and creating new migration skeletons.
 *
 * The migration history is stored in a database table named
 * as [[migrationTable]]. The table will be automatically created the first time
 * this command is executed, if it does not exist. You may also manually
 * create it as follows:
 *
 * ~~~
 * CREATE TABLE migration (
 *     version varchar(180) PRIMARY KEY,
 *     apply_time integer
 * )
 * ~~~
 *
 * Below are some common usages of this command:
 *
 * ~~~
 * # creates a new migration named 'create_user_table'
 * yii migrate/create create_user_table
 *
 * # applies ALL new migrations
 * yii migrate
 *
 * # reverts the last applied migration
 * yii migrate/down
 * ~~~
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Lukas Cerny <lukas.cerny@futuretek.cz>
 * @since 2.0
 */
class MigrateController extends YiiMigrateController
{
    /**
     * @var Migration
     */
    public $migration;

    /**
     * @var bool Don't emit exit code (useful on CI).
     */
    public $noExit = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->migration = new Migration();
    }

    /**
     * Upgrades the application by applying new migrations.
     *
     * @param int $limit
     * @return int
     */
    public function actionUp($limit = 0)
    {
        $result = $this->migration->actionUp($limit);
        echo implode(PHP_EOL, $this->migration->getLog());

        return $result || $this->noExit ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Downgrades the application by reverting old migrations.
     *
     * @param int $limit
     * @return int
     */
    public function actionDown($limit = 1)
    {
        $result = $this->migration->actionDown($limit);
        echo implode(PHP_EOL, $this->migration->getLog());

        return $result || $this->noExit ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Not implemented
     *
     * @param int $limit
     * @return int|string
     */
    public function actionRedo($limit = 1)
    {
        return 'Not implemented';
    }

    /**
     * Not implemented
     *
     * @param string $version
     * @return string
     */
    public function actionTo($version)
    {
        return 'Not implemented';
    }

    /**
     * Not implemented
     *
     * @param string $version
     * @return string
     */
    public function actionMark($version)
    {
        return 'Not implemented';
    }

    /**
     * Displays the migration history.
     *
     * This command will show the list of migrations that have been applied
     *
     * @param int $limit
     */
    public function actionHistory($limit = 10)
    {
        echo var_export($this->migration->getAppliedMigrations(), true);
    }

    /**
     * Displays the un-applied new migrations.
     *
     * This command will show the new migrations that have not been applied.
     *
     * @param int $limit
     */
    public function actionNew($limit = 10)
    {
        echo var_export($this->migration->getNewMigrations(), true);
    }

    private $_migrationNameLimit;

    /**
     * {@inheritdoc}
     */
    protected function getMigrationNameLimit()
    {
        if ($this->_migrationNameLimit !== null) {
            return $this->_migrationNameLimit;
        }
        $tableSchema = $this->db->schema ? $this->db->schema->getTableSchema($this->migrationTable, true) : null;
        if ($tableSchema !== null) {
            return $this->_migrationNameLimit = $tableSchema->columns[$this->db->driverName === 'oci' ? 'VERSION' : 'version']->size;
        }

        return static::MAX_NAME_LENGTH;
    }
}

<?php

namespace futuretek\migrations;

use Yii;
use yii\db\Query;

/**
 * Class Migration
 *
 * @package futuretek\migrations
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class Migration
{
    const BASE_MIGRATION = 'm000000_000000_base';

    public $migrationTable = '{{%migration}}';

    private $_log = [];
    private $_lastStatus = '';

    private $_migrations = [];
    private $_applied = [];

    public function getNewMigrations()
    {
        //Load applied migrations
        $this->getAppliedMigrations();

        $this->_migrations = [];

        //Main app migrations
        $this->getNewMigrationsForPath(Yii::$app->basePath . DIRECTORY_SEPARATOR . 'migrations');

        $pathsExplored = [];

        //Extensions migrations
        foreach (Yii::$app->extensions as $extension) {
            $migPath = Yii::$app->vendorPath . DIRECTORY_SEPARATOR . $extension['name'] . DIRECTORY_SEPARATOR . 'migrations';
            if (is_dir($migPath)) {
                $pathsExplored[] = str_replace('\\', '/', $migPath);
                $this->getNewMigrationsForPath($migPath);
            }
        }

        //Modules migrations
        foreach (Yii::$app->modules as $name => $module) {
            if (!is_array($module) || !array_key_exists('class', $module)) {
                Yii::warning("Class for module {$name} not specified.", 'migrations');
                continue;
            }
            $ref = new \ReflectionClass($module['class']);
            $migPath = dirname($ref->getFileName()) . DIRECTORY_SEPARATOR . 'migrations';
            if (is_dir($migPath) && !in_array(str_replace('\\', '/', $migPath), $pathsExplored, true)) {
                $pathsExplored[] = str_replace('\\', '/', $migPath);
                $this->getNewMigrationsForPath($migPath);
            }
        }

        sort($this->_migrations);

        return $this->_migrations;
    }

    public function getLastStatus()
    {
        return $this->_lastStatus;
    }

    public function getAppliedMigrations()
    {
        $history = $this->getMigrationHistory(null);
        foreach ($history as $migration) {
            $this->_applied[substr($migration['version'], 1, 13)] = $migration;
        }

        return $this->_applied;
    }

    public function getLog()
    {
        return $this->_log;
    }

    protected function getNewMigrationsForPath($migrationPath)
    {
        $handle = opendir($migrationPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($path) && !array_key_exists($matches[2], $this->_applied)) {
                $this->_migrations[] = [
                    'version' => $matches[1],
                    'path' => $migrationPath,
                ];
            }
        }
        closedir($handle);
    }

    public function getNewMigrationsForModule($migrationPath, $module)
    {
        $handle = opendir($migrationPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($path) && !array_key_exists($matches[2], $this->_applied)) {
                $this->_migrations[] = [
                    'version' => $matches[1],
                    'path' => $migrationPath,
                    'module' => $module,
                ];
            }
        }
        closedir($handle);
        sort($this->_migrations);
    }

    protected function getMigrationHistory($limit, $module = null)
    {
        if (Yii::$app->db->schema->getTableSchema($this->migrationTable, true) === null) {
            $this->createMigrationHistoryTable();
        } else {
            if (Yii::$app->db->schema->getTableSchema($this->migrationTable, true)->getColumn('path') === null) {
                Yii::$app->db->createCommand()->addColumn(
                    $this->migrationTable,
                    'path',
                    'varchar(255) NOT NULL DEFAULT "' . Yii::$app->basePath . '"'
                )->execute();
            }
            if (Yii::$app->db->schema->getTableSchema($this->migrationTable, true)->getColumn('app_version') === null) {
                Yii::$app->db->createCommand()->addColumn(
                    $this->migrationTable,
                    'app_version',
                    'varchar(16) NULL'
                )->execute();
            }
            if (Yii::$app->db->schema->getTableSchema($this->migrationTable, true)->getColumn('module') === null) {
                Yii::$app->db->createCommand()->addColumn(
                    $this->migrationTable,
                    'module',
                    'varchar(32) NULL'
                )->execute();
            }
        }
        $query = new Query;
        $rows = $query->select(['version', 'path', 'apply_time', 'app_version', 'module'])
            ->from($this->migrationTable)
            ->orderBy('version DESC')
            ->where(['module' => $module])
            ->limit($limit)
            ->createCommand()
            ->queryAll();

        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            $rows[$i]['position'] = $i + 1;
            if ($rows[$i]['version'] === self::BASE_MIGRATION) {
                unset($rows[$i]);
                break;
            }
        }

        return $rows;
    }

    protected function createMigrationHistoryTable()
    {
        $tableName = Yii::$app->db->schema->getRawTableName($this->migrationTable);
        $this->_log[] = "Creating migration history table \"$tableName\"...";
        Yii::$app->db->createCommand()->createTable(
            $this->migrationTable,
            [
                'version' => 'varchar(180) NOT NULL PRIMARY KEY',
                'path' => 'varchar(255) NOT NULL',
                'apply_time' => 'integer',
                'app_version' => 'varchar(16) NULL',
                'module' => 'varchar(32) NULL',
            ]
        )->execute();
        Yii::$app->db->createCommand()->insert(
            $this->migrationTable,
            [
                'version' => self::BASE_MIGRATION,
                'path' => '',
                'apply_time' => time(),
                'app_version' => Yii::$app->version,
                'module' => null,
            ]
        )->execute();
        $this->_log[] = 'done.';
    }

    protected function addMigrationHistory($version, $path, $module = null)
    {
        $command = Yii::$app->db->createCommand();
        $command->insert(
            $this->migrationTable,
            [
                'version' => $version,
                'path' => $path,
                'apply_time' => time(),
                'app_version' => Yii::$app->version,
                'module' => $module,
            ]
        )->execute();
    }

    protected function removeMigrationHistory($version, $path, $module = null)
    {
        $command = Yii::$app->db->createCommand();
        $command->delete(
            $this->migrationTable,
            [
                'version' => $version,
                'path' => $path,
                'module' => $module,
            ]
        )->execute();
    }

    /**
     * Creates a new migration instance.
     *
     * @param string $class the migration class name
     * @param string $path the migration file path
     *
     * @return \yii\db\Migration the migration instance
     */
    protected function createMigration($class, $path)
    {
        $file = $path . DIRECTORY_SEPARATOR . $class . '.php';
        require_once $file;

        return new $class(['db' => Yii::$app->db]);
    }

    /**
     * Upgrades with the specified migration class.
     *
     * @param string $class the migration class name
     * @param string $path the migration file path
     * @param string $module the migration module
     *
     * @return bool whether the migration is successful
     */
    protected function migrateUp($class, $path, $module = null)
    {
        if ($class === self::BASE_MIGRATION) {
            $this->_lastStatus = Yii::t('fts-migrations', 'Base migration is already applied.');

            return true;
        }

        $this->_log[] = "*** applying $class in $path" . ($module !== null ? ' for module ' . $module : '');
        $start = microtime(true);
        $migration = $this->createMigration($class, $path);
        ob_start();
        $result = $migration->up();
        $this->_log[] = ob_get_contents();
        ob_end_clean();
        if ($result !== false) {
            $this->addMigrationHistory($class, $path, $module);
            $time = microtime(true) - $start;
            $this->_log[] = "*** applied $class (time: " . sprintf('%.3f', $time) . 's)';
            $this->_lastStatus = $module === null ? Yii::t('fts-migrations', 'Migration {name} successfully applied.', ['name' => $class]) : Yii::t('fts-migrations', 'Migration {name} for module {module} successfully applied.', ['name' => $class, 'module' => $module]);

            return true;
        } else {
            $time = microtime(true) - $start;
            $this->_log[] = "*** failed to apply $class (time: " . sprintf('%.3f', $time) . 's)';
            $this->_lastStatus = $module === null ? Yii::t('fts-migrations', 'Failed to apply migration {name}.', ['name' => $class]) : Yii::t('fts-migrations', 'Failed to apply migration {name} for module {module}.', ['name' => $class, 'module' => $module]);

            return false;
        }
    }

    /**
     * Downgrades with the specified migration class.
     *
     * @param string $class the migration class name
     * @param string $path the migration file path
     * @param string $module the migration module
     *
     * @return boolean whether the migration is successful
     */
    protected function migrateDown($class, $path, $module = null)
    {
        if ($class === self::BASE_MIGRATION) {
            return true;
        }

        $this->_log[] = "*** reverting $class in $path" . ($module !== null ? ' for module ' . $module : '');
        $start = microtime(true);
        $migration = $this->createMigration($class, $path);
        if ($migration->down() !== false) {
            $this->removeMigrationHistory($class, $path, $module);
            $time = microtime(true) - $start;
            $this->_log[] = "*** reverted $class (time: " . sprintf('%.3f', $time) . 's)';
            $this->_lastStatus = $module === null ? Yii::t('fts-migrations', 'Migration {name} successfully reverted.', ['name' => $class]) : Yii::t('fts-migrations', 'Migration {name} for module {module} successfully reverted.', ['name' => $class, 'module' => $module]);

            return true;
        } else {
            $time = microtime(true) - $start;
            $this->_log[] = "*** failed to revert $class (time: " . sprintf('%.3f', $time) . 's)';
            $this->_lastStatus = $module === null ? Yii::t('fts-migrations', 'Failed to revert migration {name}.', ['name' => $class]) : Yii::t('fts-migrations', 'Failed to revert migration {name} for module {module}.', ['name' => $class, 'module' => $module]);

            return false;
        }
    }

    /**
     * Upgrades the application by applying new migrations.
     * For example,
     *
     * ~~~
     * yii migrate     # apply all new migrations
     * yii migrate 3   # apply the first 3 new migrations
     * ~~~
     *
     * @param integer $limit the number of new migrations to be applied. If 0, it means
     *                       applying all available new migrations.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionUp($limit = 0)
    {
        $this->getNewMigrations();
        if (0 === count($this->_migrations)) {
            $this->_log[] = 'No new migration found. Your system is up-to-date.';
            $this->_lastStatus = Yii::t('fts-migrations', 'No new migration found. Your system is up-to-date.');

            return true;
        }

        $total = count($this->_migrations);
        $limit = (int)$limit;
        if ($limit > 0) {
            $this->_migrations = array_slice($this->_migrations, 0, $limit);
        }

        $n = count($this->_migrations);
        if ($n === $total) {
            $this->_log[] = "Total $n new " . ($n === 1 ? 'migration' : 'migrations') . ' to be applied:';
        } else {
            $this->_log[] = "Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . ' to be applied:';
        }

        foreach ($this->_migrations as $migration) {
            if (!$this->migrateUp($migration['version'], $migration['path'])) {
                $this->_log[] = 'Migration failed. The rest of the migrations are canceled.';
                $this->_lastStatus = Yii::t('fts-migrations', 'Failed to apply migration {name}.', ['name' => $migration['version']]);

                return false;
            }
        }
        $this->_log[] = 'Migrated up successfully.';
        $this->_lastStatus = Yii::t('fts-migrations', 'Successfully applied {count, plural, one{one migration} other{# migrations}}.', ['count' => count($this->_migrations)]);

        return true;
    }

    /**
     * Downgrades the application by reverting old migrations.
     * For example,
     *
     * ~~~
     * yii migrate/down     # revert the last migration
     * yii migrate/down 3   # revert the last 3 migrations
     * yii migrate/down all # revert all migrations
     * ~~~
     *
     * @param integer|null $limit the number of migrations to be reverted. Defaults to 1,
     *                       meaning the last applied migration will be reverted.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionDown($limit = 1)
    {
        if ($limit === 'all') {
            $limit = null;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                $this->_log[] = 'The step argument must be greater than 0.';
                $this->_lastStatus = Yii::t('fts-migrations', 'The step argument must be greater than 0.');

                return false;
            }
        }

        $migrations = $this->getMigrationHistory($limit);

        if (0 === count($migrations)) {
            $this->_log[] = 'No migration has been done before.';
            $this->_lastStatus = Yii::t('fts-migrations', 'No migration has been done before.');

            return true;
        }

        $n = count($migrations);
        $this->_log[] = "Total $n " . ($n === 1 ? 'migration' : 'migrations') . ' to be reverted:';
        foreach ($migrations as $migration) {
            $this->_log[] = '    ' . $migration['version'] . '(' . $migration['path'] . ')';
        }

        foreach ($migrations as $migration) {
            if (!$this->migrateDown($migration['version'], $migration['path'])) {
                $this->_log[] = 'Migration failed. The rest of the migrations are canceled.';
                $this->_lastStatus = Yii::t('fts-migrations', 'Failed to revert migration {name}.', ['name' => $migration['version']]);

                return false;
            }
        }
        $this->_log[] = 'Migrated down successfully.';
        $this->_lastStatus = Yii::t('fts-migrations', 'Successfully reverted {count, plural, one{# migration} other{# migrations}}.', ['count' => count($migrations)]);

        return true;
    }

    /**
     * Upgrades the module by applying new migrations.
     *
     * @param string $path the migration file path
     * @param string $module the migration module
     * @param integer $limit the number of new migrations to be applied. If 0, it means applying all available new migrations.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionModuleUp($path, $module, $limit = 0)
    {
        $this->getNewMigrationsForModule($path, $module);
        if (0 === count($this->_migrations)) {
            $this->_log[] = 'No new migration found. Your system is up-to-date.';
            $this->_lastStatus = Yii::t('fts-migrations', 'No new migration found. Your system is up-to-date.');

            return true;
        }

        $total = count($this->_migrations);
        $limit = (int)$limit;
        if ($limit > 0) {
            $this->_migrations = array_slice($this->_migrations, 0, $limit);
        }

        $n = count($this->_migrations);
        if ($n === $total) {
            $this->_log[] = "Total $n new " . ($n === 1 ? 'migration' : 'migrations') . ' to be applied:';
        } else {
            $this->_log[] = "Total $n out of $total new " . ($total === 1 ? 'migration' : 'migrations') . ' to be applied:';
        }

        foreach ($this->_migrations as $migration) {
            if (!$this->migrateUp($migration['version'], $migration['path'], $module)) {
                $this->_log[] = 'Migration failed. The rest of the migrations are canceled.';
                $this->_lastStatus = Yii::t('fts-migrations', 'Failed to apply migration {name}.', ['name' => $migration['version']]);

                return false;
            }
        }
        $this->_log[] = 'Migrated up successfully.';
        $this->_lastStatus = Yii::t('fts-migrations', 'Successfully applied {count, plural, one{one migration} other{# migrations}}.', ['count' => count($this->_migrations)]);

        return true;
    }

    /**
     * Downgrades the module by reverting old migrations.
     * For example,
     *
     * @param string $module the migration module
     * @param integer|null $limit the number of migrations to be reverted. Defaults to 1, meaning the last applied migration will be reverted.
     *
     * @return integer the status of the action execution. 0 means normal, other values mean abnormal.
     */
    public function actionModuleDown($module, $limit = 1)
    {
        if ($limit === 'all') {
            $limit = null;
        } else {
            $limit = (int)$limit;
            if ($limit < 1) {
                $this->_log[] = 'The step argument must be greater than 0.';
                $this->_lastStatus = Yii::t('fts-migrations', 'The step argument must be greater than 0.');

                return false;
            }
        }

        $migrations = $this->getMigrationHistory($limit, $module);

        if (0 === count($migrations)) {
            $this->_log[] = 'No migration has been done before.';
            $this->_lastStatus = Yii::t('fts-migrations', 'No migration has been done before.');

            return true;
        }

        $n = count($migrations);
        $this->_log[] = "Total $n " . ($n === 1 ? 'migration' : 'migrations') . ' to be reverted:';
        foreach ($migrations as $migration) {
            $this->_log[] = '    ' . $migration['version'] . '(' . $migration['path'] . ')';
        }

        foreach ($migrations as $migration) {
            if (!$this->migrateDown($migration['version'], $migration['path'], $module)) {
                $this->_log[] = 'Migration failed. The rest of the migrations are canceled.';
                $this->_lastStatus = Yii::t('fts-migrations', 'Failed to revert migration {name}.', ['name' => $migration['version']]);

                return false;
            }
        }
        $this->_log[] = 'Migrated down successfully.';
        $this->_lastStatus = Yii::t('fts-migrations', 'Successfully reverted {count, plural, one{# migration} other{# migrations}}.', ['count' => count($migrations)]);

        return true;
    }

    public function getDbVersion()
    {
        return (new Query())->select('version')->from($this->migrationTable)->orderBy(['version' => SORT_DESC])->limit(1)->scalar();
    }
}
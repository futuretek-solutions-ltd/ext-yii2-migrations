<?php

namespace futuretek\migrations;

use yii\base\NotSupportedException;
use yii\db\ColumnSchema;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Migration as YiiMigration;
use yii\db\Query;
use yii\db\TableSchema;

/**
 * Class FtsMigration
 *
 * @property Connection|array|string $db the DB connection object or the application component ID of the DB connection
 *
 * @package futuretek\migrations
 * @author  Lukas Cerny <lukas.cerny@futuretek.cz>
 * @license Apache-2.0
 * @link    http://www.futuretek.cz
 */
class FtsMigration extends YiiMigration
{
    /**
     * Import data from CSV file into database table
     *
     * @param string $file File name
     * @param string $table table name
     * @param array $mapping Mapping table between file and database columns (every file column must be mentioned even when it is not mapped (null value))
     * @return bool Import result
     */
    public function importCsv($file, $table, $mapping)
    {
        if (!file_exists($file)) {
            return false;
        }

        $mappingCount = count($mapping);
        if (($handle = fopen($file, 'rb')) !== false) {
            while (($data = fgetcsv($handle, 1000, ';')) !== false) {
                if ($mappingCount !== count($data)) {
                    continue;
                }

                $item = [];
                for ($i = 0; $i < $mappingCount; $i++) {
                    if (!empty($mapping[$i])) {
                        $item[$mapping[$i]] = $data[$i];
                    }
                }

                $this->insert($table, $item);
            }
            fclose($handle);
        }

        return true;
    }

    /**
     * Check if foreign key exists in specified table
     *
     * @param string $keyName FK name
     * @param string $tableName Table name
     * @return bool
     * @throws \RuntimeException
     */
    public function foreignKeyExists($keyName, $tableName)
    {
        $tableSchema = $this->getDb()->getTableSchema($tableName);
        if ($tableSchema === null) {
            throw new \RuntimeException(\Yii::t('fts-migrations', 'Schema for table {tbl} not found.', ['tbl' => $tableName]));
        }

        return array_key_exists($keyName, $tableSchema->foreignKeys);
    }

    /**
     * Check if table exists
     *
     * @param string $tableName Table name
     * @return bool
     */
    public function tableExists($tableName)
    {
        return $this->getDb()->getTableSchema($tableName) instanceof TableSchema;
    }

    /**
     * Check if column exists
     *
     * @param string $tableName Table name
     * @param string $columnName Column name
     * @return bool
     */
    public function columnExists($tableName, $columnName)
    {
        $tblSchema = $this->getDb()->getTableSchema($tableName);

        return $tblSchema instanceof TableSchema && $tblSchema->getColumn($columnName) instanceof ColumnSchema;
    }

    /**
     * Disable integrity check
     *
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function disableIntegrityCheck()
    {
        $this->db->createCommand()->checkIntegrity(false)->execute();
    }

    /**
     * Enable integrity check
     *
     * @throws \yii\base\NotSupportedException
     * @throws \yii\db\Exception
     */
    public function enableIntegrityCheck()
    {
        $this->db->createCommand()->checkIntegrity()->execute();
    }

    /**
     * Get last (maximal) inserted ID in specified table
     *
     * @param string $table Table name
     * @return mixed
     */
    public function getLastInsertID($table)
    {
        $primaryKey = $this->db->schema->getTableSchema($table, true)->primaryKey;

        return (new Query())
            ->from($table)
            ->max(reset($primaryKey));
    }

    /**
     * Set collation on column.
     * Usage:
     * ```
     * $this->string(128)->notNull()->append($this->collation('utf8_czech_ci'))
     * ```
     *
     * @param string $collation Collation to set
     * @return string
     */
    public function collation($collation = 'utf8_general_ci')
    {
        switch ($this->getDb()->getDriverName()) {
            case 'mysql':
            case 'sqlsrv':
            case 'dblib':
                return "COLLATE {$collation}";
            case 'pgsql':
                return "COLLATE \"{$collation}\"";
            case 'sqlite':
                if (false !== stripos($collation, 'bin')) {
                    return 'COLLATE BINARY';
                }
                if (false !== stripos($collation, '_ci')) {
                    return 'COLLATE NOCASE';
                }

                return '';
            default:
                throw new NotSupportedException("Driver {$this->db->driverName} not supported.");
        }
    }

    /**
     * Returns current date and time SQL expression
     *
     * @return Expression
     * @throws NotSupportedException
     */
    public function now()
    {
        switch ($this->db->driverName) {
            case 'sqlsrv':
                return new Expression('SYSDATETIME()');
            case 'mysql':
            case 'pgsql':
                return new Expression('NOW()');
            case 'oci':
                return new Expression('CURRENT_DATE');
            default:
                throw new NotSupportedException("Driver {$this->db->driverName} not supported.");
        }
    }

    /**
     * Return index name compatible with all PDO DBMS.
     * Index name length limitations:
     * * Oracle - 30 chars
     * * PgSQL - 63 chars
     * * MySQL - 64 chars
     * * SQL Server - 128 chars
     *
     * In case of Oracle all index names will be hashed to prevent duplicities
     *
     * @param string $name Index name
     * @param string $prefix Index prefix
     * @param string $suffix Index suffix
     *
     * @return string
     */
    public function getIndexName($name, $prefix = 'idx', $suffix = '')
    {
        switch ($this->db->driverName) {
            case 'sqlsrv':
                $maxLength = 128;
                break;
            case 'pgsql':
                $maxLength = 63;
                break;
            case 'mysql':
                $maxLength = 64;
                break;
            case 'oci':
                $maxLength = 30;
                $name = md5($name);
                break;
            default:
                throw new NotSupportedException("Driver {$this->db->driverName} not supported.");
        }

        $prefix = $prefix ? rtrim($prefix, '_') . '_' : '';
        $suffix = $suffix ? '_' . ltrim($suffix, '_') : '';

        $prefixLen = strlen($prefix);
        $suffixLen = strlen($suffix);

        return $prefix . substr($name, 0, $maxLength - ($prefixLen + $suffixLen)) . $suffix;
    }
}

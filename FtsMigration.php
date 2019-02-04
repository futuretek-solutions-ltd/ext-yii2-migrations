<?php

namespace futuretek\migrations;

use yii\db\Migration as YiiMigration;
use yii\db\Query;
use yii\db\TableSchema;

/**
 * Class FtsMigration
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
}
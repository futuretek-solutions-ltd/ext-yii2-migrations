<?php

namespace futuretek\migrations;

use yii\db\Migration as YiiMigration;
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
     * Insert key into option table
     *
     * @param string $name Name (key) - only uppercase letters and underscore
     * @param string $defaultValue Default value
     * @param string $title Title
     * @param string $description Description
     * @param string $type Type - allowed only Option::TYPE_xxx constants
     * @param int $system Is system option (invisible to users)
     * @param string $unit Unit of measurement
     */
    public function insertOption($name, $defaultValue, $title, $description, $type, $system = 0, $unit = null)
    {
        $this->insert('option', [
            'name' => $name,
            'value' => $defaultValue,
            'title' => $title,
            'description' => $description,
            'default_value' => $defaultValue,
            'unit' => $unit,
            'system' => (int)$system,
            'type' => $type,
            'context' => 'Option',
            'context_id' => null,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
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
}
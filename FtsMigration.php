<?php

namespace futuretek\migrations;

use yii\db\Expression;
use yii\db\Migration as YiiMigration;

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
     * @param string $data Serialized data for drop-downs
     */
    public function insertOption($name, $defaultValue, $title, $description, $type, $system = 0, $unit = null, $data = null)
    {
        $this->insert('option', [
            'name' => $name,
            'value' => $defaultValue,
            'title' => $title,
            'description' => $description,
            'data' => $data,
            'default_value' => $defaultValue,
            'unit' => $unit,
            'system' => (int)$system,
            'type' => $type,
            'context' => 'Option',
            'context_id' => null,
            'created_at' => new Expression('NOW()'),
            'updated_at' => new Expression('NOW()'),
        ]);
    }

}
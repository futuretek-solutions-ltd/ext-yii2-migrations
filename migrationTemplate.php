<?php
/**
 * File migrationTemplate.php
 *
 * @package ext-migrations
 * @author Petr Compel <petrleocompel@futuretek.cz>
 * @license Apache-2.0
 * @link http://www.futuretek.cz
 *
 * @var $className string the new migration class name
 */

echo '<?php';?>

use \futuretek\migrations\FtsMigration;

class <?= $className ?> extends FtsMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        //todo: implement migration
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //todo: implement reverse migration
    }
}

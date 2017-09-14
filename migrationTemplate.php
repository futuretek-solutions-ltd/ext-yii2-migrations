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

/**
 * File <?= $className ?>.php
 *
 * @author SomeBody <somebody@futuretek.cz>
 * @license Apache-2.0
 * @link http://www.futuretek.cz
 */
class <?= $className ?> extends \futuretek\migrations\FtsMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
    }
}

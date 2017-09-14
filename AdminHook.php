<?php

namespace futuretek\migrations;

use futuretek\admin\classes\Hook;
use yii\helpers\Html;

/**
 * Class AdminHook
 * @package futuretek\options
 */
class AdminHook extends Hook
{

    public function init()
    {
        $this->controllerRoute = 'migrations';
        $this->controllerClass = 'futuretek\migrations\controllers\AdminController';
    }

    /**
     * Return menu collection
     *
     * @return array
     */
    public function getMenuArray()
    {
        $migrations = new Migration();
        $count = count($migrations->getNewMigrations());
        return [
            [
                'encode' => false,
                'label' => Html::tag('span', $this->getName()) .
                    ($count > 0 ? ' ' . Html::tag('small', $count, ['class' => 'label pull-right bg-red']) : ''),
                'icon' => $this->getIcon(),
                'url' => [$this->baseUrl('migrations/index')]
            ]
        ];
    }

    /**
     * Get extension name
     *
     * @return string Name
     */
    public function getName()
    {
        return \Yii::t('fts-migrations', 'Migrations');
    }

    /**
     * Get extension description
     *
     * @return string Description
     */
    public function getDescription()
    {
        return \Yii::t('fts-migrations', 'Migration utility');
    }

    /**
     * Get extension icon
     *
     * @return string Icon
     */
    public function getIcon()
    {
        return 'fa fa-database';
    }
}

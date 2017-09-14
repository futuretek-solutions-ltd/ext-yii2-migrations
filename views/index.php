<?php
/**
 * @var yii\web\View $this
 * @var \yii\data\ActiveDataProvider $newMigrations
 * @var \yii\data\ActiveDataProvider $appliedMigrations
 * @var bool $hasNewMigrations
 * @var bool $hasAppliedMigrations
 */
use futuretek\grid\GridView;
use yii\helpers\Html;

$this->title = Yii::t('fts-migrations', 'Migrations');
?>
<?php
$this->beginBlock('breadcrumbs');
if ($hasNewMigrations) { ?>
    <div class="btn-group">
        <?= Html::a('<i class="fa fa-angle-double-up"></i>&nbsp; ' . Yii::t('fts-migrations', 'Migrate up'), ['up'], [
            'class' => 'btn btn-primary',
            'title' => Yii::t('fts-migrations', 'Apply all un-applied migrations'),
            'data-method' => 'post',
            'data-params' => ['limit' => 'all']
        ]); ?>
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
            <span class="caret"></span>
            <span class="sr-only"><?= Yii::t('fts-migration', 'Toggle Dropdown') ?></span>
        </button>
        <ul class="dropdown-menu" role="menu">
            <li>
                <?= Html::a('<i class="fa fa-angle-up"></i>&nbsp; ' . Yii::t('fts-migrations', 'Migrate one up'), ['up'], [
                    'data-method' => 'post',
                    'title' => Yii::t('fts-migrations', 'Apply the first un-applied migration'),
                    'data-params' => ['limit' => 1]
                ]); ?>
            </li>
        </ul>
    </div>
<?php } ?>
<?php if ($hasAppliedMigrations) { ?>
    <div class="btn-group">
        <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                aria-expanded="false">
            <i class="fa fa-wrench"></i>&nbsp; <?= Yii::t('fts-migrations', 'Other options') ?> &nbsp;<span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-right" role="menu">
            <li>
                <?= Html::a('<i class="fa fa-stethoscope"></i> ' . Yii::t('fts-migrations', 'Optimize database'), ['optimize'], [
                    'data-method' => 'post',
                    'title' => Yii::t('fts-migrations', 'Optimize all database tables'),
                ]); ?>
            </li>
            <li role="separator" class="divider"></li>
            <li>
                <?= Html::a('<i class="fa fa-warning text-danger"></i> ' . Yii::t('fts-migrations', 'Clear database'), ['drop'], [
                    'data-method' => 'post',
                    'title' => Yii::t('fts-migrations', 'Drop all tables from database'),
                    'data-confirm' => Yii::t('fts-migrations', 'Are you REALLY sure to DROP ALL TABLES from the database? This operation CAN NOT be undone!'),
                ]); ?>
            </li>
        </ul>
    </div>
<?php }
$this->endBlock();
?>
<div class="row">
    <div class="col-xs-12">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= Yii::t('fts-migrations', 'New migrations') ?></h3>
            </div>
            <?= GridView::widget([
                'dataProvider' => $newMigrations,
                'responsive' => false,
                'export' => false,
                'layout' => '<div class="box-body table-responsive">{items}</div><div class="box-footer clearfix"><div class="col-md-6 col-xs-12">{summary}</div><div class="col-md-6 col-xs-12 text-right text-center-xs">{pager}</div></div>',
                'pjax' => true,
                'columns' => [
                    [
                        'attribute' => 'version',
                        'label' => Yii::t('fts-migrations', 'Name'),
                    ],
                    [
                        'attribute' => 'path',
                        'label' => Yii::t('fts-migrations', 'Path'),
                    ],
                ],
                'pager' => [
                    'options' => ['class' => 'pagination pagination-sm no-margin']
                ]
            ]); ?>
        </div>
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title"><?= Yii::t('fts-migrations', 'Applied migrations') ?></h3>
            </div>
            <?= GridView::widget([
                'dataProvider' => $appliedMigrations,
                'responsive' => false,
                'export' => false,
                'layout' => '<div class="box-body table-responsive">{items}</div><div class="box-footer clearfix"><div class="col-md-6 col-xs-12">{summary}</div><div class="col-md-6 col-xs-12 text-right text-center-xs">{pager}</div></div>',
                'pjax' => true,
                'columns' => [
                    [
                        'attribute' => 'version',
                        'label' => Yii::t('fts-migrations', 'Name'),
                    ],
                    [
                        'attribute' => 'path',
                        'label' => Yii::t('fts-migrations', 'Path'),
                    ],
                    [
                        'attribute' => 'apply_time',
                        'label' => Yii::t('fts-migrations', 'Apply time'),
                        'format' => 'raw',
                        'value' => function ($model, $key, $index) {
                            return Html::tag('span', Yii::$app->formatter->asDatetime($model['apply_time']), [
                                'title' => $model['apply_time'],
                            ]);
                        }
                    ],
                    [
                        'attribute' => 'app_version',
                        'label' => Yii::t('fts-migrations', 'Version'),
                    ],
                    [
                        'attribute' => 'module',
                        'label' => Yii::t('fts-migrations', 'Module'),
                    ],
                    [
                        'class' => 'kartik\grid\ActionColumn',
                        'template' => '{down}',
                        'buttons' => [
                            'down' => function ($url, $model, $key) {
                                return Html::a(Yii::t('fts-migrations', 'Migrate down to'), ['down'], [
                                    'class' => 'btn btn-danger btn-xs',
                                    'data-confirm' => Yii::t('fts-migrations', 'Are you sure to decrease database structures to this version? Data from newer migrations may be lost!'),
                                    'data-method' => 'post',
                                    'data-params' => ['limit' => $model['position']]
                                ]);
                            },
                        ],
                    ],
                ],
                'pager' => [
                    'options' => ['class' => 'pagination pagination-sm no-margin']
                ]
            ]); ?>
        </div>
    </div>
</div>
<?php
namespace easydowork\rbacBackup;

use mdm\admin\components\Configs;
use Yii;
use yii\caching\TagDependency;
use yii\console\Controller;
use yii\db\Query;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Class RbacController
 * @property string $cachePath
 * @property string $roleCachePath
 * @property array $tableList
 * @package console\controllers
 */
class RbacController extends Controller
{

    /**
     * @var string
     */
    public $cachePath = '@console/rbacBackUpCache';


    /**
     * @var string
     */
    public $roleCachePath = '@backend/runtime/cache';

    /**
     * @var array
     */
    public $tableList = [
        'auth_rule',
        'auth_item',
        'auth_item_child',
        'menu',
    ];

    /**
     * init
     */
    public function init()
    {
        $this->validationDir();
    }

    public function validationDir()
    {
        $dir = Yii::getAlias($this->cachePath);

        try {
            FileHelper::createDirectory($dir,0777);
            if (!@chmod($dir, 0777)) {
                $this->stdout("Operation chmod not permitted for directory $dir.",Console::BG_RED);
            }
        }catch (\Exception $e){
            $this->stdout($e->getMessage(),Console::BG_RED);
        }
    }

    /**
     * actionBackUp
     */
    public function actionBackUp()
    {
        try {
            foreach ($this->tableList as $table){
                $fileName = Yii::getAlias($this->cachePath).DIRECTORY_SEPARATOR.$table;
                file_put_contents($fileName,serialize((new Query())->from($table)->all()));
            }
            $this->stdout('back up ok.',Console::BG_GREEN);
        }catch (\Exception $e){
            $this->stdout($e->getMessage(),Console::BG_RED);
        }

    }

    /**
     * actionUpdate
     */
    public function actionUpdate()
    {
        try {
            $db = Yii::$app->db;

            $db->createCommand()->checkIntegrity(false)->execute();
            foreach ($this->tableList as $table){
                $fileName = Yii::getAlias($this->cachePath).DIRECTORY_SEPARATOR.$table;
                $data = unserialize(file_get_contents($fileName));
                if(!empty($data)){
                    $db->transaction(function () use ($db,$table,$data){
                        $db->createCommand()->truncateTable($table)->execute();
                        $db->createCommand()->batchInsert($table,array_keys($data[0]),$data)->execute();
                    });
                }
            }
            $db->createCommand()->checkIntegrity()->execute();
            //invalidate rbac cache
            Yii::$app->cache->cachePath = Yii::getAlias($this->roleCachePath);

            TagDependency::invalidate(Yii::$app->cache, Configs::CACHE_TAG);

            $this->stdout('update ok.',Console::BG_GREEN);
        }catch (\Throwable $e){
            $this->stdout($e->getMessage(),Console::BG_RED);
        }
    }

}
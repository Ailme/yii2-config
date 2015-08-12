<?php

namespace bupy7\config\commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yii\db\Query;
use bupy7\config\models\Config;
use bupy7\config\Module;

/**
 * Configurtion manager for create, delete and update configuration parameters of application.
 * 
 * @author Belosludcev Vasilij http://mihaly4.ru
 * @since 1.0.0
 */
class ManagerController extends Controller
{  
    /**
     * @var array List parameters of config the application.
     * @see Module::$params
     */
    protected $params;
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->params = Module::getInstance()->params;
    }
    
    /**
     * Initialize configuration of application.
     */
    public function actionInit()
    {
        if (!$this->confirm('Initialization configuration of application?')) {
            return self::EXIT_CODE_NORMAL;
        }     
        // insert params
        foreach ($this->params as $param) {
            $this->insert($param);
        }       
        // flush cache
        $this->run('cache/flush-all');      
        $this->stdout("\nConfiguration successfully initialized.\n", Console::FG_GREEN);
    }
    
    /**
     * Adding new configuration parameters of application.
     */
    public function actionAddNew()
    {        
        $all = count($this->params);
        $success = 0;
        foreach ($this->params as $param) {
            if (
                !(new Query)
                    ->from(Config::tableName())
                    ->where([
                        'module' => $param['module'], 
                        'name' => $param['name'],
                    ])
                    ->exists()
            ) {
                $success += $this->insert($param);
            }
        }      
        // flush cache
        if ($success > 0) {
            $this->run('cache/flush-all');
        }       
        $this->stdout(
            "New configuration successfully added. All: {$all}. Successfully: {$success}.\n",
            Console::FG_GREEN
        );
    }
    
    /**
     * Insert configuration parameter.
     * @param array $param Parameter from $config.
     * @return integer
     */
    protected function insert(array $param)
    {
        foreach (['rules', 'options'] as $binary) {
            if (isset($param[$binary])) {
                $param[$binary] = serialize($param[$binary]);
            }
        }
        return Yii::$app->db->createCommand()->insert(Config::tableName(), $param)->execute();
    }   
}


<?php

include_once('Module.php');

class ModuleLoader{
    // str
    private $modulePath;
    // str
    private $staticPath;
    // str
    private $templatePath;
    
    // array use as stack;
    var $requireCheck;
    // array use as array;
    static $moduleHasBeenLoaded;
    
    public function ModuleLoader($modulePath, $staticPath, $templatePath){
        $this -> modulePath = $modulePath;
        $this -> staticPath = $staticPath;
        $this -> templatePath = $templatePath;
        
        $this -> requireCheck = array();
        $this -> moduleHasBeenLoaded = array();
    }
    
    public function resolveModule($moduleName, &$moduleToBeLoaded/*, $requireModule*/){
        if(func_num_args() == 3){
            $requireModule = func_get_arg(2);
        }    
        if(count($this -> requireCheck) > 0 && in_array($moduleName, $this -> requireCheck)){
            echo "you are circular requiring your modules: $moduleName $requireModule->name\n";    
            die(1);
        }
        array_push($this -> requireCheck, $moduleName);
        
        // 
        // start resolving 
        //
        $module = new Module($moduleName);
        if(isset($requireModule)){
            $module->prioritize($requireModule);
            $requireModule->addRequire($module);
        }
        if(array_key_exists($moduleName, $moduleToBeLoaded)){
            if($moduleToBeLoaded[$moduleName]->priority < $module->priority){
                $moduleToBeLoaded[$moduleName] = $module;
            }
        }
        else{
            $moduleToBeLoaded[$moduleName] = $module;
        }
        if(is_dir("$this->modulePath/$moduleName")){
            if (is_file("$this->modulePath/$moduleName/require.php")){
                //
                // todo: no need to include it again, make a cache.
                $requireList = include("$this->modulePath/$moduleName/require.php");
                if(count($requireList) > 0){
                   foreach($requireList as $name){
                       $this->resolveModule($name, &$moduleToBeLoaded, $module);
                   }
                }
            }
        }
        else{
            echo "cannot find module $module\n";
            die(1);
        }
        //
        // exiting the top level recursion
        //
        if(!isset($requireModule)){
            usort($moduleToBeLoaded, create_function('$a,$b', 'return $a->priority < $b->priority;'));
        }
        array_pop($this -> requireCheck);
    }
}

?>
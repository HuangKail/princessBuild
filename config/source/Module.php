<?php
class Module{
    var $name;
    var $requireList;
    var $priority;
    public function Module($value){
        $this->name = $value;
        $this->requireList = array();
        $this->priority = 1;
    }
    
    public function addRequire($requireModule){
        array_push($this->requireList, $requireModule);
    }
    
    public function prioritize($requireModule){
        $this->priority = $requireModule->priority * 10;
        
        foreach ($this->requireList as $module) {
            $module->prioritize($this);
        }
    }
    
}
?>
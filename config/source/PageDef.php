<?php
include_once ("ModuleLoader.php");
class PageDef {
    // array
    var $pageHeadScript;
    // array
    var $pageHeadStyle;
    // array
    var $pageFootScript;
    // str
    var $pageTitle;
    // array
    var $resouce;
    // str
    var $pageModule;
    // array
    var $modulesTemplate;

    // str
    private $modulePath;
    // str
    private $staticPath;
    // str
    private $templatePath;

    // array
    private $jsModuleLoaded;
    // array
    private $cssModuleLoaded;
    // array
    private $templateModuleLoaded;

    // ModuleLoader
    private $moduleLoader;

    // these variables are for releasing
    private $cssPath;
    private $jsPath;

    // tokens to be replaced
    private $tokens;
    private $pageName;

    public function PageDef($pageName, &$pageArr, $modulePath, $staticPath, $templatePath) {
        $this -> modulePath = $modulePath;
        $this -> staticPath = $staticPath;
        $this -> templatePath = $templatePath;
        $this -> pageModule = $pageArr['pageTemplate'];
        $this -> pageName = $pageName;

        if (!is_file("$modulePath/$this->pageModule/template/$this->pageModule.html")) {
            echo "error: there is no page module file $this->pageModule\n";
            die(1);
        }

        $this -> pageTitle = $pageArr['pageTitle'];
        $this -> pageHeadScript = $pageArr['pageHeadScript'];
        $this -> pageHeadStyle = $pageArr['pageHeadStyle'];
        $this -> pageFootScript = $pageArr['pageFootScript'];
        $this -> resouce = $pageArr['resouce'];
        $this -> modulesTemplate = $pageArr['modulesTemplate'];
        $this -> cssPath = $pageArr['cssPath'];
        $this -> jsPath = $pageArr['jsPath'];

        // init loader
        $this -> moduleLoader = new ModuleLoader($modulePath, $staticPath, $templatePath);
        $this -> cssModuleLoaded = array();
        $this -> templateModuleLoaded = array();
        $this -> jsModuleLoaded = array();
        $this -> tokens = array();

    }

    public function process() {
        $this -> tokens['pageTitle'] = $this -> pageTitle;
        foreach ($this->pageHeadStyle as $resourceName) {
            if (!isset($this -> tokens[$resourceName])) {
                $this -> tokens['pageHeadStyle'] = $this -> processCss($resourceName);
            } else {
                $this -> tokens['pageHeadStyle'] .= $this -> processCss($resourceName);
            }
        }

        foreach ($this->pageHeadScript as $resourceName) {
            if (!isset($this -> tokens[$resourceName])) {
                $this -> tokens['pageHeadScript'] = $this -> processJs($resourceName);
            } else {
                $this -> tokens['pageHeadScript'] .= $this -> processJs($resourceName);
            }
        }

        foreach ($this->pageFootScript as $resourceName) {
            if (!isset($this -> tokens[$resourceName])) {
                $this -> tokens['pageFootScript'] = $this -> processJs($resourceName);
            } else {
                $this -> tokens['pageFootScript'] .= $this -> processJs($resourceName);
            }
        }

        $this -> processTemplate();

        $this -> processPage();

        $this -> replaceToken();
    }

    public function processPage() {
        $moduleToBeLoaded = array();
        $this -> moduleLoader -> resolveModule($this -> pageModule, &$moduleToBeLoaded);
        $this -> copyTemlpateFiles(&$moduleToBeLoaded);
    }

    private function processTemplate() {
        $moduleToBeLoaded = array();
        foreach ($this->modulesTemplate as $moduleName) {
            $this -> moduleLoader -> resolveModule($moduleName, &$moduleToBeLoaded);
        }
        $this -> copyTemlpateFiles(&$moduleToBeLoaded);
    }

    private function processCss($resouceName) {
        $moduleToBeLoaded = array();
        if (isset($this -> resouce['css'][$resouceName])) {
            foreach ($this->resouce['css'][$resouceName] as $moduleName) {
                $this -> moduleLoader -> resolveModule($moduleName, &$moduleToBeLoaded);
            }
            $fileList = $this -> copyCssFiles(&$moduleToBeLoaded);
            //
            // file name is $resouceName and modules are in $moduleToBeLoaded
            //
            $content = $this -> createCssImportFile($resouceName, &$fileList);
            if(!is_dir("$this->templatePath/inc/")){
                system("mkdir $this->templatePath/inc/");
            }
            file_put_contents("$this->templatePath/inc/{$resouceName}.inc", $content);
        } else {
            echo "process Head ERROR! pageDefinition: $this->pageModule css: $resouceName does not exist";
            die(1);
        }
        $retStr = "<&include file=\"princess/inc/{$resouceName}.inc\"&>";
        return $retStr;
    }

    private function processJs($resouceName) {
        $moduleToBeLoaded = array();
        $type = $this -> resouce['js'][$resouceName]['type'];
        $scriptContent = '';
        if (isset($this -> resouce['js'][$resouceName]['modules'])) {
            foreach ($this->resouce['js'][$resouceName]['modules'] as $moduleName) {
                $this -> moduleLoader -> resolveModule($moduleName, &$moduleToBeLoaded);
            }
            $fileList = $this -> copyjsFiles(&$moduleToBeLoaded, $type == 'inline');
            //
            // file name is $resouceName and modules are in $moduleToBeLoaded
            //
            $content = $this -> createJsImportFile($resouceName, &$fileList, $type == 'inline');
            if(!is_dir("$this->templatePath/inc/")){
                system("mkdir $this->templatePath/inc/");
            }
            file_put_contents("$this->templatePath/inc/{$resouceName}.inc", $content);
        } else {
            echo "process Head ERROR! pageDefinition: $this->pageModule css: $resouceName does not exist";
            die(1);
        }
        $temp = '';
        if ($type == 'inline'){
            $temp = 'inline';
        }
        $retStr = "<&include file=\"princess/inc/{$resouceName}.inc\"{$temp}&>";
        return $retStr;
    }

    private function copyTemlpateFiles(&$modules) {
        foreach ($modules as $module) {
            if (in_array($module -> name, $this -> templateModuleLoaded)) {
                continue;
            }
            if (is_dir("$this->modulePath/$module->name/template") && strlen(system("ls $this->modulePath/$module->name/template")) > 0) {
                system("cp -r $this->modulePath/$module->name/template/* $this->templatePath/");
            }
            $this -> templateModuleLoaded[] = $module -> name;
        }
    }

    private function & copyCssFiles(&$modules) {
        $dirObj;
        $fileList = array();
        foreach ($modules as $module) {
            if (in_array($module -> name, $this -> cssModuleLoaded) || !is_dir("$this->modulePath/$module->name/static/css")) {
                continue;
            }
            if (!is_dir("$this->staticPath/css")) {
                system("mkdir $this->staticPath/css");
            }
            $dirObj = opendir("$this->modulePath/$module->name/static/css");
            if ($dirObj !== FALSE) {
                while (($filename = readdir($dirObj)) !== FALSE) {
                    if ($filename == '.' || $filename == '..') {
                        continue;
                    }
                    system("cp -r $this->modulePath/$module->name/static/css/$filename $this->staticPath/css/{$module -> name}_{$filename}");
                    $fileList[] = "{$module -> name}_{$filename}";
                }
                closedir($dirObj);
            }
            if (is_dir("$this->modulePath/$module->name/static/img/") && strlen(system("ls $this->modulePath/$module->name/static/img/")) > 0) {
                system("cp -r $this->modulePath/$module->name/static/img/* $this->staticPath/img/");
            }
            $this -> cssModuleLoaded[] = $module -> name;
        }
        return $fileList;
    }

    private function & copyJsFiles(&$modules, $isInline) {
        $dirObj;
        $fileList = array();
        foreach ($modules as $module) {
            if (in_array($module -> name, $this -> jsModuleLoaded)) {
                continue;
            }
            if (!is_dir("$this->staticPath/js")) {
                system("mkdir $this->staticPath/js");
            }
            $this -> getJsFileList("$this->modulePath/{$module->name}/static/js", $module -> name, &$fileList, $isInline);
            $temp = '';
            if (strlen($temp = system("ls $this->modulePath/{$module -> name}/static/js/")) > 0) {
                system("mkdir $this->staticPath/js/{$module -> name}");
                system("cp -rf $this->modulePath/{$module -> name}/static/js/* $this->staticPath/js/{$module -> name}");
            }
            $this -> jsModuleLoaded[] = $module -> name;
        }
        return $fileList;
    }

    private function getJsFileList($dirPath, $moduleName, &$fileList, $isInline) {
        $dirObj = opendir($dirPath);
        while (($filename = readdir($dirObj)) !== FALSE) {
            if ($filename == '.' || $filename == '..') {
                continue;
            }
            $temp = $filename;
            if (strlen($filename) > 3 && substr($temp, strlen($temp) - 3, 3) == ".js") {
                if(!$isInline){
                    $fileList[] = "{$moduleName}/{$filename}";
                }
                else{
                    $fileList[] = "$dirPath/$filename";
                }
            } else if (is_dir("$dirPath/$filename")) {
                $this -> getJsFileList("{$dirPath}/{$filename}", "{$moduleName}/{$filename}", &$fileList, $isInline);
            }
        }
        closedir($dirObj);
    }

    private function replaceToken() {
        $fileContent = file_get_contents("$this->modulePath/$this->pageModule/template/$this->pageModule.html");
        foreach ($this->tokens as $key => $value) {
            $fileContent = str_replace("<!--<#$key#>-->", $value, $fileContent);
        }
        file_put_contents("$this->templatePath/$this->pageName.html", $fileContent);
    }

    private function createCssImportFile($filename, &$filenameList) {
        $outputStr = "";
        foreach ($filenameList as $file) {
            $outputStr .= "@import url(\"$file\");\n";
        }
        file_put_contents("{$this->staticPath}/css/{$filename}.css", $outputStr);
        
        $cssPath = $this -> cssPath;
        if ($cssPath[strlen($cssPath) - 1] == '/') {
            $cssPath = substr($cssPath, 0, strlen($cssPath) - 1);
        }
        $retStr = "<link type='text/css' rel='stylesheet' href='{$cssPath}/{$filename}.css?v=md5' />";
        return $retStr;
    }

    private function createJsImportFile($filename, &$filenameList, $isInline) {
        $outputStr = "";
        if (!$isInline) {
            $outputStr = file_get_contents('./source/externalJsTemplate.js');
            $outputStr .= "\n";
            foreach ($filenameList as $file) {
                $outputStr .= "importScript(\"$file\");\n";
            }
        }
        else {
            foreach($filenameList as $file){
                $outputStr .= file_get_contents($file);
                $i = 1;
                $lastWord = $outputStr[strlen($outputStr) - $i];
                while($lastWord == EOF || $lastWord == "\n" || $lastWord == "\t" || $lastWord == ' '){
                    $lastWord = $outputStr[strlen($outputStr) - $i++];
                }
                if($lastWord !== ';' && $lastWord !== '}'){
                    $outputStr .= ';';
                }
            }
        }
        $jsPath = $this -> jsPath;
        if ($jsPath[strlen($jsPath) - 1] == '/') {
            $jsPath = substr($jsPath, 0, strlen($jsPath) - 1);
        }
        $retStr = '';
        if (!$isInline) {
            $retStr = "<script type='text/javascript' src='$jsPath/{$filename}.js?v=md5'></script>";
        } else {
            $retStr = "<script type='text/javascript' src='$jsPath/{$filename}.js?v=md5' InlineContent></script>";
        }
        file_put_contents("{$this->staticPath}/js/{$filename}.js", $outputStr);
        return $retStr;
    }

}
?>
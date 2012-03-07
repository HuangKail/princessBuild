<?php
    return array(
        'pageTemplate' => 'basicpage',
        'pageTitle' => 'testpage',
        'pageHeadStyle' => array('princess'),
        'pageHeadScript' => array('index_boot', 'index_boot_inline',),
        'pageFootScript' => array(),
        'headTemplate' => array('layout', 'album_min'),
        'contentTemplate' => array('basic_info'),
        'resouce' => array(
            'js' => array(
                'index_boot_inline' => array(
                    'type' => 'inline',
                    'modules' => array('album'),
                ),
                'index_boot' => array(
                    'type' => 'ext',
                    'modules' => array('base', 'album_min', 'tangram_move',  'basic_info'),
                ), 
            ),
            'css' => array(
				'princess' => array('basicpage', 'layout', 'album_min', 'honor'),
            ),
        ), 
        'cssPath' => '<&$pDomain.static&>/static/princess/css/',
        'jsPath' => '<&$pDomain.static&>/static/princess/js/',
    );
?>

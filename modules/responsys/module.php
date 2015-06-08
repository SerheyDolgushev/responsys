<?php
/**
 * @package   Responsys
 * @author    Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date      08 Jun 2015
 * */
$Module = array(
    'name'      => 'Responsys',
    'functions' => array()
);

$ViewList = array(
    'logs' => array(
        'script'                  => 'logs.php',
        'functions'               => array('logs'),
        'params'                  => array(),
        'default_navigation_part' => 'ezsetupnavigationpart'
    )
);

$FunctionList = array(
    'logs' => array()
);

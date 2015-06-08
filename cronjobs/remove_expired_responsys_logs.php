<?php
/**
 * @package   Responsys
 * @author    Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date      08 Jun 2015
 * */
$cli->output('Starting removing expired Responsys logs');
eZPersistentObject::removeObject(
    ResponsysLog::definition(), array(
    'date' => array('<=', time() - ResponsysLog::getExpiryTime())
    )
);

$cli->output('Expired Responsys logs are removed');

<?php
/**
 * @package   Responsys
 * @author    Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date      08 Jun 2015
 * */
$http          = eZHTTPTool::instance();
$defaultFilter = array(
    'error'    => null,
    'request'  => null,
    'response' => null
);

$filter = $http->sessionVariable('responsys_logs_filter');
if ($filter === null) {
    $filter = $defaultFilter;
}

if ($http->hasVariable('filter')) {
    $filter = array_merge((array) $filter, (array) $http->variable('filter'));
}
$http->setSessionVariable('responsys_logs_filter', $filter);

$customConds = null;
$conditions  = array();
if (strlen($filter['error']) !== 0) {
    if ((bool) $filter['error']) {
        $conditions['response_error'] = array('<>', '');
    } else {
        $customConds .= ' AND response_error IS NULL';
    }
}
if (strlen($filter['request']) !== 0) {
    $conditions['request'] = array('like', '%' . $filter['request'] . '%');
}
if (strlen($filter['response']) !== 0) {
    $conditions['response'] = array('like', '%' . $filter['response'] . '%');
}

if (count($conditions) === 0) {
    $conditions  = null;
    $customConds = ' WHERE 1 ' . $customConds;
}

$params      = $Params['Module']->UserParameters;
$offset      = isset($params['offset']) ? (int) $params['offset'] : 0;
$limit       = isset($params['limit']) ? (int) $params['limit'] : 20;
$limitations = array(
    'limit'  => $limit,
    'offset' => $offset
);

$tpl = eZTemplate::factory();
$tpl->setVariable('logs', ResponsysLog::fetchList($conditions, $limitations, $customConds));
$tpl->setVariable('filter', $filter);
$tpl->setVariable('offset', $offset);
$tpl->setVariable('limit', $limit);
$tpl->setVariable('total_count', ResponsysLog::countAll($conditions));

$Result['content'] = $tpl->fetch('design:responsys/logs.tpl');
$Result['path']    = array(
    array(
        'text' => ezpI18n::tr('extension/responsys', 'Responsys logs'),
        'url'  => false
    )
);

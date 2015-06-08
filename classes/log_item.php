<?php
/**
 * @package   Responsys
 * @class     ResponsysLog
 * @author    Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date      08 Jun 2015
 * */
class ResponsysLog extends eZPersistentObject
{

    public function __construct($row = array())
    {
        parent::__construct($row);

        if ($this->attribute('id') === null) {
            $skipClasses = array('eZProcess', 'eZModule', __CLASS__);
            $bt          = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            foreach ($bt as $i => $call) {
                // remove calls for current class and index.php
                if (isset($call['class']) === false || in_array($call['class'], $skipClasses)) {
                    unset($bt[$i]);
                }
            }
            $bt = array_reverse($bt);

            $baseDir = getcwd() . '/';
            foreach ($bt as $call) {
                $backtrace[] = array(
                    'file'     => isset($call['file']) ? str_replace($baseDir, '', $call['file']) : null,
                    'line'     => isset($call['line']) ? $call['line'] : null,
                    'function' => isset($call['function']) ? $call['function'] : null,
                    'class'    => isset($call['class']) ? $call['class'] : null,
                    'type'     => isset($call['type']) ? $call['type'] : null,
                );
            }

            $this->setAttribute('backtrace', $backtrace);
        } else {
            $this->setAttribute('backtrace', unserialize($this->attribute('backtrace')));
        }
    }

    public static function definition()
    {
        return array(
            'fields'              => array(
                'id'               => array(
                    'name'     => 'ID',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'request_uri'      => array(
                    'name'     => 'RequestURI',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'request'          => array(
                    'name'     => 'Request',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'request_headers'  => array(
                    'name'     => 'RequestHeaders',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response_status'  => array(
                    'name'     => 'ResponseStatus',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => false
                ),
                'response_headers' => array(
                    'name'     => 'ResponseHeaders',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response_time'    => array(
                    'name'     => 'ResponseTime',
                    'datatype' => 'float',
                    'default'  => 0,
                    'required' => false
                ),
                'response'         => array(
                    'name'     => 'Response',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'response_error'   => array(
                    'name'     => 'ResponseError',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'date'             => array(
                    'name'     => 'Date',
                    'datatype' => 'integer',
                    'default'  => time(),
                    'required' => true
                ),
                'backtrace'        => array(
                    'name'     => 'Backtrace',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                )
            ),
            'function_attributes' => array(
                'backtrace_output' => 'getBacktraceOutput'
            ),
            'keys'                => array('id'),
            'sort'                => array('id' => 'desc'),
            'increment_key'       => 'id',
            'class_name'          => __CLASS__,
            'name'                => 'responsys_log'
        );
    }

    public function getBacktraceOutput()
    {
        return var_export($this->attribute('backtrace'), true);
    }

    public static function fetchList($conditions = null, $limitations = null, $custom_conds = null)
    {
        return eZPersistentObject::fetchObjectList(
                static::definition(), null, $conditions, null, $limitations, true, false, null, null, $custom_conds
        );
    }

    public static function countAll($conds = null, $fields = null)
    {
        return eZPersistentObject::count(static::definition(), $conds, $fields);
    }

    public function store($fieldFilters = null)
    {
        $this->setAttribute('backtrace', serialize($this->attribute('backtrace')));
        eZPersistentObject::storeObject($this, $fieldFilters);
        $this->setAttribute('backtrace', unserialize($this->attribute('backtrace')));
    }

    public static function getExpiryTime()
    {
        return (int) eZINI::instance('responsys.ini')->variable('General', 'LogsExpiryTime');
    }
}
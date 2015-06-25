<?php
/**
 * @package   Responsys
 * @class     Responsys
 * @author    Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date      08 Jun 2015
 * */
class Responsys
{
    private $serverURL          = 'https://api2-015.responsys.net/';
    private $loginURL           = 'https://login2.responsys.net/';
    private $timeout            = 15;
    private $authToken          = null;
    private $ini                = null;
    private $ignoreSSL          = true;
    private static $instance    = null;
    private static $lastLogItem = null;

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->ini = eZINI::instance('responsys.ini');

        $this->serverURL = $this->ini->variable('General', 'ServerURL');
        $this->loginURL  = $this->ini->variable('General', 'LoginURL');
        $this->timeout   = $this->ini->variable('General', 'Timeout');

        if ($this->authToken === null) {
            $this->authToken = $this->getAuthToken();
        }

        if ($this->authToken === null) {
            throw new Exception('Unable to fetch authToken.');
        }
    }

    /**
     * Singleton implementation
     * @return \Responsys
     */
    public static function instance()
    {
        if (self::$instance === null) {
            $class = get_called_class();

            self::$instance = new $class();
        }

        return self::$instance;
    }

    /**
     * Fetches authToken from the Responsys
     */
    private function getAuthToken()
    {
        $username = $this->ini->variable('Auth', 'Username');
        $password = $this->ini->variable('Auth', 'Passowrd');

        $uri = rtrim($this->loginURL, '/') . '/rest/api/v1/auth/token?user_name=' . $username . '&password=' . $password . '&auth_type=password';

        $result = $this->sendRequest($uri);
        return isset($result['authToken']) ? $result['authToken'] : null;
    }

    /**
     * Insert or update member data in contact list
     * @param string $email
     * @param string $customerID
     * @return array
     */
    public function mergeListMember($email, $customerID)
    {
        $contactListDB = $this->ini->variable('General', 'ContactListDatabase');

        $data = array(
            'list'       => array(
                'folderName' => 'MasterData'
            ),
            'recordData' => array(
                'fieldNames' => array('EMAIL_ADDRESS_', 'CUSTOMER_ID_', 'DATABASE_ID'),
                'records'    => array(
                    array('fieldValues' => array($email, $customerID, $contactListDB))
                )
            ),
            'mergeRule'  => array(
                'htmlValue'                  => 'H',
                'optinValue'                 => 'I',
                'textValue'                  => 'T',
                'insertOnNoMatch'            => true,
                'updateOnMatch'              => 'REPLACE_ALL',
                'matchColumnName1'           => 'CUSTOMER_ID_',
                'matchColumnName2'           => 'DATABASE_ID',
                'matchOperator'              => 'NONE',
                'optoutValue'                => 0,
                'rejectRecordIfChannelEmpty' => null,
                'defaultPermissionStatus'    => 'OPTIN'
            )
        );

        $uri = rtrim($this->serverURL, '/') . '/rest/api/v1/lists/CONTACTS_LIST';

        try {
            return $this->sendAuthorizedRequest($uri, $data);
        } catch (Exception $e) {
            throw new Exception('Unable to add member details to contacts list', null, $e);
        }
    }

    /**
     * Triggers custom event
     * @param string $event
     * @param string $email
     * @param string $customerID
     * @param array $optionalData
     * @return array
     */
    public function triggerCustomEvent($event, $email, $customerID, array $optionalData = array())
    {
        $this->mergeListMember($email, $customerID);

        $data = array(
            'customEvent'   => array(
                'eventNumberDataMapping' => null,
                'eventDateDataMapping'   => null,
                'eventStringDataMapping' => null
            ),
            'recipientData' => array(
                array(
                    'recipient'    => array(
                        'customerId'   => $customerID,
                        'emailAddress' => null,
                        //'emailAddress' => $email,
                        'listName'     => array(
                            'folderName' => 'MasterData',
                            'objectName' => 'CONTACTS_LIST'
                        ),
                        'recipientId'  => null,
                        'mobileNumber' => null,
                        'emailFormat'  => 'HTML_FORMAT'
                    ),
                    'optionalData' => $optionalData
                )
            )
        );

        $uri = rtrim($this->serverURL, '/') . '/rest/api/v1/events/API_' . $event;

        try {
            return $this->sendAuthorizedRequest($uri, $data);
        } catch (Exception $e) {
            throw new Exception('Unable to trigger custom event', null, $e);
        }
    }

    /**
     * Triggers custom event
     * @param string $event
     * @param array $receivers
     * @param array $optionalData
     * @return array
     */
    public function triggerCampaign($event, array $receivers, array $optionalData = array())
    {
        $contactListDB = $this->ini->variable('General', 'ContactListDatabase');

        $records     = array();
        $triggerData = array();
        foreach ($receivers as $email) {
            $records[]     = array('fieldValues' => array($email, $contactListDB));
            $triggerData[] = array('optionalData' => $optionalData);
        }

        $data = array(
            'recordData'  => array(
                'fieldNames' => array('EMAIL_ADDRESS_', 'DATABASE_ID'),
                'records'    => $records
            ),
            'mergeRule'   => array(
                'htmlValue'                  => 'H',
                'optinValue'                 => 'I',
                'textValue'                  => 'T',
                'insertOnNoMatch'            => true,
                'updateOnMatch'              => 'REPLACE_ALL',
                'matchColumnName1'           => 'EMAIL_ADDRESS_',
                'matchColumnName2'           => 'DATABASE_ID',
                'matchOperator'              => 'NONE',
                'optoutValue'                => 'O',
                'rejectRecordIfChannelEmpty' => null,
                'defaultPermissionStatus'    => 'OPTIN'
            ),
            'triggerData' => $triggerData
        );

        $uri = rtrim($this->serverURL, '/') . '/rest/api/v1/campaigns/API_' . $event . '/email';

        try {
            return $this->sendAuthorizedRequest($uri, $data);
        } catch (Exception $e) {
            throw new Exception('Unable to trigger campaign', null, $e);
        }
    }

    /**
     * Send HTTP request
     * @param string $uri
     * @param array $data
     * @param string $method
     * @param array $headers
     * @return array
     */
    private function sendAuthorizedRequest($uri, array $data = array(), $method = 'POST', array $headers = array())
    {
        $headers[] = 'Authorization: ' . $this->authToken;

        return $this->sendRequest($uri, $data, $method, $headers);
    }

    /**
     * Send HTTP request
     * @param string $uri
     * @param array $data
     * @param string $method
     * @param array $headers
     * @return array
     */
    private function sendRequest($uri, array $data = array(), $method = 'POST', array $headers = array())
    {
        $ch = curl_init($uri);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

        $defaultHeders = array(
            'content_type' => 'Content-Type: application/json',
            'user_agent'   => 'User-Agent: eZ Publish Responsys API'
        );
        $headers       = array_merge($defaultHeders, $headers);

        $dataJSON = json_encode($data);
        // Fix double slashes for unicode sequences
        $dataJSON = preg_replace('/\\\\\\\\u([0-9a-fA-F]{4})/u', '\u$1', $dataJSON);
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJSON);
                break;
            case 'PUT':
                curl_setopt($curl, CURLOPT_PUT, true);
                break;
        }

        if (count($headers) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($this->ignoreSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($ch);
        $info     = curl_getinfo($ch);
        $header   = trim(substr($response, 0, $info['header_size']));
        $body     = trim(substr($response, $info['header_size']));

        $log = new ResponsysLog();
        $log->setAttribute('request_uri', $uri);
        $log->setAttribute('request', self::json_pretty($dataJSON));
        $log->setAttribute('request_headers', implode("\n", $headers));
        $log->setAttribute('response_status', $info['http_code']);
        $log->setAttribute('response_headers', $header);
        $log->setAttribute('response_time', $info['total_time']);
        $log->setAttribute('response', $body);
        if (curl_error($ch)) {
            $log->setAttribute('response_error', curl_error($curl));
        }
        $log->store();

        self::$lastLogItem  = $log;

        curl_close($ch);

        $result = json_decode($response, true);
        if ($result === null) {
            $error = 'Invalid response from Responsys.';
            if (strlen($log->attribute('response_error')) === 0) {
                $log->setAttribute('response_error', $error);
                $log->store();
            }
            throw new Exception($error);
        }

        $error = null;
        if (isset($result['errorCode'])) {
            $error = isset($result['detail']) && empty($result['detail']) === false ? $result['detail'] : $result['title'];
        } elseif (isset($result[0]['errorMessage'])) {
            $error = $result[0]['errorMessage'];
        }

        if ($error !== null) {
            if (strlen($log->attribute('response_error')) === 0) {
                $log->setAttribute('response_error', $error);
                $log->store();
            }
            throw new Exception($error);
        }

        return $result;
    }

    public function getLastLogItem() {
        return self::$lastLogItem;
    }

    /**
     * Pretty-print JSON string
     *
     * Use 'format' option to select output format - currently html and txt supported, txt is default
     * Use 'indent' option to override the indentation string set in the format - by default for the 'txt' format it's a tab
     *
     * @param string $json Original JSON string
     * @param array $options Encoding options
     * @return string
     */
    protected static function json_pretty($json, $options = array())
    {
        $tokens = preg_split('|([\{\}\]\[,])|', $json, -1, PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        $indent = 0;

        $format = 'txt';

        //$ind = "\t";
        $ind = "    ";

        if (isset($options['format'])) {
            $format = $options['format'];
        }

        switch ($format) {
            case 'html':
                $lineBreak = '<br />';
                $ind       = '&nbsp;&nbsp;&nbsp;&nbsp;';
                break;
            default:
            case 'txt':
                $lineBreak = "\n";
                //$ind = "\t";
                $ind       = "    ";
                break;
        }

        // override the defined indent setting with the supplied option
        if (isset($options['indent'])) {
            $ind = $options['indent'];
        }

        $inLiteral = false;
        foreach ($tokens as $token) {
            if ($token == '') {
                continue;
            }

            $prefix = str_repeat($ind, $indent);
            if (!$inLiteral && ($token == '{' || $token == '[')) {
                $indent++;
                if (($result != '') && ($result[(strlen($result) - 1)] == $lineBreak)) {
                    $result .= $prefix;
                }
                $result .= $token . $lineBreak;
            } elseif (!$inLiteral && ($token == '}' || $token == ']')) {
                $indent--;
                $prefix = str_repeat($ind, $indent);
                $result .= $lineBreak . $prefix . $token;
            } elseif (!$inLiteral && $token == ',') {
                $result .= $token . $lineBreak;
            } else {
                $result .= ( $inLiteral ? '' : $prefix ) . $token;

                // Count # of unescaped double-quotes in token, subtract # of
                // escaped double-quotes and if the result is odd then we are
                // inside a string literal
                if ((substr_count($token, "\"") - substr_count($token, "\\\"")) % 2 != 0) {
                    $inLiteral = !$inLiteral;
                }
            }
        }
        return $result;
    }
}
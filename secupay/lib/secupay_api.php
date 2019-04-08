<?php
/**
 * secupay Payment Module.
 *
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 *
 * @category  Payment
 *
 * Description:
 *  Prestashop Plugin for integration of secupay AG payment services
 */

//define('SECUPAY_HOST', 'api-dist.secupay-ag.de');
//define('SECUPAY_URL', 'https://'.SECUPAY_HOST.'/payment/');
//define('SECUPAY_PATH', '/payment/');
//define('SECUPAY_PORT', 443);

/**
 *
 */
define(
    'SECUPAY_HOST',
    'api.secupay.ag'
);
/**
 *
 */
define(
    'SECUPAY_URL',
    'https://' . SECUPAY_HOST . '/payment/'
);
/**
 *
 */
define(
    'SECUPAY_PATH',
    '/payment/'
);
/**
 *
 */
define(
    'SECUPAY_PORT',
    443
);

// prevent deprecated date() function warning messages
ini_set(
    'date.timezone',
    'Europe/Berlin'
);
/**
 *
 */
define(
    'API_VERSION',
    '2.3'
);

/**
 * @param $Str
 *
 * @return bool
 */
function seems_utf8($Str)
{
    for ($i = 0; $i < strlen($Str); ++$i) {
        if (ord($Str[$i]) < 0x80) {
            continue;
        } // 0bbbbbbb
        elseif (0xC0 === (ord($Str[$i]) & 0xE0)) {
            $n = 1;
        } // 110bbbbb
        elseif (0xE0 === (ord($Str[$i]) & 0xF0)) {
            $n = 2;
        } // 1110bbbb
        elseif (0xF0 === (ord($Str[$i]) & 0xF8)) {
            $n = 3;
        } // 11110bbb
        elseif (0xF8 === (ord($Str[$i]) & 0xFC)) {
            $n = 4;
        } // 111110bb
        elseif (0xFC === (ord($Str[$i]) & 0xFE)) {
            $n = 5;
        } // 1111110b
        else {
            return false;
        } // Does not match any model

        for ($j = 0; $j < $n; ++$j) {
            // n bytes matching 10bbbbbb follow ?
            if ((++$i === strlen($Str)) || (0x80 !== (ord($Str[$i]) & 0xC0))) {
                return false;
            }
        }
    }

    return true;
}

/**
 * @param $data
 *
 * @return array|string
 */
function utf8_ensure($data)
{
    if (is_string($data)) {
        return seems_utf8($data) ? $data : utf8_encode($data);
    } elseif (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = utf8_ensure($value);
        }
        unset($value);
        unset($key);
    } elseif (is_object($data)) {
        foreach ($data as $key => $value) {
            $data->$key = utf8_ensure($value);
        }
        unset($value);
        unset($key);
    }

    return $data;
}

/**
 * Secupay logging function.
 *
 * @param bool true when we want to log
 */
function secupay_log($log)
{
    //      date_default_timezone_set("Europe/Berlin");
    static $logfile = 'cache/splog.php';
    if (!$log) {
        return;
    }
    $date = date('r');
    $x    = 0;
    foreach (func_get_args() as $val) {
        ++$x;
        if (1 === $x) {
            continue;
        }
        if (is_string($val) || is_numeric($val)) {
            file_put_contents(
                DIR_FS_CATALOG . $logfile,
                "[{$date}] {$val}\n",
                FILE_APPEND
            );
        } else {
            file_put_contents(
                DIR_FS_CATALOG . $logfile,
                "[{$date}] " . print_r(
                    $val,
                    true
                ) . "\n",
                FILE_APPEND
            );
        }
    }
}

/**
 * Class that handles SecupayApi requests and responses.
 */
class secupay_api
{
    /**
     * @var string
     */
    public $req_format;
    /**
     * @var
     */
    public $data;
    /**
     * @var string
     */
    public $req_function;
    /**
     * @var
     */
    public $error;
    /**
     * @var bool
     */
    public $sp_log;
    /**
     * @var string
     */
    public $language;

    /**
     * Constructor.
     *
     * @param array params
     * @param string $req_function
     * @param string $format
     * @param bool   $sp_log
     * @param string $language
     */
    public function __construct($params, $req_function = 'init', $format = 'application/json', $sp_log = false, $language = 'de_DE')
    {
        $this->req_function = $req_function;
        $this->req_format   = $format;
        $this->data         = $params;
        $this->sp_log       = $sp_log;
        $this->language     = $language;
    }

    /**
     * Function that creates request and sends it to Secupay.
     *
     * @return object
     */
    public function request()
    {
        $rc = null;
        if (function_exists('curl_init')) {
            $rc = $this->request_by_curl();
        } else {
            $rc = $this->request_by_socketstream();
        }

        return $rc;
    }

    /**
     * Function that creates request by curl.
     *
     * @return object response
     */
    public function request_by_curl()
    {
        $_data = json_encode(utf8_ensure($this->data));

        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_URL,
            SECUPAY_URL . $this->req_function
        );
        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );
        curl_setopt(
            $ch,
            CURLOPT_SSL_VERIFYPEER,
            false
        );
        curl_setopt(
            $ch,
            CURLOPT_CONNECTTIMEOUT,
            15
        );
        curl_setopt(
            $ch,
            CURLOPT_TIMEOUT,
            30
        );
        curl_setopt(
            $ch,
            CURLOPT_POST,
            true
        );
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $_data
        );
        // headers for APIv2
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Accept: ' . $this->req_format,
                'Content-Type: application/json',
                'User-Agent: OSC-client 1.0.0',
                'Accept-Language: ' . $this->language,
            )
        );
        secupay_log(
            $this->sp_log,
            'CURL request for ' . SECUPAY_URL . $this->req_function . ' in format : ' . $this->req_format
            . ' language: ' . $this->language
        );
        secupay_log(
            $this->sp_log,
            $_data
        );

        $rcvd = curl_exec($ch);
        secupay_log(
            $this->sp_log,
            'Response: ' . $rcvd
        );

        $this->sent_data  = json_encode($_data);
        $this->recvd_data = $rcvd;

        curl_close($ch);

        return $this->parse_answer($this->recvd_data);
    }

    /**
     * Function that parses answer from Secupay.
     *
     * @param $ret
     *
     * @return parsed object
     */
    public function parse_answer($ret)
    {
        switch (strtolower($this->req_format)) {
            case 'application/json':
                $answer = json_decode($ret);
                break;
            case 'text/xml':
                $answer = simplexml_load_string($ret);
                break;
        }

        return $answer;
    }

    /**
     * Function that creates request through fsockopen.
     *
     * @return bool
     */
    public function request_by_socketstream()
    {
        $_data = json_encode(utf8_ensure($this->data));

        $rcvd       = '';
        $rcv_buffer = '';
        $fp         = fsockopen(
            'ssl://' . SECUPAY_HOST,
            SECUPAY_PORT,
            $errstr,
            $errno
        );

        if (!$fp) {
            $this->error = "can't connect to secupay api";

            return false;
        } else {
            $req = 'POST ' . SECUPAY_PATH . $this->req_function . " HTTP/1.1\r\n";
            $req .= 'Host: ' . SECUPAY_HOST . "\r\n";
            $req .= "Content-type: application/json; Charset:UTF8\r\n";
            $req .= 'Accept: ' . $this->req_format . "\r\n";
            $req .= "User-Agent: OSC-client 1.0.0\r\n";
            $req .= 'Accept-Language: ' . $this->language . "\r\n";
            $req .= 'Content-Length: ' . strlen($_data) . "\r\n";
            $req .= "Connection: close\r\n\r\n";
            $req .= $_data;

            fputs(
                $fp,
                $req
            );
        }
        secupay_log(
            $this->sp_log,
            'SocketStream request for ' . SECUPAY_HOST . SECUPAY_PATH . $this->req_function . ' in format : '
            . $this->req_format . ' language: ' . $this->language
        );
        secupay_log(
            $this->sp_log,
            $_data
        );

        while (!feof($fp)) {
            $rcv_buffer = fgets(
                $fp,
                128
            );
            $rcvd       .= $rcv_buffer;
        }
        fclose($fp);

        secupay_log(
            $this->sp_log,
            'Response data:'
        );
        secupay_log(
            $this->sp_log,
            $rcvd
        );

        $pos  = strpos(
            $rcvd,
            "\r\n\r\n"
        );
        $rcvd = substr(
            $rcvd,
            $pos + 4
        );

        $this->sent_data  = $_data;
        $this->recvd_data = $rcvd;

        return $this->parse_answer($this->recvd_data);
    }
}

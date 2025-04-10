<?php
namespace block_obu_learnanalytics\guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class common
{
    protected static $la_ws_url = null;
    protected static $la_ws_token = null;
    protected static $la_ws_accept_sc = false;
    protected static $la_ws_trace = false;
    protected static $la_ws_curl_connect_timeout_ms = 500;  // Milliseconds
    protected static $la_ws_curl_exec_timeout_cc = 5;       // Seconds
    protected static $la_ws_curl_exec_timeout = 20;         // Seconds
    protected static $la_ws_cc = false;

    protected static $last_http_status = -1;
    protected static $last_curl_errno = -1;

    protected static $semester = '??????';

    public function __construct() 
    {
        self::$la_ws_url = \get_config('block_obu_learnanalytics', 'ws_root_url');
        if (substr_compare(self::$la_ws_url, '/', -1) != 0) {
            self::$la_ws_url .= '/';
        }
        global $USER;
        if (isset($USER->demomode) && $USER->demomode == "1") {
            self::$la_ws_token = \get_config('block_obu_learnanalytics', 'ws_bearer_token_demo');
        } else {
            self::$la_ws_token = \get_config('block_obu_learnanalytics', 'ws_bearer_token');
        }
        self::$la_ws_accept_sc = \get_config('block_obu_learnanalytics', 'ws_accept_selfcert');
        self::$la_ws_trace = \get_config('block_obu_learnanalytics', 'ws_trace_calls');

        $temp = \get_config('block_obu_learnanalytics', 'ws_curl_connecttimeout');
        if ($temp != false && $temp != "" && $temp != 0) {
            self::$la_ws_curl_connect_timeout_ms = $temp;
        }
        $temp = \get_config('block_obu_learnanalytics', 'ws_curl_timeout_cc');
        if ($temp != false && $temp != "" && $temp != 0) {
            self::$la_ws_curl_exec_timeout_cc = $temp;
        }
        $temp = \get_config('block_obu_learnanalytics', 'ws_curl_timeout');
        if ($temp != false && $temp != "" && $temp != 0) {
            self::$la_ws_curl_exec_timeout = $temp;
        }
    }

    public function send_request(string $params, string $method = 'GET', array $formParams = [])
    {

        $url = self::$la_ws_url . $params;
        $headers = [
            'Authorization' => 'Bearer ' . self::$la_ws_token,
            'Accept'  => 'application/json',
        ];
    
        // Timeouts/SSL
        $execTimeout       = self::$la_ws_cc ? self::$la_ws_curl_exec_timeout_cc : self::$la_ws_curl_exec_timeout;
        $connectTimeoutSec = self::$la_ws_curl_connect_timeout_ms / 1000.0;
        $verifySsl         = !(self::$la_ws_accept_sc == "1" || self::$la_ws_accept_sc === true);
    
        // Optional debug-file setup
        $debugFile = null;
        if (self::$la_ws_trace == "1") {
            global $CFG;
            $logfile   = $CFG->tempdir . '/obu_learnanalytics_wstraces.txt';
            $debugFile = @fopen($logfile, 'a');
        }
    
        // Create Guzzle client
            $client = new Client([
            'base_uri'        => rtrim(self::$la_ws_url, '/') . '/',
            'timeout'         => $execTimeout,
            'connect_timeout' => $connectTimeoutSec,
            'verify'          => $verifySsl,
            'allow_redirects' => ['max' => 20],
            'debug'           => $debugFile,
            'http_errors'     => false, // We manually check HTTP status
            'curl' => [
                CURLOPT_SSL_VERIFYHOST => 2,
            ],
        ]);
    
        try {
            // Build request options
            $options = [
                'headers' => $headers,
            ];
    
            // If it’s a POST, include form_params (or 'json' if the endpoint expects JSON)
            if (strtoupper($method) === 'POST') {
                // If your external endpoint expects standard POST form fields:
                $options['form_params'] = $formParams;
    
                // If your endpoint expects JSON in the request body, use:
                // $options['json'] = $formParams;
            }
    
            // Perform request
            $response = $client->request($method, ltrim($params, '/'), $options);

            self::$last_http_status = $response->getStatusCode();
    
            // Manually throw on HTTP >= 300
            if (self::$last_http_status >= 300) {
                throw new \Exception("Error calling web service HTTP Status=" . self::$last_http_status . ", params={$params}");
            }
    
            // Return JSON-decoded response
            $body = (string)$response->getBody();
            error_log("Raw response body: " . (string)$response->getBody());

            return json_decode($body, true);
    
        } catch (RequestException $ex) {
            // If Guzzle had trouble, store status or 0 if none
            self::$last_http_status = $ex->hasResponse()
                ? $ex->getResponse()->getStatusCode()
                : 0;
            self::$last_curl_errno = $ex->getCode();
    
            throw new \Exception("Guzzle request exception: " . $ex->getMessage());
        } finally {
            // Close debug file if opened
            if (is_resource($debugFile)) {
                fclose($debugFile);
            }
        }
    }

    public function setCheckConnection(bool $value = true)
    {
        self::$la_ws_cc = $value;
    }



    public function echo_error_console_log($ex, $echo = true)
    {
        $caller = "";
        $from = qualified_me();
        if ($from != null || $from != "") {
            $lastSlash = \strrpos($from, '/', -1);
            if ($lastSlash === false) {
                $caller = $from;
            } else {
                $caller = substr($from, $lastSlash + 1);
            }
        }
        if ($caller == "") {
            $temp = 'console.info("curl Exception details for console log from " . $caller);';
        } else {
            $temp = 'console.info("curl Exception details for console log");';
        }
        $temp .= 'console.log(' . json_encode($ex->getMessage()) . ');';
        $temp .= 'console.log(' . json_encode($ex->getFile()) . ');';
        $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
        $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
        if ($echo) {
            // Echo as an array - javascript is expecting that
            ob_start();     // to solve problems when something already sent
            header('Content-type: application/json');
            echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
        } else {
            return $consolehtml;
        }
    }

    // (Optional) Copy get_last_http_status(), get_status_details(), etc.
    // from the old class if you need them exactly the same in Guzzle.
}

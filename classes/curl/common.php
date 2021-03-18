<?php
// This file is for use with Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
// This file is covered by the same agreement

/**
 * Learning Analytics data for Oxford Brookes University Students and Tutors
 *
 * @package   block_obu_learnanalytics
 * @copyright 2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_obu_learnanalytics\curl;

class common
{
    protected static $la_ws_url = null;
    protected static $la_ws_token = null;
    protected static $la_ws_accept_sc = false;
    protected static $la_ws_trace = false;
    protected static $la_ws_curl_timeout_cc = 5;
    protected static $la_ws_curl_timeout = 20;
    protected static $la_ws_cc = false;

    public function __construct()
    {
        self::$la_ws_url = \get_config('block_obu_learnanalytics', 'ws_root_url');
        if (substr_compare(self::$la_ws_url, '/', -1) != 0) {
            self::$la_ws_url .= '/';
        }
        self::$la_ws_token = \get_config('block_obu_learnanalytics', 'ws_bearer_token');
        self::$la_ws_accept_sc = \get_config('block_obu_learnanalytics', 'ws_accept_selfcert');
        self::$la_ws_trace = \get_config('block_obu_learnanalytics', 'ws_trace_calls');
        $temp = \get_config('block_obu_learnanalytics', 'ws_curl_timeout_cc');
        if ($temp != false && $temp != "" && $temp != 0) {
            self::$la_ws_curl_timeout_cc = $temp;
        }
        $temp = \get_config('block_obu_learnanalytics', 'ws_curl_timeout');
        if ($temp != false && $temp != "" && $temp != 0) {
            self::$la_ws_curl_timeout = $temp;
        }
    }

    /*public function __destruct()
    {
    }
    */
    
    public function setCheckConnection(bool $value = true)
    {
        self::$la_ws_cc = $value;
    }

    /**
     * send_request
     * Call the la web services (from config) with parameters
     *
     * @param  string $params
     * @return string Json decoded string
     */
    public function send_request(string $params)
    {
        // Set it up
        $curl = curl_init();
        
        // Build URL - do we need http_build_query??
        $url = self::$la_ws_url . $params;
        // Decide which bearer token
        //global $USER;
        //if (isset($USER->demomode) && $USER->demomode == "1") {
            //
        //}
        $auth = "Authorization: Bearer " . self::$la_ws_token;
        $timeout = (self::$la_ws_cc == true) ? self::$la_ws_curl_timeout_cc : self::$la_ws_curl_timeout;

        // Set options
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $auth ));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 2);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 20);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);   // Seconds
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);      // 2 is default and does the check
        if (self::$la_ws_accept_sc == "1" || self::$la_ws_accept_sc == true) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }

        global $CFG;
        $debugFile = (self::$la_ws_trace == "1") ? fopen($CFG->tempdir . '\obu_learnanalytics_wstraces.txt', 'a') : null;
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        if ($debugFile != null) {
            curl_setopt($curl, CURLOPT_STDERR, $debugFile);
        }

        $result = curl_exec($curl);
        $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_errno = curl_errno($curl);
        $error_msg = '';

        if ($debugFile != null) {
            \fwrite($debugFile, 'WS HTTP status:' . $http_status . PHP_EOL);
            \fwrite($debugFile, 'WS Curl err no:' . $curl_errno . PHP_EOL);
            if ($curl_errno) {
                $error_msg = curl_error($curl);
                \fwrite($debugFile, 'WS Curl errmsg:' . $error_msg . PHP_EOL);
            }
        }
        
        curl_close($curl);      //TODO experimenting with keeping a single curl handle open
        $curl = null;

        if ($debugFile != null) {
            \fwrite($debugFile, 'WS from function ');   //. \xdebug_call_function(2));
            \fwrite($debugFile, PHP_EOL);
            \fwrite($debugFile, $result);
            \fwrite($debugFile, PHP_EOL);
            //\fwrite($debugFile, 'WS End OK: Rows = ' . count($result) . ' Time = ' . + ($end - $start));
            \fwrite($debugFile, 'Result type:' . \gettype($result));
            \fwrite($debugFile, PHP_EOL);
            \fwrite($debugFile, 'Json decoded type:' . \gettype(json_decode($result, true)));
            \fwrite($debugFile, PHP_EOL);
        }

        if ($debugFile != null) {
            \fclose($debugFile);
        }

        if ($http_status > 300) {
            // By throwing it we get the call stack in the exception
            throw new \Exception("Error calling web service HTTP Status = {$http_status}, params = {$params}");
        }

        return json_decode($result, true);
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
    
    /**
     * get_academic_advisor
     * Wrapper for calling Web service and adding extra data
     * only one needed so far, if we need more then we'll create a data class
     *
     * @param  string $userName
     * @return array  PNumber/Name/userid
     */
    public function get_academic_advisor(string $userName)
    {
        $params = "student/advisor/$userName/";
        $rows = $this->send_request($params);
        if ($rows == null) {
            return null;
        }
        $row = $rows[0];
        $pnumber = $row['PNumber'];
        $pname = $row['PNumber'];
        $userid = -1;
        global $DB;
        if ($DB == null) {
            $pname = "null DB Object";
        }
        $userObj = $DB->get_record("user", array('username' => $pnumber));
        if ($userObj != null && $userObj != false) {
            //$pname = $userObj->firstname . ' ' . $userObj->lastname;
            $userid = $userObj->id;
        } // If we don't find it then the pnumber will go back as the name
        return array('PNumber' => $pnumber, 'Name' => $pname, 'userid' => $userid);
    }
// End of class
}

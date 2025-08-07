<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
$PAGE->set_url('/blocks/obu_learnanalytics/students_graph_v2.php');
header('Content-Type: application/json; charset=UTF-8');

try {
    // Load composer/autoload
    require_once(__DIR__ . '/vendor/autoload.php');

    // Initialize utilities
    $util_odds = new \block_obu_learnanalytics\util\odds();
    $laRole    = $util_odds->get_la_role();

    // Override Moodle error handler
    // $old_error_handler = set_error_handler('errorHandlerOBU');
    $resultSent = false;

    $util_dates = new \block_obu_learnanalytics\util\date_functions();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Gather POST parameters
        $chartType   = $_POST['chartType']   ?? '';
        $currentWeek = $_POST['currentWeek'] ?? '';
        if ($currentWeek === '' || $currentWeek === null) {
            $current = $util_dates->get_current_week();
        } else {
            $current = $util_dates->json_2_current_week($currentWeek);
        }
        $sid         = $_POST['studentNumber'] ?? '';
        $sname       = $_POST['studentName']   ?? '';
        $programme   = $_POST['programme']     ?? '';
        $bandingCalc = $_POST['bandingCalc']   ?? 'MED???';
        $studyStage  = $_POST['sStage']        ?? '*';
    } else {
        exit(json_encode([ 'success' => false, 'error' => 'GET not supported for student graph' ]));
    }

    if ($programme === '') {
        exit(json_encode([ 'success' => false, 'error' => 'programme not passed' ]));
    }

    if ($laRole === 'STUDENT' && $sid !== $USER->username) {
        exit(json_encode([ 'success' => false, 'error' => 'permission denied' ]));
    }

    if ($chartType !== 'none') {
        $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
        $sStage        = $studyStage;

        switch ($chartType) {
            case 'ezsessions':
                $column = 'ez_sessions';
                break;
            case 'ezduration':
                $column = 'ez_duration_total';
                break;
            case 'ezsize':
                $column = 'ez_size';
                break;
            case 'vlesessions':
                $column = 'vle_sessions';
                break;
            case 'vleduration':
                $column = 'vle_duration_minutes';
                break;
            case 'vleviews':
                $column = 'vle_page_hits';
                break;
            case 'loansline':
            case 'loansbar':
            case 'loanscomb':
                $column = 'library_resources_loaned';
                break;
            case 'attperc':
                $column = 'attendance_percentage';
                break;
            case 'attsessions':
                $column = 'attendance_sessions';
                break;
            case 'attduration':
                $column = 'attendance_duration_total';
                break;
            default:
                $column = '';
                break;
        }

        $enc_pgm = htmlspecialchars(urlencode(str_replace('/', '~', $programme)));
        $params  = "student/pgmgraphdata/{$sid}/{$enc_pgm}/{$sStage}/{$simpleCurrent}/median/{$column}/";

        try {
            $curl_common       = new \block_obu_learnanalytics\guzzle\common();
            $studentGraphData = $curl_common->send_request($params);
            echo json_encode($studentGraphData);
        } catch (\Exception $ex) {
            // Restore previous error handler
            if (isset($old_error_handler)) {
                set_error_handler($old_error_handler);
                $old_error_handler = null;
            }
            exit(json_encode([ 'success' => false, 'error' => $ex->getMessage() ]));
        }
    } else {
        echo json_encode([]);
    }

} catch (\ErrorException $ex) {
    $temp       = 'console.info("ErrorException details for console log");';
    $temp      .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp      .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp      .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = sprintf('<div style="display:none"><script type="text/javascript">%s</script></div>', $temp);
    exit(json_encode([ 'success' => false, 'consolehtml' => $consolehtml ]));

} catch (\Exception $ex) {
    $temp       = 'console.info("Exception details for console log");';
    $temp      .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp      .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp      .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = sprintf('<div style="display:none"><script type="text/javascript">%s</script></div>', $temp);
    exit(json_encode([ 'success' => false, 'consolehtml' => $consolehtml ]));
}
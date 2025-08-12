<?php
define('AJAX_SCRIPT', true);

ob_start();

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/vendor/autoload.php';

$util_odds = new \block_obu_learnanalytics\util\odds();
if ($util_odds->get_la_role() === 'STUDENT') {
    die('Permission Denied');
}


function attendance_matrix(array $tree): array
{
    $matrix = [];

    foreach ($tree as $stuNo => $stu) {
        $stuName = $stu['student_name'] ?? 'Unknown';
        $personBlock = [];
        $programmeBlock = [];

        foreach ($stu as $semKey => $semVal) {
            // skip non-semester fields present in the payload
            if (in_array($semKey, ['student_person_id', 'student_name'], true)) {
                continue;
            }

            // only process entries that look like a semester block (must have weeks)
            if (!is_array($semVal) || !isset($semVal['weeks']) || !is_array($semVal['weeks'])) {
                continue;
            }

            // reset per semester
            $weekBlock = [];

            $programmeName = $semVal['programme'] ?? 'Unknown programme';

            foreach ($semVal['weeks'] as $weekNo => $weekVal) {
                $label = "Week {$weekNo}";
                $attended = 0; $non = 0; $modsMissed = [];

                foreach ($weekVal as $modId => $modVal) {
                    $sessions = $modVal['sessions'] ?? [];
                    if (!is_array($sessions)) {
                        $sessions = [];
                    }

                    $missed = 0;
                    foreach ($sessions as $s) {
                        $missed += (!empty($s['non_attended']) ? 1 : 0);
                    }
                    $total  = count($sessions);
                    $attended += max(0, $total - $missed);
                    $non     += $missed;

                    if ($missed) {
                        $modsMissed[$modId] = "{$missed}/{$total}";
                    }
                }

                $totSessions = $attended + $non;
                $pct = $totSessions ? round($attended / $totSessions * 100) : 0;

                $weekBlock[$label] = [
                    'attendance_percent' => (string)$pct,
                    'modules_missed'     => $modsMissed
                ];
            }

            $programmeBlock[$programmeName] = $weekBlock;
        }

        $personBlock[$stuName] = $programmeBlock;
        $matrix[$stuNo] = $personBlock;
    }

    return $matrix;
}

// Our formatter for getting the data in an easy to use structure for the Matrix Details expanding section
function attendance_matrix_details(array $tree): array
{
    $details = [];

    foreach ($tree as $stuNo => $stu) {
        $studentName = $stu['student_name'] ?? 'Unknown';
        $personId    = $stu['student_person_id'] ?? null;

        // find the first semester block that actually has weeks
        $semesterBlocks = array_filter($stu, function($v, $k) {
            if (in_array($k, ['student_person_id', 'student_name'], true)) return false;
            return is_array($v) && isset($v['weeks']) && is_array($v['weeks']);
        }, ARRAY_FILTER_USE_BOTH);

        if (!$semesterBlocks) {
            // still provide a stub so the UI doesn't break
            $details[$stuNo] = [
                'student_number' => (string)$stuNo,
                'person_id'      => $personId,
                'name'           => $studentName,
                'programme'      => 'Unknown programme',
                'weeks'          => [],
                'aggregate'      => ['by_module' => [], 'by_day' => []],
            ];
            continue;
        }

        // If there are multiple semester keys, pick the latest by key name
        // (adjust if you want deterministic choice another way)
        krsort($semesterBlocks);
        $sem = reset($semesterBlocks);

        $programme = $sem['programme'] ?? 'Unknown programme';
        $weeksIn   = $sem['weeks'] ?? [];

        $weeksOut = [];                   // weekNo => ['modules' => [modId => counts...], 'by_day' => [...]]
        $aggByMod = [];                   // modId => ['attended','missed','total','module_name']
        $aggByDay = [];                   // 'Mon'..'Sun' => counts

        foreach ($weeksIn as $weekNo => $weekVal) {
            if (!is_array($weekVal)) continue;

            $modulesOut = [];
            $byDayOut   = [];

            foreach ($weekVal as $modId => $modVal) {
                if (!is_array($modVal)) $modVal = [];
                $moduleName = $modVal['module_name'] ?? $modId;

                $sessions = $modVal['sessions'] ?? [];
                if (!is_array($sessions)) $sessions = [];

                $missed = 0; $total = 0; $attended = 0;

                foreach ($sessions as $s) {
                    $total++;
                    $sa = !empty($s['attended']) ? 1 : 0;
                    $sn = !empty($s['non_attended']) ? 1 : 0;
                    if ($sn) $missed++;
                    elseif ($sa) $attended++;

                    // day-of-week bucket
                    $dow = 'Unknown';
                    if (!empty($s['session_date'])) {
                        try { $dow = (new DateTime($s['session_date']))->format('D'); } catch (\Throwable $e) { $dow = 'Unknown'; }
                    }
                    if (!isset($byDayOut[$dow])) $byDayOut[$dow] = ['attended'=>0,'missed'=>0,'total'=>0];
                    $byDayOut[$dow]['total']++;
                    $byDayOut[$dow]['attended'] += $sa ? 1 : 0;
                    $byDayOut[$dow]['missed']   += $sn ? 1 : 0;
                }

                if ($attended + $missed > $total) $total = $attended + $missed;

                // Always output the module, even if total==0
                $modulesOut[$modId] = [
                    'module_id'   => $modId,
                    'module_name' => $moduleName,
                    'attended'    => $attended,
                    'missed'      => $missed,
                    'total'       => $total,
                ];

                // Aggregate by module (ensure presence even for zero-session modules)
                if (!isset($aggByMod[$modId])) {
                    $aggByMod[$modId] = ['module_id'=>$modId,'module_name'=>$moduleName,'attended'=>0,'missed'=>0,'total'=>0];
                }
                $aggByMod[$modId]['attended'] += $attended;
                $aggByMod[$modId]['missed']   += $missed;
                $aggByMod[$modId]['total']    += $total;
            }


            $weeksOut["Week {$weekNo}"] = [
                'modules' => $modulesOut,
                'by_day'  => $byDayOut,
            ];

            // roll week day buckets into global aggregate
            foreach ($byDayOut as $dow => $c) {
                if (!isset($aggByDay[$dow])) $aggByDay[$dow] = ['attended' => 0, 'missed' => 0, 'total' => 0];
                $aggByDay[$dow]['attended'] += $c['attended'];
                $aggByDay[$dow]['missed']   += $c['missed'];
                $aggByDay[$dow]['total']    += $c['total'];
            }
        }

        // sort aggregates for stable output
        ksort($weeksOut);                       // Week 1, Week 2, ...
        ksort($aggByMod);                       // by module id
        // normalize day order Mon..Sun..Unknown
        $dowOrder = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun','Unknown'];
        $aggByDay = array_replace(array_flip($dowOrder), $aggByDay); // ensure keys exist
        foreach ($aggByDay as $k => &$v) if ($v === $k) $v = ['attended'=>0,'missed'=>0,'total'=>0];
        $aggByDay = array_intersect_key($aggByDay, array_flip($dowOrder)); // keep order

        $details[$stuNo] = [
            'student_number' => (string)$stuNo,
            'person_id'      => $personId,
            'name'           => $studentName,
            'programme'      => $programme,
            'weeks'          => $weeksOut,                       // per-week detail
            'aggregate'      => [
                'by_module' => array_values($aggByMod),          // list of {module_id,module_name,attended,missed,total}
                'by_day'    => $aggByDay,                        // map DOW => counts
            ],
        ];
    }

    return $details;
}



$util_dates = new \block_obu_learnanalytics\util\date_functions();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Brookes Learning Analytics - GET not supported for tutor grid');
}

$semester    = $_POST['semester']     ?? '';

global $USER;
$today = new DateTime();
set_user_preference('obula_last_tutor_grid_date', serialize($today));


$success = true;
try {
    $params      = "tutor/adviseesmatrix/$semester/$USER->username/";
    $curl_common = new \block_obu_learnanalytics\guzzle\common();
    $results     = $curl_common->send_request($params); 
} catch (Exception $e) {
    $html    = "<p style='color:red;font-size:150%'>Exception: {$e}</p>";
    $success = false;
}



if ($success && empty($results)) {
    $html    = "<p style='color:blue;font-size:120%'>No Active Students {$semester}</p>";
    $success = false;
}

if ($success) {
    $matrix = attendance_matrix($results);        // JS “plug‑and‑play” data
    $matrixdetails = attendance_matrix_details($results);
    $count  = count($results);
} else {
    $matrix = [];
    $count  = 0;
}

header('Content-Type: application/json');
echo json_encode([
    'success'        => $success,
    'semester'       => $semester,
    'data'           => $matrix,     // ← send to buildAttendanceMatrix()
    'matrixdetails'  => $matrixdetails,
    'rawdata'        => $results,
    'students_count' => $count
]);
exit;

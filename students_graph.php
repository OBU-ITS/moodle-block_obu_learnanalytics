<?php ob_start();?><?php
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png

//Too early, config resets - $old_error_handler = set_error_handler("errorHandlerOBU");     // Not self:: as this isn't a class

try {
    require_once './jpgraph/src/jpgraph.php';
    require_once './jpgraph/src/jpgraph_line.php';
    require_once './jpgraph/src/jpgraph_bar.php';
    $jpgraph_error_handler = set_error_handler("errorHandlerOBU"); // jpgraph sets it's own

    //define('AJAX_SCRIPT', true);      // This breaks things
    require_once __DIR__ . '/../../config.php';
    // Now load up a general purpose class
    $util_odds = new \block_obu_learnanalytics\util\odds();
    $laRole = $util_odds->get_la_role();    // Protects against attacks, wrong roles and everything

    // config.php sets an error handler, so override here
    $old_error_handler = set_error_handler("errorHandlerOBU");     // Not self:: as this isn't a class
    $resultSent = false;        // Good or bad

    $util_dates = new \block_obu_learnanalytics\util\date_functions();

    $posted = true;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // The request is using the POST method
        $chartType = $_POST["chartType"];
        $currentWeek = $_POST["currentWeek"] ?? "";
        if ($currentWeek == "" || $currentWeek == null) {
            $current = $util_dates->get_current_week();
        } else {
            $current = $util_dates->json_2_current_week($currentWeek);
        }
        $sid = $_POST["studentNumber"] ?? ""; //TODO error handling if no student id
        $sname = $_POST["studentName"] ?? "";
        $programme = $_POST["programme"];
        $bandingCalc = $_POST["bandingCalc"] ?? "MED???";
        $studyStage = $_POST["sStage"] ?? "*";
    } else {
        exit("Brookes Learning Analytics - GET not supported for student graph");
    }

    if ($programme == "") {
        exit("Brookes Learning Analytics - programme not passed");
    }

    // OK So now we shouldn't let a non tutor to anyone else's data
    if ($laRole == "STUDENT" && $sid != $USER->username) {
        die("Permission to others students data denied");
    }

    if ($chartType != 'none') { // Will be none if not visible
        $simpleCurrent = $util_dates->createSimpleCurrentParam($current);
        $sStage = $studyStage;
        switch ($chartType) {
            case "ezsessions":
                $column = "ez_sessions";
                break;
            case "ezduration":
                $column = "ez_duration_total";
                break;
            case "ezsize":
                $column = "ez_size";
                break;
            case "vlesessions":
                $column = "vle_sessions";
                break;
            case "vleduration":
                $column = "vle_duration_total";
                break;
            case "vleviews":
                $column = "vle_page_views";
                break;
            case "loansline":
            case "loansbar":
            case "loanscomb":
                $column = "library_resources_loaned";
                break;
            case "attsessions":
                $column = "attendance_sessions";
                break;
            case "attduration":
                $column = "attendance_duration_total";
                break;
        }
        // Note exception isn't caught if next line fails
        $curl_common = new \block_obu_learnanalytics\curl\common();
        $params = "student/pgmgraphdata/$sid/$programme/{$sStage}/$simpleCurrent/both/{$column}/";
        try {
            $studentGraphData = $curl_common->send_request($params);
        } catch (\Exception $ex) {
            $curl_common->echo_error_console_log($ex);
            $resultSent = true;
            //throw $ex; No that stops the message getting to the console
            if (isset($old_error_handler)) {
                set_error_handler($old_error_handler);      // Not sure I need this as I go out of scope
                $old_error_handler = null;
            }
            exit;
        }

        // Now process the results
        $weeks = [];
        $plot1Counts = [];
        $plot2Counts = [];

        if ($studentGraphData != null) {
            foreach ($studentGraphData as $key => $row) {
            // echo "<pre>";
            // print_r($row);
            // echo "</pre>";

                $weeks[] = $row["first_day_week"];
                switch ($chartType) {
                    case "ezsessions":
                        $plot1Counts[] = $row["ez_sessions"] ?? 0;
                        $plot2Counts[] = $row["mean_ez_sessions"] ?? 0;
                        $plot3Counts[] = $row["median_ez_sessions"] ?? 0;
                        break;
                    case "ezduration":
                        $plot1Counts[] = $row["ez_duration_total"] ?? 0;
                        $plot2Counts[] = $row["mean_ez_duration_total"] ?? 0;
                        $plot3Counts[] = $row["median_ez_duration_total"] ?? 0;
                        break;
                    case "ezsize":
                        $plot1Counts[] = $row["ez_size"] ?? 0;
                        $plot2Counts[] = $row["mean_ez_size"] ?? 0;
                        $plot3Counts[] = $row["median_ez_size"] ?? 0;
                        break;
                    case "vlesessions":
                        $plot1Counts[] = $row["vle_sessions"] ?? 0;
                        $plot2Counts[] = $row["mean_vle_sessions"] ?? 0;
                        $plot3Counts[] = $row["median_vle_sessions"] ?? 0;
                        break;
                    case "vleduration":
                        $plot1Counts[] = $row["vle_duration_total"] ?? 0;
                        $plot2Counts[] = $row["mean_vle_duration_total"] ?? 0;
                        $plot3Counts[] = $row["median_vle_duration_total"] ?? 0;
                        break;
                    case "vleviews":
                        $plot1Counts[] = $row["vle_page_views"] ?? 0;
                        $plot2Counts[] = $row["mean_vle_page_views"] ?? 0;
                        $plot3Counts[] = $row["median_vle_page_views"] ?? 0;
                        break;
                    case "loansline":
                    case "loansbar":
                    case "loanscomb":
                        $plot1Counts[] = $row["library_resources_loaned"] ?? 0;
                        $plot2Counts[] = $row["mean_library_resources_loaned"] ?? 0;
                        $plot3Counts[] = $row["median_library_resources_loaned"] ?? 0;
                        break;
                    case "attsessions":
                        $plot1Counts[] = $row["attendance_sessions"] ?? 0;
                        $plot2Counts[] = $row["mean_attendance_sessions"] ?? 0;
                        $plot3Counts[] = $row["median_attendance_sessions"] ?? 0;
                        break;
                    case "attduration":
                        $plot1Counts[] = $row["attendance_duration_total"] ?? 0;
                        $plot2Counts[] = $row["mean_attendance_duration_total"] ?? 0;
                        $plot3Counts[] = $row["median_attendance_duration_total"] ?? 0;
                        break;
                }
            }
        }
        

        /*
        echo "<pre>";
        print_r($weeks);
        print_r($sessions);
        echo "</pre>";
        */

        // Width and height of the graph
        $width = 800;
        $height = 320;

        // Setup a title for the graph
        $graphTitle = "????";
        $plotType = "Line";
        switch ($chartType) {
            case "ezsessions":
                $graphTitle = "Electronic Library Engagement - Number of Visits";
                break;
            case "ezduration":
                $graphTitle = "Electronic Library Engagement - Duration (Minutes)";
                break;
            case "ezsize":
                $graphTitle = "Electronic Library Engagement - Downloads (MB)";
                break;
            case "vlesessions":
                $graphTitle = "Moodle Engagement - Number of Visits";
                break;
            case "vleduration":
                $graphTitle = "Moodle Engagement - Duration (Minutes)";
                break;
            case "vleviews":
                $graphTitle = "Moodle Engagement - Page Views";
                break;
            case "loansline":
            case "loansbar":
            case "loanscomb":
                $graphTitle = "Campus Library Engagement - Loans";
                $plotType = substr($chartType, 5);
                break;
            case "attsessions":
                $graphTitle = "Attendance - Number of Lectures";
                break;
            case "attduration":
                $graphTitle = "Attendance - Time in Lectures (Minutes)";
                break;
        }

        // Create a graph instance
        $graph = new Graph($width, $height, "auto");

        switch ($plotType) {
            case 'bar':
                $graph->img->SetMargin(60, 5, 5, 140); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale

                // Specify what scale we want to use,
                // text = text scale for the X-axis
                // int = integer scale for the Y-axis
                $graph->SetScale('textint');

                $graph->title->Set($graphTitle);

                // Setup titles and X-axis labels
                // not important and clashes with axis labels $graph->xaxis->title->Set('Week');
                $graph->xaxis->SetLabelAngle(50);
                $graph->xaxis->SetTickLabels($weeks);

                // Setup Y-axis title
                // not needed and clashes with labels$graph->yaxis->title->Set($chartType);
                $graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis
                // Create the first plot
                if (count($plot1Counts) > 0) {
                    $p1 = new BarPlot($plot1Counts);
                    $p1->SetWeight(22); // Doesn't seem to do anything
                    $p1->SetColor("blue");
                    $legend = (($sname == '') ? "Yours" : $sname);
                    $p1->SetLegend($legend); // Appears over months
                }

                // Create the Average plots
                if (count($plot2Counts) > 0) {
                    $p2 = new BarPlot($plot2Counts);
                    $p2->SetWeight(1);
                    $p2->SetColor("orange");
                    $p2->SetLegend("Study Stage {$sStage} Mean Average");
                }
                if (count($plot3Counts) > 0) {
                    $p3 = new BarPlot($plot3Counts);
                    $p3->SetWeight(1);
                    $p3->SetColor("orange");
                    $p3->SetLegend("Study Stage {$sStage} Median Average");
                }

                if (count($plot1Counts) > 0) {
                    $gbplot = new GroupBarPlot(array($p3, $p2, $p1));
                    $graph->Add($gbplot);
                }
                break;

            case 'comb':
                $graph->img->SetMargin(60, 5, 5, 140); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale

                // Specify what scale we want to use,
                // text = text scale for the X-axis
                // int = integer scale for the Y-axis
                $graph->SetScale('textint');

                $graph->title->Set($graphTitle);

                // Setup titles and X-axis labels
                // not important and clashes with axis labels $graph->xaxis->title->Set('Week');
                $graph->xaxis->SetLabelAngle(50);
                $graph->xaxis->SetTickLabels($weeks);

                // Setup Y-axis title
                // not needed and clashes with labels$graph->yaxis->title->Set($chartType);
                $graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis
                // Create the first plot
                if (count($plot1Counts) > 0) {
                    $p1 = new BarPlot($plot1Counts);
                    $p1->SetWeight(22); // Doesn't seem to do anything
                    $p1->SetColor("blue");
                    $legend = (($sname == '') ? "Yours" : $sname);
                    $p1->SetLegend($legend); // Appears over months
                }

                // Create the Average plots
                if (count($plot2Counts) > 0) {
                    $p2 = new BarPlot($plot2Counts);
                    $p2->SetWeight(1);
                    $p2->SetColor("orange");
                    $p2->SetLegend("Study Stage {$sStage} Mean Average");
                }
                if (count($plot3Counts) > 0) {
                    $p3 = new BarPlot($plot3Counts);
                    $p3->SetWeight(1);
                    $p3->SetColor("green");
                    $p3->SetLegend("Study Stage {$sStage} Median Average");
                }

                if (count($plot1Counts) > 0) {
                    $gbplot = new GroupBarPlot(array($p3, $p2, $p1));
                    $graph->Add($gbplot);
                }
                break;

            default:
                $graph->img->SetMargin(80, 5, 5, 140); // 1st margin is left, Last margin is bottom so stops plot legend overwriting scale

                // Specify what scale we want to use,
                // text = text scale for the X-axis
                // int = integer scale for the Y-axis
                $graph->SetScale('textint');

                $graph->title->Set($graphTitle);

                // Setup titles and X-axis labels
                // not important and clashes with axis labels $graph->xaxis->title->Set('Week');
                $graph->xaxis->SetLabelAngle(50);
                $graph->xaxis->SetTickLabels($weeks);

                // Setup Y-axis title
                // not needed and clashes with labels$graph->yaxis->title->Set($chartType);
                $graph->yscale->ticks->SupressZeroLabel(true); // Don't Show 0 label on Y-axis
                // Create the first plot
                if (count($plot1Counts) > 0) {
                    $p1 = new LinePlot($plot1Counts);
                    $legend = (($sname == '') ? "Yours" : $sname);
                    $p1->SetLegend($legend); // Appears over months
                    $graph->Add($p1);
                    // Have to set color after add or it's ignored
                    //$p1->SetWeight(22);     // Doesn't seem to do anything
                    $p1->SetColor("darkblue");
                }

                // Create the Average plots
                if (count($plot2Counts) > 0) {
                    $p2 = new LinePlot($plot2Counts);
                    $p2->SetLegend("Study Stage {$sStage} Mean Average");
                    $graph->Add($p2);
                    //$p2->SetWeight(1);
                    //$p2->SetColor("orange");
                    //$p2->SetColor("#fc9d03");
                    $p2->SetColor('darkred');
                }
                if (count($plot3Counts) > 0) {
                    $p3 = new LinePlot($plot3Counts);
                    $p3->SetLegend("Study Stage {$sStage} Median Average");
                    $graph->Add($p3);
                    $p3->SetColor('darkgreen');
                }
                break;
        }

        // Return the graph, as it was posted we need to encode the returned image
        // So prepare the image
        $gdImgHandler = $graph->Stroke(_IMG_HANDLER);
        // Use the output buffer and imagepng
        ob_start();
        imagepng($gdImgHandler); // Write to OB, I know this works because if I save it to a file it's OK
        //        $graph->img->Stream();            // Output from this looks the same to the naked eye as imagepng
        $image_data = ob_get_contents(); // grab the contents of the buffer
        ob_end_clean(); // Empty the buffer as we have what we want
        // Now encode and return it
        $contentType = 'image/png';
        echo "data:$contentType;base64," . base64_encode($image_data);
        $resultSent = true;
        // Tried as an array, but was corrupted
        //header('Content-type: application/json');
        //echo json_encode(array('success' => true, 'graphImage' => base64_encode($image_data)));
    }
} catch (\ErrorException $ex) {
    $temp = 'console.info("ErrorException details for console log");';
    $temp .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
    // Echo as an array - javascript is expecting that
    ob_start();     // to solve problems when something already sent
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
    $resultSent = true;
    //throw $ex; No that stops the message getting to the console
    exit;
} catch (\Exception $ex) {
    $temp = 'console.info("Exception details for console log");';
    $temp .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
    // Echo as an array - javascript is expecting that
    ob_start();     // to solve problems when something already sent
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
    $resultSent = true;
    //throw $ex; No that stops the message getting to the console
    exit;
} finally {
    if (!$resultSent) {
        // So had an error/exception that was not caught
        // but it seems that when we restore the error handler, that triggers the exception handling
        // so override that for now
        $old_ex_handler = set_exception_handler("exceptionHandlerOBU");
    }
    if (isset($old_error_handler)) {
        set_error_handler($old_error_handler);
    }
    // Now we should put the exception handler back and hope it doesn't get triggered again
    //if (isset($old_ex_handler)) {
        // Can't because it gets called instead of mine - set_exception_handler($old_ex_handler);
    //}
}

/**
 * errorHandlerOBU - To detect errors and throw exception so we can log them
 *
 * @param  mixed $errno
 * @param  mixed $errstr
 * @param  mixed $errfile
 * @param  mixed $errline
 * @return void
 */
function errorHandlerOBU($errno, $errstr, $errfile, $errline)
{
    // try/catch doesn't catch it throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    // this isn't caught either throw new \Exception($errstr, $errno);
    // But tried it again and this does get caught
    throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    exit;

    /*
    // So instead let's format the error as we would have in the catch, although without the stack for now
    // This will allow you to get more info to be output $ex = new \Exception($errstr, $errno);
    $temp = 'console.info("Message from obu_learnanalytics error handler");';
    $temp .= 'console.log(' . json_encode($errstr) . ');';
    $temp .= 'console.log(' . json_encode($errfile . ' line#:' . $errline) . ');';
    // $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
    // Echo as an array - javascript is expecting that if it's an error
    ob_start();     // to solve problems when something already sent
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
    //throw $ex; No that stops the message getting to the console
    exit;
    */
}

/**
 * exceptionHandlerOBU
 * Needed to try and get exceptions back to be seen at the client
 *
 * @param  mixed $ex
 * @return void
 */
function exceptionHandlerOBU($ex)
{
    $temp = 'console.info("Exception details for console log");';
    $temp .= 'console.log(' . json_encode($ex->getMessage()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getFile()) . ');';
    $temp .= 'console.log(' . json_encode($ex->getTraceAsString()) . ');';
    $consolehtml = \sprintf('<div display="none"><script type="text/javascript">%s</script></div>', $temp);
    // Echo as an array - javascript is expecting that
    ob_start();     // to solve problems when something already sent
    header('Content-type: application/json');
    echo json_encode(array('success' => false, 'consolehtml' => $consolehtml));
}

<?php
/*
// Very important to not return anything until graph is ready
// or return type will be text/html and it needs to be png
// THIS VERSION IS THE ORIGINAL WITH A COMBINED CHART
*/

require_once './jpgraph/src/jpgraph.php';
require_once './jpgraph/src/jpgraph_bar.php';
require_once './jpgraph/src/jpgraph_mgraph.php';
//echo __DIR__;
//require_once '../../config.php';
require_once __DIR__ . '/../../config.php';

// Next section of lines is to protect page from being called from outside moodle
// and breaking security by impersonating a post
defined('MOODLE_INTERNAL') || die();
die("Not supported");
global $USER;
if ($USER == null || $USER->id == 0) {
    die("Not Authenticated");
}

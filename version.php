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
 * Displays Learning Analytics data for Oxford Brookes University Students and Tutors
 *
 * @package     block_obu_learnanalytics
 * @copyright   2020 Ken Burch <ken.burch@brookes.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component  = 'block_obu_learnanalytics';
$plugin->release    = 'v0.9.8';
$plugin->version    = 2021021702;   // yyyymmddvv
$plugin->requires   = 2019052000; // Moodle v3.7.0
// Supported value is any of the predefined constants MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC or MATURITY_STABLE.
$plugin->maturity   = MATURITY_BETA;
$plugin->release    = 'Release: ' . $plugin->release;

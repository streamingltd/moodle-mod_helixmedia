<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains all necessary code to view a helixmedia activity instance
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @copyright  MEDIAL
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @author     Tim Williams for Streaming LTD 2014
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/helixmedia/lib.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

global $CFG, $PAGE;

$id = optional_param('id', 0, PARAM_INT); // Course Module ID.
$l = optional_param('l', 0, PARAM_INT);  // HML ID.
$debug = optional_param('debuglaunch', 0, PARAM_INT);

if ($l) { // Two ways to specify the module.
    $hmli = $DB->get_record('helixmedia', ['id' => $l], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('helixmedia', $hmli->id, $hmli->course, false, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('helixmedia', $id, 0, false, MUST_EXIST);
    $hmli = $DB->get_record('helixmedia', ['id' => $cm->instance], '*', MUST_EXIST);
}

$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

$toolconfig = [];
$toolconfig["launchcontainer"] = get_config("helixmedia", "default_launch");

$PAGE->set_cm($cm, $course); // Set's up global $COURSE.

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

$url = new moodle_url('/mod/helixmedia/view.php', ['id' => $cm->id]);
$PAGE->set_url($url);

$launchcontainer = lti_get_launch_container($hmli, $toolconfig);

$lparams = ['type' => HML_LAUNCH_NORMAL, 'id' => $cm->id];

if ($debug) {
    $lparams['debuglaunch'] = 1;
}

$launchurl = new moodle_url('/mod/helixmedia/launch.php', $lparams);

if ($launchcontainer == LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS) {
    $PAGE->set_pagelayout('base');
    $PAGE->blocks->show_only_fake_blocks();
} else {
    $PAGE->set_pagelayout('incourse');
}

require_course_login($course, true, $cm);
require_capability('mod/helixmedia:view', $context);

helixmedia_view($hmli, $course, $cm, $context);

$pagetitle = strip_tags($course->shortname . ': ' . format_string($hmli->name));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

// Print the page header.
echo $OUTPUT->header();

if ($hmli->showtitlelaunch) {
    // Print the main part of the page.
    echo $OUTPUT->heading(format_string($hmli->name));
}

if ($hmli->showdescriptionlaunch && $hmli->intro) {
    echo $OUTPUT->box($hmli->intro, 'generalbox description', 'intro');
}

if ($launchcontainer == LTI_LAUNCH_CONTAINER_WINDOW) {
    $output = $PAGE->get_renderer('mod_helixmedia');
    $disp = new \mod_helixmedia\output\viewwindow($launchurl->out(false), false);
    echo $output->render($disp);
} else {
    $size = helixmedia_get_instance_size($hmli, $course->id);
    $output = $PAGE->get_renderer('mod_helixmedia');
    $disp = new \mod_helixmedia\output\view($launchurl->out(true), $size->audioonly);
    echo $output->render($disp);
}
// Finish the page.
echo $OUTPUT->footer();

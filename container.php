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
 * This page acts as a container for the launch code
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

require_once("../../config.php");
require_login();

$course = optional_param('course', -1, PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$aid = optional_param('aid', 0, PARAM_INT);
$l = optional_param('l', 0, PARAM_INT);
$w = optional_param('w', 1000, PARAM_INT);
$h = optional_param('h', 600, PARAM_INT);
$ret = optional_param('ret', "", PARAM_TEXT);
$nassign = optional_param('n_assign', 0, PARAM_INT);
$eassign = optional_param('e_assign', 0, PARAM_INT);
$nfeed = optional_param('n_feed', 0, PARAM_INT);
$efeed = optional_param('e_feed', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$type = optional_param('type', -1, PARAM_INT);
$name = optional_param('name', "", PARAM_TEXT);
$intro = optional_param('intro', "", PARAM_TEXT);
$modtype = optional_param('modtype', "", PARAM_ALPHANUM);

if ($course > 0) {
    $PAGE->set_context(context_course::instance($course));
} else {
    $PAGE->set_context(context_system::instance());
}

$output = $PAGE->get_renderer('mod_helixmedia');
$disp = new \mod_helixmedia\output\container($course, $id, $aid, $l, $w, $h, $ret, $nassign, $eassign,
    $nfeed, $efeed, $userid, $type, $name, $intro, $modtype);
echo $output->render($disp);

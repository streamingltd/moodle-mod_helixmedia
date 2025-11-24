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
 * This page lists all the instances of helixmedia in a particular course
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/helixmedia/lib.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_login($course);
$PAGE->set_pagelayout('incourse');

$event = \mod_helixmedia\event\course_module_instance_list_viewed::create(
    ['context' => context_course::instance($course->id)]
);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/helixmedia/index.php', ['id' => $course->id]);
$pagetitle = strip_tags($course->shortname . ': ' . get_string("modulenamepluralformatted", "helixmedia"));
$PAGE->set_title($pagetitle);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// Print the main part of the page.
echo $OUTPUT->heading(get_string("modulenamepluralformatted", "helixmedia"));

// Get all the appropriate data.
if (! $hmlis = get_all_instances_in_course("helixmedia", $course)) {
    notice(get_string('nohelixmedias', 'helixmedia'), "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances (your module will probably extend this).
$timenow = time();
$strname = get_string("name");
$strsectionname  = get_string('sectionname', 'format_' . $course->format);
$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $table->head  = [$strsectionname, $strname];
    $table->align = ["center", "left"];
} else {
    $table->head  = [$strname];
}

foreach ($hmlis as $hmli) {
    if (!$hmli->visible) {
        // Show dimmed if the mod is hidden.
        $link = "<a class=\"dimmed\" href=\"view.php?id=$hmli->coursemodule\">$hmli->name</a>";
    } else {
        // Show normal if the mod is visible.
        $link = "<a href=\"view.php?id=$hmli->coursemodule\">$hmli->name</a>";
    }

    if ($usesections) {
        $table->data[] = [get_section_name($course, $hmli->section), $link];
    } else {
        $table->data[] = [$link];
    }
}

echo "<br />";

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();

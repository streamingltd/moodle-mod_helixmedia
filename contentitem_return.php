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
 * Handle the return from the Tool Provider after selecting a content item.
 *
 * @package mod_helixmedia
 * @copyright  2025 Streaming Ltd, Vital Source Technologies http://vitalsource.com
 * @author     Stephen Vickers, Tim Williams <tim@medial.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

$courseid = required_param('course', PARAM_INT);
$jwt = required_param('JWT', PARAM_RAW);

$context = context_course::instance($courseid);

$pageurl = new moodle_url('/mod/helixmedia/contentitem_return.php');
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('popup');
$PAGE->set_context($context);

// Cross-Site causes the cookie to be lost if not POSTed from same site.
global $_POST;
if (!empty($_POST["repost"])) {
    // Unset the param so that LTI 1.1 signature validation passes.
    unset($_POST["repost"]);
} else if (!isloggedin()) {
    header_remove("Set-Cookie");
    $output = $PAGE->get_renderer('mod_lti');
    $page = new \mod_lti\output\repost_crosssite_page($_SERVER['REQUEST_URI'], $_POST);
    echo $output->header();
    echo $output->render($page);
    echo $output->footer();
    return;
}

if (empty($jwt)) {
    echo "JWT empty";
    exit;
}

$params = helixmedia_convert_from_jwt($jwt);

$clientid = $params['oauth_consumer_key'] ?? '';
$messagetype = $params['lti_message_type'] ?? '';
$version = $params['lti_version'] ?? '';
$items = $params['content_items'] ?? '';
$errormsg = $params['lti_errormsg'] ?? '';
$msg = $params['lti_msg'] ?? '';

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
require_login($course);
require_sesskey();

// Do some permissions stuff.
$data = json_decode($params['dl-data']);
$cap = helixmedia_auth_capability($data->launchtype, $course->id, $data->modtype);

if ($cap == null || !has_capability($cap, $context, $USER)) {
    $output = $PAGE->get_renderer('mod_helixmedia');
    $disp = new \mod_helixmedia\output\launchmessage(get_string('not_authorised', 'helixmedia'));
    echo $cap . $output->render($disp);
    die;
}

$redirecturl = null;
$returndata = null;
if (empty($errormsg) && !empty($items)) {
    try {
        $returndata = helixmedia_process_content_item($messagetype, $clientid, $items);
    } catch (moodle_exception $e) {
        $errormsg = $e->getMessage();
    }
}

// Call JS module to redirect the user to the course page or close the dialogue on error/cancel.
$PAGE->requires->js_call_amd('mod_helixmedia/contentitem_return', 'init', [$returndata, $CFG->wwwroot]);

// Add messages to notification stack for rendering later.
if ($errormsg) {
    // Content item selection has encountered an error.
    \core\notification::error($errormsg);
}

echo $OUTPUT->header();
echo '<div class="alert alert-success mt-4 text-center">' . get_string('selectioncomplete', 'helixmedia') . '</div>';
echo $OUTPUT->footer();

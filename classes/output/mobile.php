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
 * This file contains helixmedia mobile code
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

namespace mod_helixmedia\output;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/helixmedia/lib.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');
require_once($CFG->libdir . '/externallib.php');

use context_module;
use mod_helixmedia_external;

/**
 * Handles requests from MoodleMobile
 */
class mobile {
    /**
     * Returns the helixmedia course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB, $CFG;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('helixmedia', $args->cmid);

        // Capabilities check.
        require_login($args->courseid, false, $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability('mod/helixmedia:view', $context);
        if ($args->userid != $USER->id) {
            require_capability('mod/helixmedia:manage', $context);
        }
        $helixmedia = $DB->get_record('helixmedia', ['id' => $cm->instance]);
        $size = helixmedia_get_instance_size($helixmedia, $args->courseid);

        [$token, $tokenid] = helixmedia_get_mobile_token($cm->id, $USER->id, $args->courseid);

        $launchurl = $CFG->wwwroot . "/mod/helixmedia/launch.php?type=" . HML_LAUNCH_NORMAL . "&id=" . $cm->id .
            "&mobiletokenid=" . $tokenid . "&mobiletoken=" . $token;

        $helixmedia->name = format_string($helixmedia->name);
        [$helixmedia->intro, $helixmedia->introformat] =
            external_format_text($helixmedia->intro, $helixmedia->introformat, $context->id, 'mod_helixmedia', 'intro');

        $data = [
            'helixmedia' => $helixmedia,
            'cmid' => $cm->id,
            'courseid' => $args->courseid,
            'launchurl' => $launchurl,
            'description' => $helixmedia->showdescriptionlaunch ? $helixmedia->intro : '',
            'canusemoduleinfo' => $args->appversioncode >= 44000,
            'medialurl' => get_config("helixmedia", "launchurl"),
        ];

        if ($size->audioonly) {
            $data['height'] = '100';
        } else {
            $data['height'] = '650';
        }

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_helixmedia/mobile_view_page', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => '',
        ];
    }
}

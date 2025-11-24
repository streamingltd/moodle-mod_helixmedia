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
 * The mod_helixmedia LTI launch event with legacy log compatibility.
 *
 * @package    mod_helixmedia
 * @copyright  2023 Streaming LTD
 * @author     Tim Williams tim@medial.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\event;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

/**
 * LTI Launch Event
 *
 * Class for event to be triggered when a course module is viewed with legacy log compatibility.
 * Note this class is only used in Moodle 4.1 and lower
 *
 * @package    mod_helixmedia
 * @since      Moodle 2.7
 * @copyright  2023 Streaming LTD
 * @author     Tim Williams tim@medial.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_compat_launch extends lti_launch {
    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return [$this->courseid, $this->objecttable, 'launch', 'launch.php?id=' . $this->contextinstanceid . '&type=' .
            HML_LAUNCH_NORMAL, $this->objectid, $this->contextinstanceid];
    }
}

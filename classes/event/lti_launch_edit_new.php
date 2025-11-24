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
 * The mod_helixmedia LTI new edit launch event.
 *
 * @package    mod_helixmedia
 * @copyright  2015 Streaming LTD
 * @author     Tim Williams tmw@autotrain.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\event;

/**
 * LTI new edit Launch Event
 *
 * Class for event to be triggered when a course module is viewed.
 *
 * @package    mod_helixmedia
 * @since      Moodle 2.7
 * @copyright  2015 Streaming LTD
 * @author     Tim Williams tim@medial.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lti_launch_edit_new extends \core\event\base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'helixmedia';
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' launched a new editing session for the '{$this->objecttable}' activity with the " .
            "course module id '$this->contextinstanceid'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('log_launcheditnew', 'mod_helixmedia');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return null;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
        // Make sure this class is never used without proper object details.
        if (empty($this->objectid) || empty($this->objecttable)) {
            throw new \coding_exception('The lti_launch_edit event must define objectid and object table.');
        }
        // Make sure the context level is set to course.
        if ($this->contextlevel != CONTEXT_COURSE) {
            throw new \coding_exception('Context level must be CONTEXT_COURSE.');
        }
    }
}

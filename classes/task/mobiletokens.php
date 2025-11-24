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
 * This file defines the version of helixmedia
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @copyright  2013 Tim Williams (For Streaming LTD)
 * @author     Tim Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\task;

/**
 * Cleanup task for HelixMedia;
 */
class mobiletokens extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('mobiletokens', 'mod_helixmedia');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;
        $dayago = time() - (24 * 60 * 60);
        $DB->delete_records_select("helixmedia_mobile", "timecreated < " . $dayago);
    }
}

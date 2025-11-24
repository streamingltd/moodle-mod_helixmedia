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
 * Cleanup task for HelixMedia.
 */
class cleanup extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanup', 'mod_helixmedia');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG, $DB;

        // Cleanup old LTI 1.3 access tokens.
        $DB->delete_records_select('helixmedia_access_tokens', 'validuntil < ?', [time()]);

        // Cleanup resource link ID generation.
        $prerecs = $DB->get_records('helixmedia_pre');

        // If there is only one entry in the table, leave it alone regardless. This is needed to stop InnoDB from
        // incorrectly recalculating the AUTO_INCREMENT value if the DB is restarted with an empty table.
        if (count($prerecs) < 2) {
             return;
        }
        // Remove the last element so that the most recent preid value always explicitly remains in the database
        // for the benefit of InnoDB.
        array_pop($prerecs);

        $subplugins = \core_plugin_manager::instance()->get_installed_plugins('assignsubmission');
        if (array_key_exists('helixassign', $subplugins)) {
            $assigninstalled = true;
        } else {
            $assigninstalled = false;
        }

        $feedplugins = \core_plugin_manager::instance()->get_installed_plugins('assignfeedback');
        if (array_key_exists('helixfeedback', $feedplugins)) {
            $feedinstalled = true;
        } else {
            $feedinstalled = false;
        }

        foreach ($prerecs as $prerec) {
            $hm = $DB->get_record('helixmedia', ['preid' => $prerec->id]);
            if (!$hm && $assigninstalled) {
                $hm = $DB->get_record('assignsubmission_helixassign', ['preid' => $prerec->id]);
            }
            if (!$hm && $feedinstalled) {
                $hm = $DB->get_record('assignfeedback_helixfeedback', ['preid' => $prerec->id]);
            }

            // Clean out anything with an ID that is now in the main table or older than the session time out.
            if ($hm || $prerec->timecreated + $CFG->sessiontimeout < time()) {
                $DB->delete_records('helixmedia_pre', ['id' => $prerec->id]);
            }
        }
    }
}

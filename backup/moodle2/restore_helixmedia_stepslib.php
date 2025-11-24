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
 * This file contains all the restore steps that will be used
 * by the restore_helixmedia_activity_task
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/helixmedia/lib.php');

/**
 * Structure step to restore one helixmedia activity
 */
class restore_helixmedia_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define the backup structure
     * @return restore_path_element
     */
    protected function define_structure() {

        $paths = [];
        $paths[] = new restore_path_element('helixmedia', '/activity/helixmedia');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process a backup element
     * @param array $data The backup element
     */
    protected function process_helixmedia($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->servicesalt = uniqid('', true);

        $newitemid = $DB->insert_record('helixmedia', $data);

        // Ideally this needs to be more elegant, but this will prevent restores from mucking up new instances
        // if the preid is higher. There doesn't seem to be a standard SQL way to retrieve the next value in a
        // sequence. So just get the next pre_id and check that it is greater than the one we are adding here
        // and if it isn't keep going until it is.

        $nextpreid = helixmedia_preallocate_id();
        while ($nextpreid < $data->preid) {
            $DB->delete_records('helixmedia_pre', ['id' => $nextpreid]);
            $nextpreid = helixmedia_preallocate_id();
        }
        $DB->delete_records('helixmedia_pre', ['id' => $nextpreid]);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Code executed after backup
     */
    protected function after_execute() {
        // Add helixmedia related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_helixmedia', 'intro', null);
    }
}

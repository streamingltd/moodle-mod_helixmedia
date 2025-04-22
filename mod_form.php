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
 * This page contains the instance configuration form for the HML activity.
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

class mod_helixmedia_mod_form extends moodleform_mod {

    public function definition() {
        global $add, $CFG, $update, $DB, $PAGE;
        $mform =& $this->_form;

        $mform->addElement('hidden', 'preid');
        $mform->setType('preid', PARAM_INT);

        $mform->addElement('text', 'name', get_string("helixmediatext", "helixmedia"), 'size="47"');
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        if ($CFG->version >= 2015051100) {
            $this->standard_intro_elements(get_string("helixmediasummary", "helixmedia"));
        } else {
            $this->add_intro_editor(true, get_string("helixmediasummary", "helixmedia"));
        }

        $launchoptions = array();
        $launchoptions[LTI_LAUNCH_CONTAINER_DEFAULT] = get_string('default', 'lti');
        $launchoptions[LTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'lti');
        $launchoptions[LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'lti');
        $launchoptions[LTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'lti');

        $mform->addElement('select', 'launchcontainer', get_string('launchinpopup', 'lti'), $launchoptions);
        $mform->setDefault('launchcontainer', LTI_LAUNCH_CONTAINER_DEFAULT);
        $mform->addHelpButton('launchcontainer', 'launchinpopup', 'lti');

        $mform->addElement('checkbox', 'showtitlelaunch', '&nbsp;', ' ' . get_string('display_name', 'lti'));
        $mform->addHelpButton('showtitlelaunch', 'display_name', 'lti');

        $mform->addElement('checkbox', 'showdescriptionlaunch', '&nbsp;', ' ' . get_string('display_description', 'lti'));
        $mform->addHelpButton('showdescriptionlaunch', 'display_description', 'lti');

        $mform->addElement('static', 'choosemedia', get_string('choosemedia_title', 'mod_helixmedia'), '');
        $features = array('groups' => false, 'groupings' => false, 'groupmembersonly ' => true,
                          'outcomes' => false, 'gradecat' => false, 'idnumber' => false);
        $this->standard_coursemodule_elements($features);
        $this->add_action_buttons();
    }

    function definition_after_data() {
        parent::definition_after_data();
        global $PAGE, $add, $update;
        $mform =& $this->_form;
        $pr =& $mform->getElement('preid');
        $ch =& $mform->getElement('choosemedia');
        $output = $PAGE->get_renderer('mod_helixmedia');
        if ($add) {
            $preid = helixmedia_preallocate_id();
            $pr->setValue($preid);
            $disp = new \mod_helixmedia\output\modal($preid,
                array('type' => HML_LAUNCH_THUMBNAILS, 'l' => $preid),
                array('type' => HML_LAUNCH_EDIT, 'l' => $preid), true);
            $ch->setValue($output->render($disp));
            return;
        }

        if ($update) {
            $preid = helixmedia_get_preid($update);
            $pr->setValue($preid);
            $disp = new \mod_helixmedia\output\modal($preid,
                array('type' => HML_LAUNCH_THUMBNAILS, 'id' => $update),
                array('type' => HML_LAUNCH_EDIT, 'id' => $update), true);
            $ch->setValue($output->render($disp));
            return;
        }
    }

}

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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

/**
 * This page contains the instance configuration form for the HML activity.
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */
class mod_helixmedia_mod_form extends moodleform_mod {
    /**
     * Defines the form
     */
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

        $this->standard_intro_elements(get_string("helixmediasummary", "helixmedia"));

        $launchoptions = [];
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

        if (get_config("helixmedia", "ltiversion") == LTI_VERSION_1P3) {
            $mform->addElement('checkbox', 'addgrades', '&nbsp;', ' ' . get_string('addgrades', 'helixmedia'));
        }

        $mform->addElement('hidden', 'custom');
        $mform->setType('custom', PARAM_RAW);

        $mform->addElement('static', 'choosemedia', get_string('choosemedia_title', 'mod_helixmedia'), '');

        $features = ['groups' => false, 'groupings' => false, 'groupmembersonly ' => true,
                          'outcomes' => false, 'gradecat' => false, 'idnumber' => false];
        $this->standard_coursemodule_elements($features);
        $this->add_action_buttons();
    }

    /**
     * Set values in the form after creation
     */
    public function definition_after_data() {
        global $PAGE, $add, $update;
        $mform =& $this->_form;
        $pr =& $mform->getElement('preid');
        $ch =& $mform->getElement('choosemedia');
        if (get_config("helixmedia", "ltiversion") == LTI_VERSION_1P3) {
            $grade =& $mform->getElement('addgrades');
        }
        $output = $PAGE->get_renderer('mod_helixmedia');
        if ($add) {
            $preid = helixmedia_preallocate_id();
            $pr->setValue($preid);
            $disp = new \mod_helixmedia\output\modal(
                $preid,
                ['type' => HML_LAUNCH_THUMBNAILS, 'l' => $preid],
                ['type' => HML_LAUNCH_EDIT, 'l' => $preid],
                true
            );
            $ch->setValue($output->render($disp));
        }

        if ($update) {
            $preid = helixmedia_get_preid($update);
            $pr->setValue($preid);
            $disp = new \mod_helixmedia\output\modal(
                $preid,
                ['type' => HML_LAUNCH_THUMBNAILS, 'id' => $update],
                ['type' => HML_LAUNCH_EDIT, 'id' => $update],
                true
            );
            $ch->setValue($output->render($disp));
        }
        parent::definition_after_data();
    }
}

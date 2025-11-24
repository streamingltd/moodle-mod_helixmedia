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
 * This file contains a library of functions and constants for the helixmedia module
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu, MEDIAL
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @author     Chris Scribner
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * List of features supported in URL module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function helixmedia_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_CONTENT;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
            if (get_config("helixmedia", "ltiversion") == LTI_VERSION_1P3) {
                return true;
            }
            return false;
        default:
            return null;
    }
}

/**
 * Allocate a resource link ID
 */
function helixmedia_preallocate_id() {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

    $pre = new stdclass();
    $pre->timecreated = time();
    $pre->servicesalt = uniqid('', true);

    $pre->id = $DB->insert_record('helixmedia_pre', $pre);

    // If the value here is 1 then either this is a new install or the auto_increment value has been reset
    // due to the problem with InnoDB (mariadb & mysqli only) not storing this value persistently. Check regardless.
    if ($pre->id == 1 && ($CFG->dbtype == "mariadb" || $CFG->dbtype == "mysqli")) {
        $val = 1;
        // Check the activity mod.
        $sql = "SELECT MAX(preid) AS preid FROM " . $CFG->prefix . "helixmedia;";
        $vala = $DB->get_record_sql($sql);
        if ($vala) {
            $val = $vala->preid;
        }
        // Check the Submissions.
        $assigninstalled = $DB->get_records('assign_plugin_config', ['plugin' => 'helixassign']);
        if (count($assigninstalled) > 0) {
            $sql = "SELECT MAX(preid) AS preid FROM " . $CFG->prefix . "assignsubmission_helixassign;";
            $valb = $DB->get_record_sql($sql);
            if ($valb && $valb->preid > $val) {
                $val = $valb->preid;
            }
        }
        // Check the Feedback.
        $feedinstalled = $DB->get_records('assign_plugin_config', ['plugin' => 'helixfeedback']);
        if (count($feedinstalled) > 0) {
            $sql = "SELECT MAX(preid) AS preid FROM " . $CFG->prefix . "assignfeedback_helixfeedback;";
            $valc = $DB->get_record_sql($sql)->preid;
            if ($valc && $valc->preid > $val) {
                $val = $valc->preid;
            }
        }

        // Checking all the instances created by the HTML editor would be a massive slow query, so
        // i'm going to assume that all the modules get used with a reasonable degree of frequency and just add 100
        // +10% of the highest value found to offest things. This is likely to be a very rare problem, since mitgating steps
        // are being taken else where to prevent this problem, so this exists simply to fix installations that have already
        // gone wrong.

        $val = intval($val / 10) + 100;

        $DB->execute("ALTER TABLE " . $CFG->prefix . "helixmedia_pre AUTO_INCREMENT=" . $val . "");
        $pre = new stdclass();
        $pre->timecreated = time();
        $pre->servicesalt = uniqid('', true);
        $pre->id = $DB->insert_record('helixmedia_pre', $pre);
    }

    return $pre->id;
}

/**
 * Get the resource link id
 * @param int $cmid Course module id
 * @return int The Resource link id
 */
function helixmedia_get_preid($cmid) {
    global $DB;
    $cm = get_coursemodule_from_id('helixmedia', $cmid, 0, false, MUST_EXIST);
    $hmli = $DB->get_record('helixmedia', ['id' => $cm->instance], '*', MUST_EXIST);
    return $hmli->preid;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $helixmedia An object from the form in mod.html
 * @param object $mform The Moodle form
 * @return int The id of the newly inserted helixmedia record
 **/
function helixmedia_add_instance($helixmedia, $mform) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

    $prerec = $DB->get_record('helixmedia_pre', ['id' => $helixmedia->preid]);

    $helixmedia->timecreated = time();
    $helixmedia->timemodified = $helixmedia->timecreated;
    if (property_exists($prerec, 'servicesalt')) {
        $helixmedia->servicesalt = $prerec->servicesalt;
    }
    if (!isset($helixmedia->showtitlelaunch)) {
        $helixmedia->showtitlelaunch = 0;
    }

    if (!isset($helixmedia->showdescriptionlaunch)) {
        $helixmedia->showdescriptionlaunch = 0;
    }

    // Set these to some defaults for now.
    $helixmedia->icon = "";
    $helixmedia->secureicon = "";

    $helixmedia->id = $DB->insert_record('helixmedia', $helixmedia);

    if (property_exists($helixmedia, 'addgrades') && $helixmedia->addgrades) {
        if (!isset($helixmedia->cmidnumber)) {
            $helixmedia->cmidnumber = '';
        }
        helixmedia_grade_item_update($helixmedia);
    }

    return $helixmedia->id;
}


/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $helixmedia An object from the form in mod.html
 * @param object $mform The Moodle form
 * @return boolean Success/Fail
 **/
function helixmedia_update_instance($helixmedia, $mform) {
    global $DB, $CFG;

    $helixmedia->timemodified = time();
    $helixmedia->id = $helixmedia->instance;

    if (!isset($helixmedia->showtitlelaunch)) {
        $helixmedia->showtitlelaunch = 0;
    }

    if (!isset($helixmedia->showdescriptionlaunch)) {
        $helixmedia->showdescriptionlaunch = 0;
    }

    if (property_exists($helixmedia, 'addgrades') && $helixmedia->addgrades) {
        helixmedia_grade_item_update($helixmedia);
    } else {
        // Instance is no longer accepting grades from Provider, set grade to "No grade" value 0.
        helixmedia_grade_item_delete($helixmedia);
    }

    return $DB->update_record('helixmedia', $helixmedia);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function helixmedia_delete_instance($id) {
    global $DB;

    if (! $helixmedia = $DB->get_record("helixmedia", ["id" => $id])) {
        return false;
    }

    // Delete any dependent records here.
    helixmedia_grade_item_delete($helixmedia);

    $DB->delete_records("helixmedia", ["id" => $helixmedia->id]);
    return true;
}

/**
 * Execute post-install custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function helixmedia_install() {
     return true;
}

/**
 * Execute post-uninstall custom actions for the module
 * This function was added in 1.9
 *
 * @return boolean true if success, false on error
 */
function helixmedia_uninstall() {
    return true;
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  object $hml        hml object
 * @param  object $course     course object
 * @param  object $cm         course module object
 * @param  object $context    context object
 * @param  object $user       user object or null
 * @since Moodle 3.0
 */
function helixmedia_view($hml, $course, $cm, $context, $user = null) {
    global $USER;
    if ($user == null) {
        $user = $USER;
    }

    // Trigger course_module_viewed event.
    $params = [
        'context' => $context,
        'objectid' => $hml->id,
        'userid' => $user->id,
    ];

    $event = \mod_helixmedia\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('helixmedia', $hml);
    $event->trigger();

    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Create grade item for given hmli
 *
 * @category grade
 * @param object $hmli object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function helixmedia_grade_item_update($hmli, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    if (!$hmli->addgrades) {
        return 0;
    }

    $params = ['itemname' => $hmli->name, 'idnumber' => $hmli->cmidnumber];

    $custom = json_decode($hmli->custom);
    if (!property_exists($custom, 'is_quiz') || strtolower($custom->is_quiz) != "true") {
        return 0;
    }

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademax']  = intval($custom->max_score);
    $params['grademin']  = 0;

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/helixmedia', $hmli->course, 'mod', 'helixmedia', $hmli->id, 0, $grades, $params);
}

/**
 * Update activity grades
 *
 * @param stdClass $hmli The HML instance
 * @param int      $userid Specific user only, 0 means all.
 * @param bool     $nullifnone Not used
 */
function helixmedia_update_grades($hmli, $userid = 0, $nullifnone = true) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/lti/servicelib.php');
    // MEDIAL doesn't have its own grade table so the only thing to do is update the grade item.
    if ($hmli->addgrades) {
        lti_grade_item_update($hmli);
    }
}

/**
 * Delete grade item for given basiclti
 *
 * @category grade
 * @param object $hmli object
 * @return object hmli
 */
function helixmedia_grade_item_delete($hmli) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    grade_update('mod/helixmedia', $hmli->course, 'mod', 'helixmedia', $hmli->id, 0, null, ['deleted' => 1]);
}

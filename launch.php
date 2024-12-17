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
 * This file contains all necessary code to view a helixmedia activity instance
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');
require_once($CFG->dirroot.'/mod/helixmedia/lib.php');

?>

<?php

// Course module ID.
$id = optional_param('id', 0, PARAM_INT); // Course Module ID.

// Assignment course module ID.
$aid = optional_param('aid', 0, PARAM_INT);

// HML preid, only used here for a Fake launch for new instances.
$l = optional_param('l', 0, PARAM_INT);  // HML ID.

// Hidden option to force debug lanuch.
$debug = optional_param('debuglaunch', 0, PARAM_INT);

// Course ID.
// Note using $COURSE->id here seems to give random results.
global $USER;

$c  = optional_param('course', false, PARAM_INT);
if ($c === false) {
    if (property_exists($USER, 'currentcourseaccess')) {
        $c = 1;
        $lastime = 0;
        // Find the most recent course access, this should be the course we are in since the page just loaded.
        foreach ($USER->currentcourseaccess as $key => $time) {
            if ($time > $lastime) {
                $c = $key;
                $lastime = $time;
            }
        }
    } else {
        $c = $COURSE->id;
    }
    $courseinc = false;
} else {
    $courseinc = true;
}

// New assignment submission ID.
$nassign = optional_param('n_assign', 0, PARAM_INT);

// Existing assignment submission ID.
$eassign = optional_param('e_assign', 0, PARAM_INT);

// New feedback ID.
$nfeed = optional_param('n_feed', 0, PARAM_INT);

// Existing feedback ID.
$efeed = optional_param('e_feed', 0, PARAM_INT);

// User ID for student submission viewing.
$userid = optional_param('userid', 0, PARAM_INT);

// Launch type.
$type = required_param('type', PARAM_INT);

// Used for migration only.
$mid  = optional_param('mid', -1, PARAM_INT);

// Base64 encoded return URL.
$ret  = optional_param('ret', "", PARAM_TEXT);

// Item name.
$name  = optional_param('name', "", PARAM_TEXT);

// Item Intro text.
$intro  = optional_param('intro', "", PARAM_TEXT);

// What's the modtype here.
$modtype  = optional_param('modtype', "", PARAM_TEXT);

// Check for responsive embeds with ATTO or TinyMCE
$responsive = optional_param('responsive', 0, PARAM_BOOL);

if (strlen($ret) > 0) {
    $ret = base64_decode($ret);
}

$hmli = null;
$cmid = -1;
$postscript = false;
$legacyjsresize = false;

if ($l || $nassign || $nfeed || $type == HML_LAUNCH_TINYMCE_EDIT || $type == HML_LAUNCH_TINYMCE_VIEW ||
    $type == HML_LAUNCH_ATTO_EDIT || $type == HML_LAUNCH_ATTO_VIEW || $type == HML_LAUNCH_LIB_ONLY) {
    // This means that we're doing a "fake" launch for a new instance or viewing via a link created in TinyMCE/ATTO.

    $hmli = new stdclass();
    $hmli->id = -1;

    if ($l) {
        $hmli->preid = $l;
    } else {
        if ($nassign) {
            $hmli->preid = $nassign;
        } else {
            if ($nfeed) {
                $hmli->preid = $nfeed;
            } else {
                if ($type == HML_LAUNCH_TINYMCE_EDIT || HML_LAUNCH_ATTO_EDIT) {
                    $hmli->preid = helixmedia_preallocate_id();
                    $postscript = true;
                }
            }
        }
    }

    if ($type == HML_LAUNCH_TINYMCE_VIEW || $type == HML_LAUNCH_ATTO_VIEW) {
        if ((!$courseinc || !isloggedin()) && strpos($_SERVER ['HTTP_USER_AGENT'], 'MoodleMobile') !== false) {
            $output = $PAGE->get_renderer('mod_helixmedia');
            $disp = new \mod_helixmedia\output\launchmessage(get_string('moodlemobile', 'helixmedia'));
            echo $output->render($disp);
            die;
        }

        if ($responsive == 0) {
            $legacyjsresize = helixmedia_legacy_dynamic_size($hmli, $c);
        }
    }

    $hmli->name = '';
    $hmli->course = $c;
    $hmli->intro = "";
    $hmli->introformat = 1;
    $hmli->timecreated = time();
    $hmli->timemodified = $hmli->timecreated;
    $hmli->showtitlelaunch = 0;
    $hmli->showdescriptionlaunch = 0;
    $hmli->servicesalt = uniqid('', true);
    $hmli->icon = "";
    $hmli->secureicon = "";

    if ($aid) {
        $cm = get_coursemodule_from_id('assign', $aid, 0, false, MUST_EXIST);
        $assign = $DB->get_record('assign', array('id' => $cm->instance), '*', MUST_EXIST);
        if ($nassign) {
            $hmli->name = get_string('assignsubltititle', 'helixmedia', $assign->name);
            $hmli->intro = fullname($USER);
        } else {
            $fuser = $DB->get_record('user', array('id' => $userid)); 
            $hmli->intro = $assign->name;
            $hmli->name = get_string('assignfeedltititle', 'helixmedia', fullname($fuser));
        }
        $hmli->cmid = $aid;
    } else {
        if (strlen($name) > 0) {
            $hmli->name = $name;
        } else {
            $a = new \stdclass();
            $a->name = fullname($USER);
            $a->date = userdate(time(), get_string('strftimedatetimeshort'));
            $hmli->intro = fullname($USER);
        }
        //if (strlen($intro) > 0) {
        //    $hmli->intro = $intro;
        //}
        $hmli->cmid = -1;
    }
    $course = $DB->get_record('course', array('id' => $c), '*', MUST_EXIST);
    if (method_exists("context_course", "instance")) {
        $context = context_course::instance($course->id);
    } else {
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
    }
    $PAGE->set_context($context);
} else {
    // Normal launch.
    if ($id) {
        $cm = get_coursemodule_from_id('helixmedia', $id, 0, false, MUST_EXIST);
        $cmid = $cm->id;
        $hmli = $DB->get_record('helixmedia', array('id' => $cm->instance), '*', MUST_EXIST);
        $hmli->cmid = $cm->id;
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    } else {
        if ($eassign) {
            $hmlassign = $DB->get_record('assignsubmission_helixassign', array('preid' => $eassign));
            $hmli = $DB->get_record('assign', array('id' => $hmlassign->assignment));
            $cm = get_coursemodule_from_instance('assign', $hmli->id, 0, false, MUST_EXIST);
            $cmid = $cm->id;
            $hmli->cmid = $cm->id;
            $hmli->preid = $hmlassign->preid;
            $hmli->servicesalt = $hmlassign->servicesalt;
            $hmli->name = get_string('assignsubltititle', 'helixmedia', $hmli->name);
            $hmli->intro = fullname($USER);
            $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        } else {
            if ($efeed) {
                $hmlfeed = $DB->get_record('assignfeedback_helixfeedback', array('preid' => $efeed));
                $hmli = $DB->get_record('assign', array('id' => $hmlfeed->assignment));
                $cm = get_coursemodule_from_instance('assign', $hmli->id, 0, false, MUST_EXIST);
                $cmid = $cm->id;
                $hmli->cmid = $cm->id;
                $hmli->preid = $hmlfeed->preid;
                $hmli->servicesalt = $hmlfeed->servicesalt;
                $fuser = $DB->get_record('user', array('id' => $userid)); 
                $hmli->intro = $hmli->name;
                $hmli->name = get_string('assignfeedltititle', 'helixmedia', fullname($fuser));
                $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
            } else {
                $output = $PAGE->get_renderer('mod_helixmedia');
                $disp = new \mod_helixmedia\output\launchmessage(get_string('invalid_launch', 'helixmedia'), 'error');
                echo $output->render($disp). $type;
                exit(0);
            }
        }
    }

    $PAGE->set_cm($cm, $course);
    if (method_exists("context_module", "instance")) {
        $context = context_module::instance($cm->id);
    } else {
        $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    }
    $PAGE->set_context($context);
}

// Is this a mobile app launch?
$mobiletokenid = optional_param('mobiletokenid', 0, PARAM_INT);
if ($mobiletokenid) {
    $mobiletoken = required_param('mobiletoken', PARAM_TEXT);
    $tokenrecord = $DB->get_record('helixmedia_mobile', array('id' => $mobiletokenid));
    if (!$tokenrecord ||
        $tokenrecord->token != $mobiletoken ||
        $tokenrecord->instance != $cm->id) {
            $output = $PAGE->get_renderer('mod_helixmedia');
            $disp = new \mod_helixmedia\output\launchmessage(get_string('invalid_mobile_token', 'helixmedia'));
            echo $output->render($disp);
            exit(0);
    }
    $user = $DB->get_record('user', array('id' => $tokenrecord->userid));
} else {
    require_login($course);
    $user = $USER;
}

// Do some permissions stuff.
$cap = null;
switch ($type) {
    case HML_LAUNCH_NORMAL:
    case HML_LAUNCH_THUMBNAILS:
    case HML_LAUNCH_TINYMCE_VIEW:
    case HML_LAUNCH_ATTO_VIEW:
    case HML_LAUNCH_VIEW_FEEDBACK:
    case HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS:
        if ($course->id == SITEID) {
            $cap = 'mod/helixmedia:myview';
        } else {
            $cap = 'mod/helixmedia:view';
        }
        break;
    case HML_LAUNCH_EDIT:
        $cap = 'mod/helixmedia:addinstance';
        break;
    case HML_LAUNCH_TINYMCE_EDIT:
        $cap = helixmedia_get_visiblecap($modtype, 'tiny/medial');
        break;
    case HML_LAUNCH_ATTO_EDIT:
        $cap = helixmedia_get_visiblecap($modtype);
        break;
    case HML_LAUNCH_STUDENT_SUBMIT:
    case HML_LAUNCH_STUDENT_SUBMIT_PREVIEW:
    case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
        $cap = 'mod/assign:submit';
        break;
    case HML_LAUNCH_VIEW_SUBMISSIONS:
    case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
    case HML_LAUNCH_FEEDBACK:
    case HML_LAUNCH_FEEDBACK_THUMBNAILS:
        $cap = 'mod/assign:grade';
        break;
    case HML_LAUNCH_LIB_ONLY:
        if ($course->id == SITEID) {
            $cap = 'mod/helixmedia:myview';
        } else {
            $cap = 'mod/helixmedia:view';
        }
        break;
}

if ($cap == null || !has_capability($cap, $context, $user)) {
    $output = $PAGE->get_renderer('mod_helixmedia');
    $disp = new \mod_helixmedia\output\launchmessage(get_string('not_authorised', 'helixmedia'));
    echo $output->render($disp);
    die;
}

$hmli->debuglaunch = 0;
$modconfig = get_config("helixmedia");
if ( ($modconfig->forcedebug && $modconfig->restrictdebug && is_siteadmin()) ||
     ($modconfig->restrictdebug == false && $modconfig->forcedebug)) {
    $hmli->debuglaunch = 1;
}


// Do the logging.
if ($type == HML_LAUNCH_NORMAL || $type == HML_LAUNCH_EDIT) {

    // Moodle 4.2+ now emits a warning if legacy log methods are present in events
    // So we don't have to split the code base use a sub class if we actually need legacy logging here 
    if ($CFG->version < 2023042400 && get_config('logstore_legacy', 'loglegacy') == 1) {
        $cname = '_compat';
    } else {
        $cname = '';
    }

    if ($type == HML_LAUNCH_EDIT) {
        if ($l) {
            $cname = '\mod_helixmedia\event\lti_launch_edit'.$cname.'_new';
            $event = $cname::create(array(
                'objectid' => $hmli->id,
                'context' => $context
            ));
        } else {
            $cname = '\mod_helixmedia\event\lti_launch'.$cname.'_edit';
            $event = $cname::create(array(
                'objectid' => $hmli->id,
                'context' => $context
            ));
        }
    } else {
        $cname = '\mod_helixmedia\event\lti'.$cname.'_launch';
        $event = $cname::create(array(
            'objectid' => $hmli->id,
            'context' => $context
        ));
    }

    if (isset($cm)) {
        $event->add_record_snapshot('course_modules', $cm);
    }

    $event->add_record_snapshot('course', $course);

    // The launch container may not be set for a new instance but Moodle will complain if it's missing, so set default here.
    if (!property_exists($hmli, "launchcontainer")) {
        $hmli->launchcontainer = LTI_LAUNCH_CONTAINER_DEFAULT;
    }

    $event->add_record_snapshot('helixmedia', $hmli);
    $event->trigger();
}

if ($type == HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS || $type == HML_LAUNCH_VIEW_SUBMISSIONS) {
    $hmli->userid = $userid;
}

if ($type == HML_LAUNCH_NORMAL) {
    helixmedia_view($hmli, $course, $cm, $context, $user);
}

$ishtmlassign = false;

// Try to detect if this is an ATTO/TINY Launch where these plugins have been used with a text area for student submissions
if ($type == HML_LAUNCH_ATTO_EDIT || $type == HML_LAUNCH_TINYMCE_EDIT || $type == HML_LAUNCH_ATTO_VIEW || $type == HML_LAUNCH_TINYMCE_VIEW) {

    // If this is a tutor, check if we are grading. If we are they are looking at a student submission.
    if (has_capability('mod/assign:viewgrades', $context, $user)) {
        $ishtmlassign = helixmedia_detect_assign_grading_view($_SERVER['HTTP_REFERER']);
    }
}

//helixmedia_view_mod($hmli, $type, $mid, $ret, $user, $modtype);
$PAGE->set_pagelayout('popup');
$PAGE->set_url('/mod/helixmedia/view.php', array('id' => $hmli->id));
$PAGE->set_title('');
$PAGE->set_heading('');
echo $OUTPUT->header();
$output = $PAGE->get_renderer('mod_helixmedia');
$disp = new \mod_helixmedia\output\launcher($hmli, $type, $mid, $ret, $user, $modtype, $postscript, $legacyjsresize, $ishtmlassign);
echo $output->render($disp);
echo $OUTPUT->footer();

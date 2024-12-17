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


namespace mod_helixmedia\output;
defined('MOODLE_INTERNAL') || die();

/**
 * Launch activity
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use renderable;
use renderer_base;
use templatable;

/**
 * Container renderable class.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launcher implements renderable, templatable {

    private $postscript, $preid, $text, $size, $legacyjsresize, $endpoint, $debuglaunch, $params;

    /**
     * Constructor.
     *
     * @param $instance The helixmedia instance.
     * @param $type The Helix Launch Type
     * @param $ref The value for the custom_video_ref parameter
     * @param $modtype The module type, use to check if we can use the more permissive
     * @param $ret The return URL to set for the modal dialogue
     * @param $ishtmlassign If this is an ATTO/Tiny launch from a student submission
     */

    public function __construct($instance, $type, $ref, $ret, $user, $modtype, $postscript, $legacyjsresize = false, $ishtmlassign = false) {
        global $CFG, $DB;

        $this->postscript = $postscript;
        $this->preid = $instance->preid;
        $this->text = false;
        $this->size = 128;
        $this->legacyjsresize = $legacyjsresize;

        $modconfig = get_config("helixmedia");

        if (property_exists($instance, "version")) {
            $version = $hml->version;
        } else {
            $version = get_config('mod_helixmedia', 'version');
        }

        // Check to see if the DB has duplicate preid's for the assignment submission, if it does send an
        // old version number to trigger the fix for this problem. The check doesn't need to be exhaustive.
        // Either the whole lot will match, or none will.
        if ($type == HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS || $type == HML_LAUNCH_VIEW_SUBMISSIONS) {
            $ass = $DB->get_record("course_modules", array("id" => $instance->cmid));
            $recs = $DB->get_records("assignsubmission_helixassign", array("assignment" => $ass->instance));
            $num = -1;
            foreach ($recs as $rec) {
                if ($num == -1) {
                    $num = $rec->preid;
                } else {
                    if ($num == $rec->preid) {
                        $version = 2014111700;
                        break;
                    }
                }
            }
        }

        // Set up the type config.
        $typeconfig = (array)$instance;
        $typeconfig['sendname'] = $modconfig->sendname;
        $typeconfig['sendemailaddr'] = $modconfig->sendemailaddr;
        $typeconfig['customparameters'] = $modconfig->custom_params."\nhml_version=".$version;

        switch ($type) {
            case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
            case HML_LAUNCH_THUMBNAILS:
            case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
            case HML_LAUNCH_FEEDBACK_THUMBNAILS:
            case HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS:
                // For MEDIAL 8.0.07 and higher we can use a responsive thumbnail

                if ($modconfig->medialversion >= 80007) {
                    $typeconfig['customparameters'] .= "\nthumbnail=Y\nthumbnail_width=-1\nthumbnail_height=-1";
                } else {
                    $typeconfig['customparameters'] .= "\nthumbnail=Y\nthumbnail_width=230\nthumbnail_height=129";
                }
                $this->size = 64;
                break;
        }

        switch ($type) {
            case HML_LAUNCH_NORMAL:
            case HML_LAUNCH_TINYMCE_VIEW:
            case HML_LAUNCH_ATTO_VIEW:
                $typeconfig['customparameters'] .= "\nview_only=Y\nno_horiz_borders=Y\nplay_only=Y";
                $this->text = get_string('pleasewait', 'helixmedia');
                break;
            case HML_LAUNCH_EDIT:
            case HML_LAUNCH_TINYMCE_EDIT:
            case HML_LAUNCH_ATTO_EDIT:
                $typeconfig['customparameters'] .= "\nno_horiz_borders=Y\nlink_response=Y";
                $this->text = get_string('pleasewaitup', 'helixmedia');
                break;
            case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
                // Nothing to do here.
                $typeconfig['customparameters'] .= "\nplay_only=Y";
                break;
            case HML_LAUNCH_STUDENT_SUBMIT:
                $typeconfig['customparameters'] .= "\nlink_response=Y\nlink_type=Assignment\nplay_only=Y";
                $typeconfig['customparameters'] .= "\nassignment_ref=".$instance->cmid;
                $typeconfig['customparameters'] .= "\ntemp_assignment_ref=".helixmedia_get_assign_into_refs($instance->cmid)."\n";
                $typeconfig['customparameters'] .= "\ngroup_assignment=".helixmedia_is_group_assign($instance->cmid);
                $this->text = get_string('pleasewaitup', 'helixmedia');
                break;
            case HML_LAUNCH_STUDENT_SUBMIT_PREVIEW:
                $typeconfig['customparameters'] .= "\nlink_type=Assignment";
                $typeconfig['customparameters'] .= "\nassignment_ref=".$instance->cmid."\n";
                /**Note play_only is redundant in HML 3.1.007 onwards and will be ignored**/
                $typeconfig['customparameters'] .= "\nplay_only=Y\nno_horiz_borders=Y";
                $typeconfig['customparameters'] .= "\ntemp_assignment_ref=".helixmedia_get_assign_into_refs($instance->cmid)."\n";
                $typeconfig['customparameters'] .= "\ngroup_assignment=".helixmedia_is_group_assign($instance->cmid);
                $this->text = get_string('pleasewait', 'helixmedia');
                break;
            case HML_LAUNCH_VIEW_SUBMISSIONS:
                $this->text = get_string('pleasewait', 'helixmedia');
            case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
                $typeconfig['customparameters'] .= "\nresponse_user_id=".$instance->userid;
                break;
            case HML_LAUNCH_VIEW_FEEDBACK:
                $typeconfig['customparameters'] .= "\nplay_only=Y\nno_horiz_borders=Y";
                $this->text = get_string('pleasewait', 'helixmedia');
                break;
            case HML_LAUNCH_FEEDBACK:
                $this->text = get_string('pleasewaitup', 'helixmedia');
                break;
        }
        if ($ref > -1) {
            $typeconfig['customparameters'] .= "\nvideo_ref=".$ref;
        }

        if ($ishtmlassign) {
            $typeconfig['customparameters'] .= "\nmoodlehtmlassign=Y";
        }

        $typeconfig['customparameters'] .= "\nlaunch_type=".$type;
        $typeconfig['acceptgrades'] = 0;
        $typeconfig['allowroster'] = 1;
        $typeconfig['forcessl'] = '0';
        $typeconfig['launchcontainer'] = $modconfig->default_launch;

        // Default the organizationid if not specified.
        if (!empty($modconfig->org_id)) {
            $typeconfig['organizationid'] = $modconfig->org_id;
        } else {
            $urlparts = parse_url($CFG->wwwroot);
            $typeconfig['organizationid'] = $urlparts['host'];
        }

        $this->endpoint = trim($modconfig->launchurl);

        $orgid = $typeconfig['organizationid'];

        $course = $DB->get_record("course", array("id" => $instance->course));
        $requestparams = helixmedia_build_request($instance, $typeconfig, $course, $type, $user, $modtype);
        $launchcontainer = lti_get_launch_container($instance, $typeconfig);

        if ($orgid) {
            $requestparams["tool_consumer_instance_guid"] = $orgid;
        }

        $this->params = lti_sign_parameters($requestparams, $this->endpoint, "POST", $modconfig->consumer_key, $modconfig->shared_secret);

        if (isset($instance->debuglaunch)) {
            $this->debuglaunch = ( $instance->debuglaunch == 1 );
            // Moodle 2.8 strips this out at the form submission stage, so this needs to be added after the request
            // is signed in 2.8 since the remote server will never see this parameter.
            if ($this->debuglaunch) {
                $submittext = get_string('press_to_submit', 'lti');
                $this->params['ext_submit'] = $submittext;
            }
        } else {
            $this->debuglaunch = false;
        }
    }


    public function export_for_template(renderer_base $output) {
        $data = [
            'launchcode' => lti_post_launch_html($this->params, $this->endpoint, $this->debuglaunch),
            'postscript' => $this->postscript,
            'preid' => $this->preid,
            'pleasewait' => !$this->debuglaunch,
            'text' => $this->text,
            'size' => $this->size,
            'legacyjsresize' => $this->legacyjsresize
        ];
        return $data;
    }
}

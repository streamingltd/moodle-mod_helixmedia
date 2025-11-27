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
class launcherbase {
    /**
     * @var true if we need to include the script that triggers after page load
     *      to post a message back to the parent frame with the resource link id.
     */
    protected $postscript;

    /**
     * @var The Resource Link ID
     */
    protected $preid;

    /**
     * @var Text to display while loading
     */
    protected $text;

    /**
     * @var Use the legacy resize method - for backwards compatibility with old embeds
     */
    protected $legacyjsresize;

    /**
     * @var The launch URL to use
     */
    protected $endpoint;

    /**
     * @var Should we force debugging on.
     */
    protected $debuglaunch;

    /**
     * @var Contains the signed LTI parameters
     */
    protected $params;

    /**
     * Constructor.
     *
     * @param object $instance The helixmedia instance.
     * @param int $type The Helix Launch Type
     * @param string $ret The return URL to set for the modal dialogue
     * @param int $user The Users
     * @param string $modtype The module type, use to check if we can use the more permissive
     * @param bool $postscript true if we need to include the script that triggers after page load to post a message
     *                    back to the parent frame with the resource link id.
     * @param bool $legacyjsresize Use the legacy resize method - for backwards compatibility with old embeds
     * @param bool $ishtmlassign If this is an ATTO/Tiny launch from a student submission
     */
    protected function __construct(
        $instance,
        $type,
        $ret,
        $user,
        $modtype,
        $postscript,
        $legacyjsresize = false,
        $ishtmlassign = false
    ) {
        $this->postscript = $postscript;
        $this->preid = $instance->preid;
        $this->legacyjsresize = $legacyjsresize;
    }

    /**
     * Gets the type config
     *
     * @param object $instance The helixmedia instance.
     * @param int $type The Helix Launch Type
     * @param int $version The plugin version
     * @param bool $ishtmlassign If this is an ATTO/Tiny launch from a student submission
     * @param object $modconfig Plugin config
     * @return array
     */
    protected function gettypeconfig($instance, $type, $version, $ishtmlassign, $modconfig) {
        global $CFG;
        $typeconfig = (array)$instance;
        $typeconfig['sendname'] = $modconfig->sendname;
        $typeconfig['sendemailaddr'] = $modconfig->sendemailaddr;
        $typeconfig['messagetype'] = 'basic-lti-launch-request';
        $custom = helixmedia_split_custom_parameters($modconfig->custom_params);
        $custom['custom_hml_version'] = strval($version);
        $typeconfig['loadingtext'] = false;

        switch ($type) {
            case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
            case HML_LAUNCH_THUMBNAILS:
            case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
            case HML_LAUNCH_FEEDBACK_THUMBNAILS:
            case HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS:
                // For MEDIAL 8.0.07 and higher we can use a responsive thumbnail.
                $custom['custom_thumbnail'] = 'Y';
                if ($modconfig->medialversion >= 80007) {
                    $custom['custom_thumbnail_width'] = '-1';
                    $custom['custom_thumbnail_height'] = '-1';
                } else {
                    $custom['custom_thumbnail_width'] = '230';
                    $custom['custom_thumbnail_height'] = '129';
                }
                break;
        }

        switch ($type) {
            case HML_LAUNCH_NORMAL:
            case HML_LAUNCH_TINYMCE_VIEW:
            case HML_LAUNCH_ATTO_VIEW:
                $custom['custom_view_only'] = 'Y';
                $custom['custom_play_only'] = 'Y';
                $typeconfig['loadingtext'] = get_string('pleasewait', 'helixmedia');
                break;
            case HML_LAUNCH_EDIT:
            case HML_LAUNCH_TINYMCE_EDIT:
            case HML_LAUNCH_ATTO_EDIT:
                $custom['custom_link_response'] = 'Y';
                $typeconfig['loadingtext'] = get_string('pleasewaitup', 'helixmedia');
                $typeconfig['messagetype'] = $this->message_type();
                break;
            case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
                // Nothing to do here.
                $custom['custom_play_only'] = 'Y';
                break;
            case HML_LAUNCH_STUDENT_SUBMIT:
                $custom['custom_link_response'] = 'Y';
                $custom['custom_link_type'] = 'Assignment';
                $custom['custom_assignment_ref'] = strval($instance->cmid);
                $custom['custom_temp_assignment_ref'] = helixmedia_get_assign_into_refs($instance->cmid);
                $custom['custom_group_assignment'] = helixmedia_is_group_assign($instance->cmid);
                $typeconfig['messagetype'] = $this->message_type();

                $typeconfig['loadingtext'] = get_string('pleasewaitup', 'helixmedia');
                break;
            case HML_LAUNCH_STUDENT_SUBMIT_PREVIEW:
                $custom['custom_link_type'] = 'Assignment';
                $custom['custom_assignment_ref'] = strval($instance->cmid);
                $custom['custom_temp_assignment_ref'] = helixmedia_get_assign_into_refs($instance->cmid);
                $custom['custom_group_assignment'] = helixmedia_is_group_assign($instance->cmid);
                $custom['custom_play_only'] = 'Y';

                $typeconfig['loadingtext'] = get_string('pleasewait', 'helixmedia');
                break;
            case HML_LAUNCH_VIEW_SUBMISSIONS:
                $typeconfig['loadingtext'] = get_string('pleasewait', 'helixmedia');
                // Fall through here because we want the following custom param as well.
            case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
                $custom['custom_response_user_id'] = strval($instance->userid);
                break;
            case HML_LAUNCH_VIEW_FEEDBACK:
                $custom['custom_play_only'] = 'Y';
                $typeconfig['loadingtext'] = get_string('pleasewait', 'helixmedia');
                break;
            case HML_LAUNCH_FEEDBACK:
                $typeconfig['loadingtext'] = get_string('pleasewaitup', 'helixmedia');
                $typeconfig['messagetype'] = $this->message_type();
                break;
        }

        if ($ishtmlassign) {
            $custom['custom_moodlehtmlassign'] = 'Y';
        }

        $custom['custom_launch_type'] = strval($type);
        if (!empty($instance->custom)) {
            $ec = json_decode($instance->custom);
            if ($ec) {
                foreach ($ec as $k => $v) {
                    $custom['custom_' . $k] = $v;
                }
            }
        }

        $typeconfig['customparameters'] = $custom;
        $typeconfig['acceptgrades'] = 0;
        $typeconfig['allowroster'] = 1;
        $typeconfig['forcessl'] = '0';
        $typeconfig['launchcontainer'] = $modconfig->default_launch;
        $typeconfig['ltiversion'] = $modconfig->ltiversion;

        // Default the organizationid if not specified.
        if (!empty($modconfig->org_id)) {
            $typeconfig['organizationid'] = $modconfig->org_id;
        } else {
            $urlparts = parse_url($CFG->wwwroot);
            $typeconfig['organizationid'] = $urlparts['host'];
        }
        return $typeconfig;
    }

    /**
    * Gets the Lti message type to send with a launch
    * @return string
    **/
    protected function message_type() {
        throw new \Exception("Must be implemented in sub class");
    }

    /**
     * This function builds the request that must be sent to the tool producer
     *
     * @param object    $instance       HML instance object
     * @param object    $typeconfig     HML tool configuration
     * @param object    $course         Course object
     * @param int       $type           The launch type
     * @param object    $user           User object if the launch isn't for the current user
     * @param boolean   $modtype          Set to true if we are in a modtype
     *
     * @return array    $request        Request details
     */
    protected function build_request($instance, $typeconfig, $course, $type, $user = null, $modtype = "") {
        global $USER, $CFG;

        if ($user == null) {
            $user = $USER;
        }

        if (empty($instance->cmid)) {
            $instance->cmid = 0;
        }

        $role = $this->get_ims_role($user, $instance->cmid, $course->id, $type, $modtype);

        $requestparams = [
            'user_id' => strval($user->id),
            'roles' => $role,
            'context_id' => strval($course->id),
            'context_label' => trim(html_to_text($course->shortname, 0)),
            'context_title' => trim(html_to_text($course->fullname, 0)),
            'launch_presentation_locale' => current_language(),
        ];

        if (!empty($instance->name)) {
            $requestparams[$this->title_claim_name($typeconfig['messagetype'])] = trim(html_to_text($instance->name, 0));
        }

        if (!empty($instance->intro)) {
            // We need to always use CRLF line endings for LTI, otherwise the signature validation will fail.
            // Moodle backup/restore sometimes converts the line endings to LF only.
            // The Moodle core code uses a straight str_replace of \n with \r\n
            // which won't cope properly with text where the line endings have been mixed up and \r only from the mac.
            $intro = html_to_text($instance->intro);
            $intro = preg_replace('/\r\n|\r|\n/', "\r\n", $intro);

            $requestparams[$this->desc_claim_name($typeconfig['messagetype'])] = substr($intro, 0, 1000);
        }

        if (!empty($instance->preid) && $instance->preid != -1) {
            $requestparams[$this->id_claim_name($typeconfig['messagetype'])] = strval($instance->preid);
        }

        if ($course->format == 'site') {
            $requestparams['context_type'] = 'Group';
        } else {
            $requestparams['context_type'] = 'CourseSection';
            $requestparams['lis_course_section_sourcedid'] = strval($course->idnumber);
        }

        // Send user's name and email data if appropriate.
        if (
            $typeconfig['sendname'] == LTI_SETTING_ALWAYS ||
             ( $typeconfig['sendname'] == LTI_SETTING_DELEGATE && $instance->instructorchoicesendname == LTI_SETTING_ALWAYS )
        ) {
            $requestparams['lis_person_name_given'] = $user->firstname;
            $requestparams['lis_person_name_family'] = $user->lastname;
            $requestparams['lis_person_name_full'] = $user->firstname . " " . $user->lastname;
        }

        if (
            $typeconfig['sendemailaddr'] == LTI_SETTING_ALWAYS ||
            ($typeconfig['sendemailaddr'] == LTI_SETTING_DELEGATE &&
            $instance->instructorchoicesendemailaddr == LTI_SETTILTI_VERSION_1P3NG_ALWAYS )
        ) {
            $requestparams['lis_person_contact_email_primary'] = $user->email;
        }

        $requestparams = array_merge($typeconfig['customparameters'], $requestparams);

        // Make sure we let the tool know what LMS they are being called from.
        $requestparams["ext_lms"] = "moodle-2";
        $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
        $requestparams['tool_consumer_info_version'] = strval($CFG->version);

        // Add oauth_callback to be compliant with the 1.0A spec.
        $requestparams['oauth_callback'] = 'about:blank';

        $requestparams['lti_version'] = $typeconfig['ltiversion'];
        $requestparams['lti_message_type'] = $typeconfig['messagetype'];

        return $requestparams;
    }

    /**
     * Gets the claim name for the resource link title
     * @param string $messagetype
     * @return string
     */
    protected function title_claim_name($messagetype) {
        return 'resource_link_title';
    }

    /**
     * Gets the claim name for the resource link description
     * @param string $messagetype
     * @return string
     */
    protected function desc_claim_name($messagetype) {
        return 'resource_link_description';
    }

    /**
     * Gets the claim name for the resource link id
     * @param string $messagetype
     * @return string
     */
    protected function id_claim_name($messagetype) {
        return 'resource_link_id';
    }

    /**
     * Gets the IMS role string for the specified user and Helixmedia course module.
     *
     * @param mixed $user User object or user id
     * @param int $cmid The course module id of the LTI activity
     * @param int $courseid The course id
     * @param int $type The launch type
     * @param boolean $modtype Set to true if we are in a modtype
     *
     * @return string A role string suitable for passing with an LTI launch
     */
    private function get_ims_role($user, $cmid, $courseid, $type, $modtype) {
        $roles = [];

        $coursecontext = \context_course::instance($courseid);
        if (empty($cmid) || $cmid == -1) {
            // If no cmid is passed, check if the user is a teacher in the course
            // This allows other modules to programmatically "fake" a launch without
            // a real Helixmedia instance.

            if (has_capability('moodle/course:manageactivities', $coursecontext)) {
                array_push($roles, 'Instructor');
            } else {
                array_push($roles, 'Learner');
            }
        } else {
            if (has_capability('mod/helixmedia:manage', $coursecontext)) {
                array_push($roles, 'Instructor');
            } else {
                array_push($roles, 'Learner');
            }
        }

        if (is_siteadmin($user)) {
            array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator');
        }

        return join(',', $roles);
    }
}

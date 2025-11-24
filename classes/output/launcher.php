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
class launcher extends launcherbase implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param object $instance The helixmedia instance.
     * @param int $type The Helix Launch Type
     * @param string $ret The return URL to set for the modal dialogue
     * @param object $user The user
     * @param string $modtype The module type, use to check if we can use the more permissive
     * @param bool $postscript true if we need to include the script that triggers after page load to post a message
     *                    back to the parent frame with the resource link id.
     * @param bool $legacyjsresize Use the legacy resize method - for backwards compatibility with old embeds
     * @param bool $ishtmlassign If this is an ATTO/Tiny launch from a student submission
     */
    public function __construct(
        $instance,
        $type,
        $ret,
        $user,
        $modtype,
        $postscript,
        $legacyjsresize = false,
        $ishtmlassign = false
    ) {
        global $CFG, $DB;

        parent::__construct($instance, $type, $ret, $user, $modtype, $postscript, $legacyjsresize, $ishtmlassign);

        $modconfig = get_config("helixmedia");

        if (property_exists($instance, "version")) {
            $version = $instance->version;
        } else {
            $version = get_config('mod_helixmedia', 'version');
        }

        // Check to see if the DB has duplicate preid's for the assignment submission, if it does send an
        // old version number to trigger the fix for this problem. The check doesn't need to be exhaustive.
        // Either the whole lot will match, or none will.
        if ($type == HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS || $type == HML_LAUNCH_VIEW_SUBMISSIONS) {
            $ass = $DB->get_record("course_modules", ["id" => $instance->cmid]);
            $recs = $DB->get_records("assignsubmission_helixassign", ["assignment" => $ass->instance]);
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
        $typeconfig = $this->gettypeconfig($instance, $type, $version, $ishtmlassign, $modconfig);

        $this->endpoint = trim($modconfig->launchurl);

        $orgid = $typeconfig['organizationid'];

        $course = $DB->get_record("course", ["id" => $instance->course]);
        $requestparams = $this->build_request($instance, $typeconfig, $course, $type, $user, $modtype);

        if ($orgid) {
            $requestparams["tool_consumer_instance_guid"] = $orgid;
        }

        $this->params = lti_sign_parameters(
            $requestparams,
            $this->endpoint,
            "POST",
            $modconfig->consumer_key,
            $modconfig->shared_secret
        );

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

        $this->text = $typeconfig['loadingtext'];
    }

    /**
     * Exports data for rendering
     * @param renderer_base $output The renderer
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'launchcode' => lti_post_launch_html($this->params, $this->endpoint, $this->debuglaunch),
            'postscript' => $this->postscript,
            'preid' => $this->preid,
            'pleasewait' => !$this->debuglaunch,
            'text' => $this->text,
            'legacyjsresize' => $this->legacyjsresize,
        ];
        return $data;
    }
}

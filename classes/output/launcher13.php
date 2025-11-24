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
 * @copyright  2025 Streaming Ltd
 * @author Tim Williams <tim@medial.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launcher13 extends launcherbase implements renderable, templatable {
    /**
     * @var Launch params
     */
    protected $params;

    /**
     * @var LTI Tool url
     */
    protected $toolurl;

    /**
     * @var Is this a debug launch
     */
    protected $debuglaunch;

    /**
     * @var Debug launch params
     */
    protected $debugparams;

    /**
     * Constructor.
     *
     * @param object $hmli The helixmedia instance.
     * @param string $type The Helix Launch Type
     * @param string $ret The return URL to set for the modal dialogue
     * @param int $userid The user id
     * @param string $modtype The module type, use to check if we can use the more permissive
     * @param bool $postscript true if we need to include the script that triggers after page load to post a message
     *                    back to the parent frame with the resource link id.
     * @param bool $legacyjsresize Use the legacy resize method - for backwards compatibility with old embeds
     * @param bool $ishtmlassign If this is an ATTO/Tiny launch from a student submission
     * @param string $nonce LTI 1.3 nonce
     * @param string $state LTI 1.3 launch state
     */
    public function __construct(
        $hmli,
        $type,
        $ret,
        $userid,
        $modtype,
        $postscript,
        $legacyjsresize = false,
        $ishtmlassign = false,
        $nonce = false,
        $state = ''
    ) {
        global $CFG, $DB;

        parent::__construct($hmli, $type, $ret, $userid, $modtype, $postscript, $legacyjsresize, $ishtmlassign);

        $modconfig = get_config("helixmedia");
        $this->toolurl = $modconfig->launchurl . '/LtiAdv/Tool/' . $modconfig->guid;
        $this->debuglaunch = $hmli->debuglaunch;

        $typeconfig = $this->gettypeconfig($hmli, $type, get_config('mod_helixmedia', 'version'), $ishtmlassign, $modconfig);
        $this->endpoint = trim($modconfig->launchurl);

        $orgid = $typeconfig['organizationid'];

        $course = $DB->get_record("course", ["id" => $hmli->course]);
        $user = $DB->get_record("user", ["id" => $userid]);
        $requestparams = $this->build_request($hmli, $typeconfig, $course, $type, $user, $modtype);

        if ($orgid) {
            $requestparams["tool_consumer_instance_guid"] = $orgid;
        }

        $returnurlparams = [
            'course' => $course->id,
            'sesskey' => sesskey(),
        ];
        $returnurl = new \moodle_url('/mod/helixmedia/contentitem_return.php', $returnurlparams);
        if (strtolower($returnurl->get_scheme()) === 'http') {
            $returnurl->set_scheme('https');
        }
        $requestparams['content_item_return_url'] = $returnurl->out(false);
        $requestparams['accept_types'] = 'ltiResourceLink';

        // Add in the services.
        foreach (helixmedia_get_services() as $service) {
            $service->add_launch_parameters($requestparams, $hmli);
        }

        // Add in video_ref if supplied, this happens for LTI 1.3 content selection.
        if (property_exists($hmli, 'video_ref') && $hmli->video_ref !== '') {
            $requestparams['custom_video_ref'] = strval($hmli->video_ref);
        }

        $requestparams['data'] = json_encode(['launchtype' => $type, 'modtype' => $modtype]);

        $this->text = $typeconfig['loadingtext'];
        $this->params = [];

        $jwt = lti_sign_jwt($requestparams, $this->endpoint, $modconfig->clientid, 0, $nonce);
        $this->params[] = $this->get_param('id_token', $jwt['id_token']);
        if (isset($state)) {
            $this->params[] = $this->get_param('state', $state);
        }

        $this->debugparams = [];
        if ($this->debuglaunch) {
            $requestparams['nonce'] = $nonce;
            $requestparams['state'] = $state;
            foreach ($requestparams as $key => $value) {
                $p = new \stdclass();
                $p->key = $key;
                $p->value = $value;
                $this->debugparams[] = $p;
            }
        }
    }

    /**
     * Gets a param as a stdclass
     * @param string $key
     * @param string $value
     * @return stdclass
     */
    private function get_param($key, $value) {
        $p = new \stdclass();
        $p->key = $key;
        $p->value = $value;
        return $p;
    }

    /**
     * Exports data for rendering
     * @param renderer_base $output The renderer
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = [
            'url' => $this->toolurl,
            'params' => $this->params,
            'name' => 'ltiAuthForm',
            'debuglaunch' => $this->debuglaunch,
            'debugparams' => $this->debugparams,
            'text' => $this->text,
            'postscript' => $this->postscript,
            'preid' => $this->preid,
        ];
        return $data;
    }

    /**
     * Gets the claim name for the resource link title
     * @param string $messagetype LTI message type
     * @return string
     */
    protected function title_claim_name($messagetype) {
        if ($messagetype == 'ContentItemSelectionRequest') {
            return 'title';
        }
        return 'resource_link_title';
    }

    /**
     * Gets the claim name for the resource link description
     * @param string $messagetype LTI message type
     * @return string
     */
    protected function desc_claim_name($messagetype) {
        if ($messagetype == 'ContentItemSelectionRequest') {
            return 'text';
        }
        return 'resource_link_description';
    }

    /**
     * Gets the claim name for the resource link id
     * @param string $messagetype LTI message type
     * @return string
     */
    protected function id_claim_name($messagetype) {
        if ($messagetype == 'ContentItemSelectionRequest') {
            return 'custom_resource_link_id';
        }
        return 'resource_link_id';
    }
}

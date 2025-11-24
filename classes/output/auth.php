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

require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * Search form renderable.
 *
 * @package    mod_helixmedia
 * @copyright  2025 Streaming Ltd
 * @author     Tim Williams <tim@medial.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Container renderable class.
 *
 * @package    mod_helixmedia
 * @copyright  2025 Streaming Ltd
 * @author     Tim Williams <tim@medial.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth implements renderable, templatable {
    /**
     * @var Launch params
     */
    private $params;

    /**
     * @var LTI login url
     */
    private $loginurl;

    /**
     * @var Debug launch
     */
    private $debuglaunch;

    /**
     * Prepares an LTI 1.3 login request
     *
     * @param object|null    $hmli  MEDIAL launch config
     * @param object         $config    Tool type configuration
     * @param string         $type The tool type id
     * @param string         $ret
     * @param int            $user The user the launch is for
     * @param string         $modtype The module type (if this is Tiny/ATTO)
     * @param boolean        $legacyjsresize
     * @param boolean        $ishtmlassign Is this an assignment submisison using Tiny/ATTO?
     * @param int            $mobiletokenid The ID of the mobile token if the launch comes from MoodleMobile.
     * @param string|bool    $mobiletoken The mobile token string of false if not a mobile launch
     * @param bool           $postscript If we need to send the resource link ID via a javascipt message
     * @return array Login request parameters
     */
    public function __construct(
        $hmli,
        $config,
        $type,
        $ret,
        $user,
        $modtype,
        $legacyjsresize,
        $ishtmlassign,
        $mobiletokenid,
        $mobiletoken,
        $postscript
    ) {
        global $CFG, $SESSION;
        $ltihint = [];

        $endpoint = $config->launchurl . '/LtiAdv/Tool/' . $config->guid;
        $this->loginurl = $config->launchurl . '/LtiAdv/OidcLogin';
        $this->debuglaunch = $hmli->debuglaunch;

        if ($hmli->cmid > 0) {
            $launchid = 'ltilaunch' . $hmli->cmid . '_' . rand();
            $ltihint['cmid'] = $hmli->cmid;
        } else {
            $launchid = "ltilaunch_nomod_" . rand();
        }

        if ($mobiletokenid > 0 && $mobiletoken) {
            $ltihint['mobiletokenid'] = $mobiletokenid;
            $ltihint['mobiletoken'] = $mobiletoken;
        }

        $SESSION->$launchid = new \stdclass();
        $SESSION->$launchid->hmli = $hmli;
        $SESSION->$launchid->type = $type;
        $SESSION->$launchid->ret = $ret;
        $SESSION->$launchid->userid = $user->id;
        $SESSION->$launchid->modtype = $modtype;
        $SESSION->$launchid->legacyjsresize = $legacyjsresize;
        $SESSION->$launchid->ishtmlassign = $ishtmlassign;
        $SESSION->$launchid->postscript = $postscript;
        $ltihint['launchid'] = $launchid;

        $this->params = [];
        $this->params[] = $this->get_param('iss', $CFG->wwwroot);
        $this->params[] = $this->get_param('target_link_uri', $endpoint);
        $this->params[] = $this->get_param('login_hint', $user->id);
        $this->params[] = $this->get_param('lti_message_hint', json_encode($ltihint));
        $this->params[] = $this->get_param('client_id', $config->clientid);
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
     * Exports the data for rendering
     * @param renderer_base $output renderer
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = [
            'url' => $this->loginurl,
            'params' => $this->params,
            'name' => 'ltiInitiateLoginForm',
            'debuglaunch' => $this->debuglaunch,
            'text' => get_string('ltiauth', 'helixmedia'),
        ];

        return $data;
    }
}

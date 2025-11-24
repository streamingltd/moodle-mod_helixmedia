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
 * This file responds to a login authentication request
 *
 * @package    mod_helixmedia
 * @copyright  2019 Stephen Vickers, 2025 Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');
global $_POST, $_SERVER;

$scope = optional_param('scope', '', PARAM_TEXT);
$responsetype = optional_param('response_type', '', PARAM_TEXT);
$clientid = optional_param('client_id', '', PARAM_TEXT);
$redirecturi = optional_param('redirect_uri', '', PARAM_URL);
$loginhint = optional_param('login_hint', '', PARAM_TEXT);
$ltimessagehintenc = optional_param('lti_message_hint', '', PARAM_TEXT);
$state = optional_param('state', '', PARAM_TEXT);
$responsemode = optional_param('response_mode', '', PARAM_TEXT);
$nonce = optional_param('nonce', '', PARAM_TEXT);
$prompt = optional_param('prompt', '', PARAM_TEXT);

$ok = !empty($scope) && !empty($responsetype) && !empty($clientid) &&
      !empty($redirecturi) && !empty($loginhint) &&
      !empty($nonce);

if (!$ok) {
    echo 'invalid_request';
    exit;
}
$ltimessagehint = json_decode($ltimessagehintenc);

if (!isset($ltimessagehint->launchid)) {
    echo 'invalid_request';
    echo 'No launch id in LTI hint';
    exit;
}
if (($scope !== 'openid')) {
    echo 'invalid_scope';
    exit;
}
if (($responsetype !== 'id_token')) {
    echo 'unsupported_response_type';
    exit;
}

$config = get_config('helixmedia');
if (!$clientid === $config->clientid) {
    echo 'unauthorized_client';
    exit;
}

if (property_exists($ltimessagehint, 'mobiletokenid') && $ltimessagehint->mobiletokenid > 0) {
    $tokenrecord = $DB->get_record('helixmedia_mobile', ['id' => $ltimessagehint->mobiletokenid]);
    if (
        !$tokenrecord ||
        $tokenrecord->token != $ltimessagehint->mobiletoken
    ) {
        echo 'mobile_access_deined';
        exit;
    } else {
        $user = $DB->get_record('user', ['id' => $tokenrecord->userid]);
        // Session handling is unreliable with MoodleMobile. We only support launchtype 1 so just get it from the DB.
        $launchdata = new \stdclass();
        $mod = $DB->get_record('course_modules', ['id' => $tokenrecord->instance], '*', MUST_EXIST);
        $launchdata->hmli = $DB->get_record('helixmedia', ['id' => $mod->instance], '*', MUST_EXIST);
        $launchdata->hmli->cmid = $tokenrecord->instance;
        $launchdata->type = HML_LAUNCH_NORMAL;
        $launchdata->ret = '';
        $launchdata->userid = $user->id;
        $launchdata->modtype = '';
        $launchdata->legacyjsresize = false;
        $launchdata->ishtmlassign = false;
    }
} else {
    $user = $USER;

    $launchid = $ltimessagehint->launchid;
    if (!property_exists($SESSION, $launchid)) {
        echo 'launchdata_missing';
        exit;
    } else {
        $launchdata = $SESSION->$launchid;
        unset($SESSION->$launchid);
    }

    $course = $DB->get_record('course', ['id' => $launchdata->hmli->course]);
    if (!$course) {
        echo 'course_not_found';
        exit;
    }
    require_login($course);
}

if ($loginhint !== $user->id) {
    echo 'access_denied' . $loginhint . ' ' . $user->id . ' ' . $mobiletokenid;
    exit;
}

// If we're unable to load up config; we cannot trust the redirect uri for POSTing to.
if (empty($config)) {
    throw new moodle_exception('invalidrequest', 'error');
} else {
    $uris = helixmedia_redirect_urls();
    if (!in_array($redirecturi, $uris)) {
        throw new moodle_exception('invalidrequest', 'error');
    }
}

if (isset($responsemode)) {
    if (!$responsemode === 'form_post') {
        echo 'invalid_request: Invalid response_mode';
        exit;
    }
} else {
    echo 'invalid_request: Missing response_mode';
    exit;
}

if (!empty($prompt) && ($prompt !== 'none')) {
    echo 'invalid_request: Invalid prompt';
    exit;
}

// Do some permissions stuff.
$cap = helixmedia_auth_capability($launchdata->type, $launchdata->hmli->course, $launchdata->modtype);
$context = context_course::instance($launchdata->hmli->course);

$PAGE->set_context($context);
$output = $PAGE->get_renderer('mod_helixmedia');

if ($cap == null || !has_capability($cap, $context, $USER)) {
    $disp = new \mod_helixmedia\output\launchmessage(get_string('not_authorised', 'helixmedia'));
    echo $output->render($disp);
    die;
}

unset($SESSION->lti_message_hint);

$disp = new \mod_helixmedia\output\launcher13(
    $launchdata->hmli,
    $launchdata->type,
    $launchdata->ret,
    $launchdata->userid,
    $launchdata->modtype,
    $launchdata->postscript,
    $launchdata->legacyjsresize,
    $launchdata->ishtmlassign,
    $nonce,
    $state
);
echo $output->render($disp);

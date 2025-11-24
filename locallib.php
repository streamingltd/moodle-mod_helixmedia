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
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

defined('MOODLE_INTERNAL') || die;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/lib/filelib.php');


// Activity types.
define('HML_LAUNCH_NORMAL', 1);
define('HML_LAUNCH_THUMBNAILS', 2);
define('HML_LAUNCH_EDIT', 3);

// Special type for migration from the repository module. Now Redundant so disabled define('HML_LAUNCH_RELINK', 4).

// Assignment submission types.
define('HML_LAUNCH_STUDENT_SUBMIT', 5);
define('HML_LAUNCH_STUDENT_SUBMIT_PREVIEW', 17);
define('HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS', 6);
define('HML_LAUNCH_VIEW_SUBMISSIONS', 7);
define('HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS', 8);

// TinyMCE types. Do not change these values, they are embedded in the TinyMCE plugin html code.
define('HML_LAUNCH_TINYMCE_EDIT', 9);
define('HML_LAUNCH_TINYMCE_VIEW', 10);

// Submission Feedback types.
define('HML_LAUNCH_FEEDBACK', 11);
define('HML_LAUNCH_FEEDBACK_THUMBNAILS', 12);

// Submission Feedback types.
define('HML_LAUNCH_VIEW_FEEDBACK', 13);
define('HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS', 14);

// ATTO Types. Do not change these values, they are embedded in the ATTO plugin html code.
define('HML_LAUNCH_ATTO_EDIT', 15);
define('HML_LAUNCH_ATTO_VIEW', 16);

// Launch Type that shows the MEDIAL library and does not allow for content selection.
// Allows the user to manage their content. Implmented in tiny_medial plugin.
define('HML_LAUNCH_LIB_ONLY', 18);

// Note next ID should be 19.

// For version check.
define('MEDIAL_MIN_VERSION', '8.5.000');
define('MEDIAL_LTI13_MIN_VERSION', '9.0.000');

/**
 * Checks to see if a course module is a group assignment
 * @param int $cmid The course module id
 * @return bool true if this is a group assingment
 **/
function helixmedia_is_group_assign($cmid) {
    global $DB;
    $cm = $DB->get_record('course_modules', ['id' => $cmid]);
    $assign = $DB->get_record('assign', ['id' => $cm->instance]);

    if ($assign->teamsubmission) {
         return "Y";
    } else {
         return "N";
    }
}

/**
 * Get assingment reference information need by MEDIAL in the launch
 * @param int $assignid The assignment ID
 * @return string The refs as a
 */
function helixmedia_get_assign_into_refs($assignid) {
    global $DB;
    $refs = "";

    $module = $DB->get_record("course_modules", ["id" => $assignid]);

    if (!$module) {
        return "";
    }

    $assignment = $DB->get_record("assign", ["id" => $module->instance]);

    if (!$assignment) {
        return "";
    }

    $first = true;
    $pos = strpos($assignment->intro, "/mod/helixmedia/launch.php");

    while ($pos != false) {
        $l = strpos($assignment->intro, "l=", $pos);

        if ($l != false) {
            $l = $l + 2;
            $e = strpos($assignment->intro, "\"", $l);
            if ($e != false) {
                if (!$first) {
                    $refs .= ",";
                } else {
                    $first = false;
                }
                $refs .= substr($assignment->intro, $l, $e - $l);
            }
        }
        $pos = strpos($assignment->intro, "/mod/helixmedia/launch.php", $pos + 1);
    }
    return $refs;
}

/**
 * Posts an LTI launch directly to MEDIAL.
 * @param object $params The parameters to send
 * @param string $endpoint The launch url to use
 * @return string The result
 **/
function helixmedia_curl_post_launch_html($params, $endpoint) {
    global $CFG;
    $modconfig = get_config("helixmedia");
    if ($modconfig->ltiversion === LTI_VERSION_1P3) {
        $params['client_id'] = $modconfig->clientid;
    } else {
        $params['oauth_consumer_key'] = $modconfig->consumer_key;
    }

    set_time_limit(0);

    $cookiesfile = $CFG->dataroot . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR . "helixmedia-curl-cookies-" .
        microtime(true) . ".tmp";
    while (file_exists($cookiesfile)) {
        $cookiesfile = $CFG->dataroot . DIRECTORY_SEPARATOR . "temp" . DIRECTORY_SEPARATOR .
            "helixmedia-curl-cookies-" . microtime(true) . ".tmp";
    }

    $curl = new \curl();
    $curl->setopt([
        'CURLOPT_TIMEOUT' => 50,
        'CURLOPT_CONNECTTIMEOUT' => 30,
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_VERBOSE' => 1,
        'CURLOPT_FRESH_CONNECT' => true,
        'CURLOPT_FORBID_REUSE' => true,
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_COOKIESESSION' => true,
        'CURLOPT_COOKIEFILE' => $cookiesfile,
        'CURLOPT_COOKIEJAR' => $cookiesfile,
        // Enable this if we are talking to something with a self-signed cert: 'CURLOPT_SSL_VERIFYHOST' => false,.
        // Ditto: 'CURLOPT_SSL_VERIFYPEER' => false.
    ]);
    $result = $curl->post($endpoint, $params);
    $resp = $curl->get_info();
    if ($curl->get_errno() != CURLE_OK || $resp['http_code'] != 200) {
        if ($r = $curl->get_raw_response()) {
            return "<p>CURL Error connecting to MEDIAL: " . $r[0] . "</p>" .
                "<p>" . get_string("version_check_fail", "helixmedia") . "</p>";
        } else {
            return "<p>CURL Error connecting to MEDIAL: No response</p>" .
                "<p>" . get_string("version_check_fail", "helixmedia") . "</p>";
        }
    }

    if (file_exists($cookiesfile)) {
        unlink($cookiesfile);
    }

    return $result;
}

/**
 * Splits the custom parameters field to the various parameters
 *
 * @param string $customstr     String containing the parameters
 *
 * @return Array of custom parameters
 */
function helixmedia_split_custom_parameters($customstr) {
    $lines = preg_split("/[\n;]/", $customstr);
    $retval = [];
    foreach ($lines as $line) {
        $pos = strpos($line, "=");
        if ($pos === false || $pos < 1) {
            continue;
        }
        $key = trim(core_text::substr($line, 0, $pos));
        $val = trim(core_text::substr($line, $pos + 1, strlen($line)));
        $key = lti_map_keyname($key);
        $retval['custom_' . $key] = $val;
    }
    return $retval;
}

/**
 * Checks the moduletype we are viewing here to see if we can use the more permissive modtype permission
 * @param string $modtype The module type
 * @param string $edtype The editor type to look in
 * @return string The permission to use
 **/
function helixmedia_get_visiblecap($modtype = false, $edtype = 'atto/helixatto') {
    if (!$modtype) {
        return $edtype . ':visible';
    }

    global $DB;
    $config = get_config(str_replace('/', '_', $edtype), 'modtypeperm');

    // If the config is missing, return the default permission. This should only happen if this is a TinyMCE legacy plugin edit.
    if (!$config) {
        return 'mod/helixmedia:addinstance';
    }

    $types = explode("\n", $config);

    for ($i = 0; $i < count($types); $i++) {
        $types[$i] = trim($types[$i]);
        if (strlen($types[$i]) > 0 && $types[$i] == $modtype && $DB->get_record('modules', ['name' => $types[$i]])) {
            return $edtype . ':visiblemodtype';
        }
    }

    return $edtype;
}

/**
 * Gets a the URL of the current page directly from header information
 * @return string
 */
function curpageurl() {
    $pageurl = 'http';
    if (array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "on") {
        $pageurl .= "s";
    }

    $pageurl .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageurl .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageurl .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageurl;
}

/**
 * Gets the launch instance size information
 * @param object $hmli HMLI instance
 * @param int $course The Course id
 * @return object containing size information
 */
function helixmedia_get_instance_size($hmli, $course) {
    global $CFG;

    if (isset($hmli->custom)) {
        // If this is LTI 1.3 we might have custom information that tells us this in the DB.
        $custom = json_decode($hmli->custom);
        if ($custom && property_exists($custom, 'audioonly')) {
            // All new embeds are responsive, so we don't have the width/height in the data, so set responsive sizes.
            $custom->width = 0;
            $custom->height = 0;
            if (strtolower($custom->audioonly) == "true") {
                $custom->audioonly = true;
            } else {
                $custom->audioonly = false;
            }
            return $custom;
        }
    }

    $url = helixmedia_get_playerwidthurl();
    $retdata = helixmedia_curl_post_launch_html(["context_id" => $course, "resource_link_id" => $hmli->preid,
        "include_height" => "Y"], $url);

    $parts = explode(":", $retdata);
    // If there is more than one part, then MEDIAL understands the include_height param.
    if (count($parts) > 1) {
        $vals = new stdclass();
        $vals->width = intval($parts[0]);
        $vals->height = intval($parts[1]);
        if (count($parts) > 1 && $parts[2] == 'Y') {
            $vals->audioonly = true;
        } else {
            $vals->audioonly = false;
        }
        return $vals;
    }

    // Old version of MEDIAL, return standard data.
    $vals = new stdclass();
    $vals->width = intval($retdata);
    $vals->height = -1;
    $vals->audioonly = false;
    return $vals;
}

/**
 * Gets the URL to use for player width queries
 * @return string url
 */
function helixmedia_get_playerwidthurl() {
    return helixmedia_get_alturl("PlayerWidth");
}

/**
 * Gets the URL to use for session status queries
 * @return string url or false if we don't want to use session status
 */
function helixmedia_get_status_url() {
    if (get_config("helixmedia", "ltiversion") == LTI_VERSION_1P3) {
        // LTI 1.3 doesn't use session status calls because we don't have a resource link id for new resources.
        return false;
    }
    return helixmedia_get_alturl("SessionStatus");
}

/**
 * Gets the URL to use for upload status queries
 * @return string url
 */
function helixmedia_get_upload_url() {
    return helixmedia_get_alturl("UploadStatus");
}


/**
 * Gets an LTI URL with /Launch substituted
 * @param string $alt The string to substitute
 * @return string url
 */
function helixmedia_get_alturl($alt) {
    $url = trim(get_config("helixmedia", "launchurl"));
    if (get_config("helixmedia", "ltiversion") == LTI_VERSION_1P3) {
        return $url . '/LtiAdv/' . $alt;
    }
    $pos = helixmedia_str_contains(strtolower($url), "/launch", true);
    return substr($url, 0, $pos) . $alt;
}

/**
 * Gets the LTI launch URL
 * @return URL string
 **/
function helixmedial_launch_url() {
    $url = trim(get_config("helixmedia", "launchurl"));
    if (get_config("helixmedia", "ltiversion") == LTI_VERSION_1P3) {
        return $url . '/LtiAdv/Tool/' . trim(get_config("helixmedia", "guid"));
    }

    return $url;
}

/**
 * Checks if a MEDIAL resource link id has been used.
 * @param int $preid The resource link ID we are interested in
 * @param int $as Redundant (was the assignment submission)
 * @param int $userid The user who owns the media
 * @return true if the resource link id has nothing associated with it.
 **/
function helixmedia_is_preid_empty($preid, $as, $userid) {
    return !helixmedia_get_media_status($preid, $userid, true);
}

/**
 * Gets the status of the uploaded medial.
 * @param int $preid The resource link ID we are interested in
 * @param int $userid The user who owns the media
 * @param bool $statusonly true if we only want a true false upload status here
 * @return bool false if nothing has been uploaded, true or the timestamp the media was linked
 * to the resource link ID (depending on status field)
 * Note, will return a boolean if MEDIAL doesn't return a creation date.
 **/
function helixmedia_get_media_status($preid, $userid, $statusonly = false) {
    global $CFG;

    $retdata = helixmedia_curl_post_launch_html(
        ["resource_link_id" => $preid, "user_id" => $userid, "json" => "Y"],
        helixmedia_get_upload_url()
    );

    // We got a 404, the MEDIAL server doesn't support this call, so return false.
    // The old method was to check for the presence of a resource link ID so this is consistent.
    if (strpos($retdata, "HTTP 404") > 0) {
        return true;
    }

    // The MEDIAL server doesn't support the json call (Introduced with 8.0.008).
    if (strlen($retdata) == 1) {
        if ($retdata == "Y") {
            return true;
        } else {
            return false;
        }
    }

    $json = json_decode($retdata);

    // If nothing uploaded, then just return false.
    if ($json->uploadStatus == "N") {
        return false;
    }

    // If we got a Y and only want status, return true here.
    if ($statusonly && $json->uploadStatus == "Y") {
        return true;
    }

    $dt = new \DateTime($json->createdAt);
    return $dt->getTimestamp();
}

/**
 * String search convenience method
 * @param string $haystack String to search
 * @param string $needle String to look for
 * @param bool $ignorecase true to ignore case
 * @return bool false or striing position
 **/
function helixmedia_str_contains($haystack, $needle, $ignorecase = false) {
    if ($ignorecase) {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
    }
    $needlepos = strpos($haystack, $needle);
    return ($needlepos === false ? false : ($needlepos + 1));
}

/**
 * Checks the version of MEDIAL we are pointing at
 * @return string Version check information
 **/
function helixmedia_version_check() {
    $statusurl = trim(get_config("helixmedia", "launchurl"));
    if (strlen($statusurl) == 0) {
        return "<p>" . get_string("version_check_not_done", "helixmedia") . "</p>";
    }

    // Detected the correct version URL based on the launch URL rather than version specified so it works when
    // there is a mis-match.
    $pos = helixmedia_str_contains(strtolower($statusurl), "/lti/launch", true);
    if (!$pos) {
        $endpoint = $statusurl . '/Version.txt';
    } else {
        $endpoint = substr($statusurl, 0, $pos) . "/Version.txt";
    }

    $curl = new \curl();
    $curl->setopt([
        'CURLOPT_TIMEOUT' => 50,
        'CURLOPT_CONNECTTIMEOUT' => 30,
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_VERBOSE' => 1,
        'CURLOPT_FRESH_CONNECT' => true,
        'CURLOPT_FORBID_REUSE' => true,
        'CURLOPT_RETURNTRANSFER' => true,
        // For self signed cert debugging: 'CURLOPT_SSL_VERIFYHOST' => false,.
        // Ditto: 'CURLOPT_SSL_VERIFYPEER' => false.
    ]);
    $result = $curl->get($endpoint);
    $resp = $curl->get_info();
    if ($curl->get_errno() != CURLE_OK || $resp['http_code'] != 200) {
        return "<p>CURL Error connecting to MEDIAL: url:" . $endpoint . ", response is '" . $result . "'</p>" .
              "<p>" . get_string("version_check_fail", "helixmedia") . "</p>";
    }

    $v = new stdclass();
    $v->min = MEDIAL_MIN_VERSION;
    $v->actual = $result;
    $message = "<p>" . get_string('version_check_message', 'helixmedia', $v) . "</p>";

    $reqver = parse_medial_version(MEDIAL_MIN_VERSION);
    $lti13reqver = parse_medial_version(MEDIAL_LTI13_MIN_VERSION);
    $actualver = parse_medial_version($result);

    set_config('medialversion', $actualver, "helixmedia");

    if ($actualver < $reqver) {
        $message .= "<p class='warning'>" . get_string('version_check_upgrade', 'helixmedia') . "</p>";
    }

    if ($actualver < $lti13reqver) {
        set_config('lti13supported', false, "helixmedia");
        $message .= "<p>Note: LTI 1.3 support requires MEDIAL version " . MEDIAL_LTI13_MIN_VERSION . "</p>";
    } else {
        set_config('lti13supported', true, "helixmedia");
    }

    return $message;
}

/**
 * Gets the client id
 * @return string
 */
function helixmedia_get_clientid() {
    $clientid = get_config('helixmedia', 'clientid');
    if (!$clientid) {
        $clientid = random_string(15);
        set_config('clientid', $clientid, 'helixmedia');
    }
    return $clientid;
}

/**
 * Verifies the private key of a launch
 * @return bool
 */
function helixmedia_verify_private_key() {
    global $CFG;
    require_once($CFG->dirroot . '/mod/lti/upgradelib.php');
    return mod_lti_verify_private_key();
}

/**
 * Parses the MEDIAL version into a single int
 * @param string $str $str The version string
 * @return int version number
 * */
function parse_medial_version($str) {
    $parts = explode('.', $str);
    $concat = '';
    for ($loop = 0; $loop < count($parts); $loop++) {
        $concat .= $parts[$loop];
    }
    return intval($concat);
}

/**
 * handle dynamic resizing of the iframe
 * @param object $hmli The module instance
 * @param int $c
 * @return The resize code
 */
function helixmedia_legacy_dynamic_size($hmli, $c) {
    // This handles dynamic sizing of the launch frame.
    $size = helixmedia_get_instance_size($hmli, $c);

    if ($size->width == 0) {
        $ratio = 0.605;
        // If height is -1, use old size rules.
        if ($size->height == -1) {
            $ratio = 0.85;
        }
        return "<script type=\"text/javascript\">\n" .
             "var vid=parent.document.getElementById('hmlvid-" . $hmli->preid . "');\n" .
             "if (vid != null) {\n" .
             "var h=parseInt(vid.parentElement.offsetWidth*" . $ratio . ");\n" .
             "vid.style.width='100%';\n" .
             "if (h>0) {vid.style.height=h+'px';}\n" .
             "}\n" .
             "</script>\n";
    } else {
        // If height is -1, use old size rules.
        if ($size->height == -1) {
            $w = "530px";
            $h = "420px";
            if ($size->width == 640) {
                $w = "680px";
                $h = "570px";
            } else {
                if ($size->width == 835) {
                    $w = "880px";
                    $h = "694px";
                }
            }
        } else {
            if ($size->audioonly) {
                $w = $size->width . "px";
                $h = $size->height . "px";
            } else {
                $w = "380px";
                $h = "340px";
                if ($size->width == 640) {
                    $w = "680px";
                    $h = "455px";
                } else {
                    if ($size->width == 835) {
                        $w = "875px";
                        $h = "575px";
                    }
                }
            }
        }

        return "<script type=\"text/javascript\">\n" .
             "var vid=parent.document.getElementById('hmlvid-" . $hmli->preid . "');" .
             "if (vid != null) {\n" .
             "vid.style.width='" . $w . "';\n" .
             "vid.style.height='" . $h . "';\n" .
             "}\n" .
             "</script>\n";
    }
}

/**
 * Look at URL information to detect when we are in the assing grading area
 * @param string $url The url
 * @return bool true if we are grading
 */
function helixmedia_detect_assign_grading_view($url) {
    $url = parse_url($url);

    if (strpos($url['path'], '/mod/assign/') === false) {
        return false;
    }

    $query = explode('&', $url['query']);

    // These three actions equate to a tutor viewing a submission.
    if (
        strpos($url['query'], 'action=viewpluginassignsubmission') !== false ||
        strpos($url['query'], 'action=grading') !== false ||
        strpos($url['query'], 'action=grader') !== false
    ) {
        return true;
    }
    return false;
}

/**
 * Are we installed on Moodle v5+
 * @return bool true if we are
 */
function helixmedia_is_moodle_5() {
    global $CFG;
    if ($CFG->version >= 2025041400) {
        return true;
    } else {
        return false;
    }
}

/**
 * Gets the auth capability to use
 * @param int $type Launch type
 * @param int $courseid
 * @param string $modtype
 * @return The capability or null if not found
 */
function helixmedia_auth_capability($type, $courseid, $modtype) {
    switch ($type) {
        case HML_LAUNCH_NORMAL:
        case HML_LAUNCH_THUMBNAILS:
        case HML_LAUNCH_TINYMCE_VIEW:
        case HML_LAUNCH_ATTO_VIEW:
        case HML_LAUNCH_VIEW_FEEDBACK:
        case HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS:
            if ($courseid == SITEID) {
                return 'mod/helixmedia:myview';
            } else {
                return 'mod/helixmedia:view';
            }
        case HML_LAUNCH_EDIT:
            return 'mod/helixmedia:addinstance';
        case HML_LAUNCH_TINYMCE_EDIT:
            return helixmedia_get_visiblecap($modtype, 'tiny/medial');
        case HML_LAUNCH_ATTO_EDIT:
            return helixmedia_get_visiblecap($modtype);
        case HML_LAUNCH_STUDENT_SUBMIT:
        case HML_LAUNCH_STUDENT_SUBMIT_PREVIEW:
        case HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS:
            return 'mod/assign:submit';
        case HML_LAUNCH_VIEW_SUBMISSIONS:
        case HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS:
        case HML_LAUNCH_FEEDBACK:
        case HML_LAUNCH_FEEDBACK_THUMBNAILS:
            return 'mod/assign:grade';
        case HML_LAUNCH_LIB_ONLY:
            if ($courseid == SITEID) {
                return 'mod/helixmedia:myview';
            } else {
                return 'mod/helixmedia:view';
            }
    }
    return null;
}


/** LTI 1.3 specific stuff, modified from mod_lti **/

/**
 * Verifies the JWT signature of an incoming message.
 *
 * @param stdclass $tool The tool config
 * @param string $clientid The client id.
 * @param string $jwtparam JWT parameter value.
 * @return true if the key validates
 * @throws moodle_exception
 * @throws UnexpectedValueException     Provided JWT was invalid
 * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
 * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
 * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
 * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
 */
function helixmedia_verify_jwt_signature($tool, $clientid, $jwtparam) {
    $key = $tool->clientid ?? '';

    if ($clientid !== $key) {
        throw new moodle_exception('errorincorrectconsumerkey', 'mod_lti');
    }

    $keyseturl = $tool->launchurl . '/LtiAdv/JWK/' . $tool->guid;
    lti_verify_with_keyset($jwtparam, $keyseturl, $clientid);
    return true;
}

/**
 * Gets the permissable scopes for MEDIAL LTI tokens
 * @return An array of scopes
 **/
function helixmedia_get_permitted_service_scopes() {
    return ['https://purl.imsglobal.org/spec/lti-ags/scope/score'];
}

/**
 * Create a new access token.
 *
 * @param string[] $scopes Scopes permitted for new token
 *
 * @return stdClass Access token
 */
function helixmedia_new_access_token($scopes) {
    global $DB;

    // Make sure the token doesn't exist (even if it should be almost impossible with the random generation).
    $numtries = 0;
    do {
        $numtries++;
        $generatedtoken = md5(uniqid(rand(), 1));
        if ($numtries > 5) {
            throw new moodle_exception('Failed to generate MEDIAL LTI access token');
        }
    } while ($DB->record_exists('helixmedia_access_tokens', ['token' => $generatedtoken]));
    $newtoken = new stdClass();
    $newtoken->scope = json_encode(array_values($scopes));
    $newtoken->token = $generatedtoken;

    $newtoken->timecreated = time();
    $newtoken->validuntil = $newtoken->timecreated + LTI_ACCESS_TOKEN_LIFE;
    $newtoken->lastaccess = null;

    $DB->insert_record('helixmedia_access_tokens', $newtoken);

    return $newtoken;
}

/**
 *
 * @param string[] $scopes  Array of scopes which give permission for the current request.
 *
 * @return string|int|boolean  The OAuth consumer key, the LTI type ID for the validated bearer token,
                               true for requests not requiring a scope, otherwise false.
 */
function helixmedia_get_oauth_key_from_headers($scopes = null) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/lti/OAuth.php');
    $now = time();

    $requestheaders = \moodle\mod\lti\OAuthUtil::get_headers();

    if (isset($requestheaders['Authorization'])) {
        if (substr($requestheaders['Authorization'], 0, 6) == "OAuth ") {
            $headerparameters = OAuthUtil::split_header($requestheaders['Authorization']);

            return format_string($headerparameters['oauth_consumer_key']);
        } else if (empty($scopes)) {
            return true;
        } else if (substr($requestheaders['Authorization'], 0, 7) == 'Bearer ') {
            $tokenvalue = trim(substr($requestheaders['Authorization'], 7));
            $conditions = ['token' => $tokenvalue];

            $token = $DB->get_record('helixmedia_access_tokens', $conditions);
            if ($token) {
                // Log token access.
                $DB->set_field('helixmedia_access_tokens', 'lastaccess', $now, ['id' => $token->id]);
                $permittedscopes = json_decode($token->scope);
                if ((intval($token->validuntil) > $now) && !empty(array_intersect($scopes, $permittedscopes))) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Gets the valid redirect URIs
 * @return array of URIs
 **/
function helixmedia_redirect_urls() {
    return [helixmedial_launch_url()];
}


/**
 * Return the mapping for standard message parameters to JWT claim.
 *
 * @return array
 */
function helixmedia_get_jwt_claim_mapping() {
    $mappings = lti_get_jwt_claim_mapping();
    // The data claim mapping for a deep linking is missing from the default mappings, perhaps because Moodle doesn't use it.
    // MEDIAL uses this to echo back important data so we know what to do at the end of content selection, so add it in so we can
    // read it.
    $mappings['dl-data'] = ['suffix' => 'dl', 'group' => '', 'claim' => 'data', 'isarray' => false];
    return $mappings;
}

/**
 * Verfies the JWT and converts its claims to their equivalent message parameter.
 *
 * @param string $jwtparam   JWT parameter
 *
 * @return array  message parameters
 * @throws moodle_exception
 */
function helixmedia_convert_from_jwt($jwtparam) {
    $params = [];
    $parts = explode('.', $jwtparam);
    $ok = (count($parts) === 3);
    if ($ok) {
        $payload = JWT::urlsafeB64Decode($parts[1]);
        $claims = json_decode($payload, true);
        $ok = !is_null($claims) && !empty($claims['iss']);
    }

    if ($ok) {
        $toolconfig = get_config('helixmedia');
        helixmedia_verify_jwt_signature($toolconfig, $claims['iss'], $jwtparam);
        $params['oauth_consumer_key'] = $claims['iss'];
        foreach (helixmedia_get_jwt_claim_mapping() as $key => $mapping) {
            $claim = LTI_JWT_CLAIM_PREFIX;
            if (!empty($mapping['suffix'])) {
                $claim .= "-{$mapping['suffix']}";
            }
            $claim .= '/claim/';
            if (is_null($mapping['group'])) {
                $claim = $mapping['claim'];
            } else if (empty($mapping['group'])) {
                $claim .= $mapping['claim'];
            } else {
                $claim .= $mapping['group'];
            }

            if (isset($claims[$claim])) {
                $value = null;
                if (empty($mapping['group'])) {
                    $value = $claims[$claim];
                } else {
                    $group = $claims[$claim];
                    if (is_array($group) && array_key_exists($mapping['claim'], $group)) {
                        $value = $group[$mapping['claim']];
                    }
                }
                if (!empty($value) && $mapping['isarray']) {
                    if (is_array($value)) {
                        if (is_array($value[0])) {
                            $value = json_encode($value);
                        } else {
                            $value = implode(',', $value);
                        }
                    }
                }
                if (!is_null($value) && is_string($value) && (strlen($value) > 0)) {
                    $params[$key] = $value;
                }
            }
            $claim = LTI_JWT_CLAIM_PREFIX . '/claim/custom';
            if (isset($claims[$claim])) {
                $custom = $claims[$claim];
                if (is_array($custom)) {
                    foreach ($custom as $key => $value) {
                        $params["custom_{$key}"] = $value;
                    }
                }
            }
            $claim = LTI_JWT_CLAIM_PREFIX . '/claim/ext';
            if (isset($claims[$claim])) {
                $ext = $claims[$claim];
                if (is_array($ext)) {
                    foreach ($ext as $key => $value) {
                        $params["ext_{$key}"] = $value;
                    }
                }
            }
        }
    }

    if (isset($params['content_items'])) {
        $params['content_items'] = lti_convert_content_items($params['content_items']);
    }
    $messagetypemapping = lti_get_jwt_message_type_mapping();
    if (isset($params['lti_message_type']) && array_key_exists($params['lti_message_type'], $messagetypemapping)) {
        $params['lti_message_type'] = $messagetypemapping[$params['lti_message_type']];
    }
    return $params;
}


/**
 * Processes the tool provider's response to the ContentItemSelectionRequest and builds the configuration data from the
 * selected content item. This configuration data can be then used when adding a tool into the course.
 *
 * @param string $messagetype The value for the lti_message_type parameter.
 * @param string $consumerkey The consumer key.
 * @param string $contentitemsjson The JSON string for the content_items parameter.
 * @return stdClass The array of module information objects.
 * @throws moodle_exception
 * @throws lti\OAuthException
 */
function helixmedia_process_content_item($messagetype, $consumerkey, $contentitemsjson) {
    // Check lti_message_type. Show debugging if it's not set to ContentItemSelection.
    // No need to throw exceptions for now since lti_message_type does not seem to be used in this processing at the moment.
    if ($messagetype !== 'ContentItemSelection') {
        debugging(
            "lti_message_type is invalid: {$messagetype}. It should be set to 'ContentItemSelection'.",
            DEBUG_DEVELOPER
        );
    }

    $items = json_decode($contentitemsjson);
    if (empty($items)) {
        throw new moodle_exception('errorinvaliddata', 'mod_lti', '', $contentitemsjson);
    }
    if (!isset($items->{'@graph'}) || !is_array($items->{'@graph'})) {
        throw new moodle_exception('errorinvalidresponseformat', 'mod_lti');
    }

    $typeconfig = get_config('helixmedia');
    if ($typeconfig->clientid != $consumerkey) {
        throw new moodle_exception('invalidclientid', 'mod_helixmedia');
    }

    $items = $items->{'@graph'};

    // MEDIAL only ever returns one item here.
    if (count($items) == 0) {
        return false;
    }

    return reset($items);
}


/**
 * Initializes an array with the services supported by the LTI module
 *
 * @return array List of services
 */
function helixmedia_get_services() {
    $services = [];
    $services[] = new \mod_helixmedia\services\gradebookservices();
    return $services;
}

/**
 * Gets a token for access via the Moodle Mobile app
 * @param int $cmid The Course module instance the token is for
 * @param int $userid The user ID this is for
 * @param int $courseid The course ID this is for
 * @return array The token string and id
 */
function helixmedia_get_mobile_token($cmid, $userid, $courseid) {
    global $DB;
    $DB->delete_records("helixmedia_mobile", ['instance' => $cmid, 'userid' => $userid, 'course' => $courseid]);
    $token = helixmedia_random_code(40);
    $tokenid = $DB->insert_record("helixmedia_mobile", [
        'instance' => $cmid,
        'userid' => $userid,
        'course' => $courseid,
        'token' => $token,
        'timecreated' => time(),
        ]);

    return [$token, $tokenid];
}

/**
 * Generates a random code
 * @param int $length The number of chars to return
 * @return A string
 */
function helixmedia_random_code($length) {
    $chars = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $clen   = strlen($chars) - 1;
    $id  = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[mt_rand(0, $clen)];
    }
    return $id;
}

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
 * This page contains the global config for the HML activity
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams for Streaming LTD
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/helixmedia/lib.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

if (optional_param('section', null, PARAM_ALPHA) == 'modsettinghelixmedia') {
    require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');
    $settings->add(new admin_setting_heading(
        'helixmedia/version_check',
        get_string("version_check_title", "mod_helixmedia"),
        helixmedia_version_check()
    ));
}

$hosturl = get_config('helixmedia', 'hosturl');
if (!$hosturl) {
    set_config('hosturl', $CFG->wwwroot, 'helixmedia');
} else {
    if (optional_param('section', null, PARAM_ALPHA) == 'modsettinghelixmedia' && $hosturl != $CFG->wwwroot) {
        if ($hosturl == 'ignorewww') {
            $warning = '<div class="alert alert-warning" role="alert">' .
                get_string("ignorewwwsitewarning", "helixmedia") .
                '</div>';
        } else {
            $warning = '<div class="alert alert-warning" role="alert">' .
                get_string("sitewarning", "helixmedia", ['old' => $hosturl, 'new' => $CFG->wwwroot]) .
                '</div><p>';

            switch (get_config('helixmedia', 'ltiversion')) {
                case LTI_VERSION_1:
                    $warning .= get_string('sitewarning10', 'helixmedia');
                    break;
                case LTI_VERSION_1P3:
                    $warning .= get_string('sitewarning13', 'helixmedia') .
                        "<div class='border rounded pb-2 pt-2 pl-3 pr-3 d-inline-block mb-3 text-dark bg-light'>" .
                        "DELETE FROM " . $CFG->prefix . "config_plugins WHERE plugin = 'helixmedia' AND name = 'clientid';" .
                        "</div>";
                    break;
            }
            $warning .= "</p><p>" . get_string('sitewarningremove', 'helixmedia') . "</p>";
        }

        $warning .= "<div class='border rounded pb-2 pt-2 pl-3 pr-3 d-inline-block mb-3 text-dark bg-light'>" .
            "UPDATE " . $CFG->prefix . "config_plugins SET value = '" . $CFG->wwwroot .
            "' WHERE plugin = 'helixmedia' AND name = 'clientid';" .
            "</div>";

        $settings->add(new admin_setting_heading('helixmedia/lti13warn', get_string("sitewarningheader", "helixmedia"), $warning));
    }
}

$keycheck = helixmedia_verify_private_key();
if (!empty($keycheck)) {
    $settings->add(new admin_setting_description(
        'helixmedia/lti13keywarn',
        '',
        '<div class="alert alert-danger">' . get_string("lti13keywarn", "helixmedia") . '</div>' . $warning
    ));
}

$settings->add(new admin_setting_heading('helixmedia/settings_header', get_string("lti_settings_title", "mod_helixmedia"), ''));

$options = [
    LTI_VERSION_1 => get_string('lti10', 'helixmedia'),
];

if (get_config('helixmedia', 'lti13supported')) {
    $options[LTI_VERSION_1P3] = get_string('jwtsecurity', 'lti');
}

$settings->add(new admin_setting_configselect(
    'helixmedia/ltiversion',
    get_string('ltiversion', 'lti'),
    get_string('ltiversion_help', 'lti'),
    LTI_VERSION_1,
    $options
));

$settings->add(new admin_setting_configtext(
    'helixmedia/launchurl',
    get_string("launch_url", "helixmedia"),
    get_string("launch_url2", "helixmedia"),
    "",
    PARAM_URL
));


$settings->add(new admin_setting_configtext(
    'helixmedia/consumer_key',
    get_string("consumer_key", "helixmedia"),
    get_string("consumer_key2", "helixmedia"),
    "",
    PARAM_TEXT
));

$settings->add(new admin_setting_configpasswordunmask(
    'helixmedia/shared_secret',
    get_string("shared_secret", "helixmedia"),
    get_string("shared_secret2", "helixmedia"),
    "",
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'helixmedia/guid',
    get_string("guid", "helixmedia"),
    get_string("guid2", "helixmedia"),
    "",
    PARAM_TEXT
));

$client = new admin_setting_description(
    'helixmedia/clientid',
    get_string("clientid", "helixmedia"),
    '<div class="mb-3"><div class="border rounded pb-2 pt-2 pl-3 pr-3 d-inline-block mb-3 text-dark' .
    ' bg-light">' . helixmedia_get_clientid() . '</div><br/ >' . get_string("clientid2", "helixmedia") . '</div>'
);

$settings->add($client);

$settings->add(new admin_setting_heading('helixmedia/other_settings_header', get_string("other_settings_title", "helixmedia"), ''));

$settings->add(new admin_setting_configtext(
    'helixmedia/org_id',
    get_string("org_id", "helixmedia"),
    get_string("org_id2", "helixmedia"),
    "",
    PARAM_TEXT
));

$launchoptions = [];
$launchoptions[LTI_LAUNCH_CONTAINER_EMBED] = get_string('embed', 'lti');
$launchoptions[LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS] = get_string('embed_no_blocks', 'lti');
$launchoptions[LTI_LAUNCH_CONTAINER_WINDOW] = get_string('new_window', 'lti');


$settings->add(new admin_setting_configselect(
    'helixmedia/default_launch',
    get_string('default_launch_container', 'lti'),
    "",
    LTI_LAUNCH_CONTAINER_EMBED_NO_BLOCKS,
    $launchoptions
));

$options = [];
$options[0] = get_string('never', 'lti');
$options[1] = get_string('always', 'lti');

$settings->add(new admin_setting_configselect(
    'helixmedia/sendname',
    get_string('share_name_admin', 'lti'),
    "",
    '1',
    $options
));

$settings->add(new admin_setting_configselect(
    'helixmedia/sendemailaddr',
    get_string('share_email_admin', 'lti'),
    "",
    '1',
    $options
));

$settings->add(new admin_setting_configtextarea(
    'helixmedia/custom_params',
    get_string('custom', 'lti'),
    "",
    "",
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'helixmedia/modal_delay',
    get_string("modal_delay", "helixmedia"),
    get_string("modal_delay2", "helixmedia"),
    0,
    PARAM_INT
));

$settings->add(new admin_setting_configcheckbox(
    'helixmedia/forcedebug',
    get_string('forcedebug', 'helixmedia'),
    get_string('forcedebug_help', 'helixmedia'),
    0
));

$settings->add(new admin_setting_configcheckbox(
    'helixmedia/restrictdebug',
    get_string('restrictdebug', 'helixmedia'),
    get_string('restrictdebug_help', 'helixmedia'),
    1
));

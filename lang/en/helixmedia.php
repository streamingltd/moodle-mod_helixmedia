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
 * This file contains en_utf8 translation of the Media Library module
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @copyright  Streaming LTD
 * @author     Tim Williams (for Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addgrades'] = 'Allow this MEDIAL activity to add grades in the gradebook';
$string['addgrades_help'] = 'MEDIAL will send grades from video quizzes to this activity if you check this option, this will only occur if you choose a video quiz.';
$string['assignfeedltititle'] = 'Feedback for {$a}';
$string['assignsubltititle'] = 'Assignment Submission ({$a})';
$string['choosemedia_title'] = 'Choose Media';
$string['cleanup'] = 'Cleanup MEDIAL resource link ID table';
$string['clientid'] = 'MEDIAL LTI 1.3 Client ID';
$string['clientid2'] = 'This is the LTI 1.3 client ID, copy+paste this into the MEDIAL Lti Site config.';
$string['configproblem'] = 'MEDIAL videos have been disabled because there is a problem with your MEDIAL plugin settings. Please report this to the system administrator.';
$string['consumer_key'] = 'MEDIAL LTI 1.0 Consumer Key';
$string['consumer_key2'] = 'The consumer key used for access to the MEDIAL LTI server. LTI 1.0 only, leave empty for LTI 1.3.';
$string['forcedebug'] = 'Force LTI Launch Debug mode on';
$string['forcedebug_help'] = 'Allows you to debug LTI launch problems by showing the launch parameters.';
$string['guid'] = 'MEDIAL LTI 1.3 Platform ID';
$string['guid2'] = 'The LTI 1.3 Platform ID, leave blank for LTI 1.0';
$string['helixmedia:addinstance'] = 'Add a new MEDIAL Resource';
$string['helixmedia:manage'] = 'Manage MEDIAL Resources';
$string['helixmedia:myview'] = 'View MEDIAL Resources on the dashboard';
$string['helixmedia:view'] = 'View MEDIAL Resources';
$string['helixmediasummary'] = 'Summary';
$string['helixmediatext'] = 'Activity name';
$string['hml_in_new_window'] = 'Open MEDIAL Resource';
$string['hml_in_new_window_message'] = "If a new window doesn't open automatically containing the resource you wish to view, please use the link below to open it.";
$string['ignorewwwsitewarning'] = 'This plugin has been set to ignore root URL inconsistencies, this may cause LTI launch failures and/ database corruption within MEDIAL. Please ensure that you have consulted with MEDIAL support over the setup of this plugin. You can remove this warning and restore the normal consistency checks by executing the SQL below on your database.';
$string['invalid_launch'] = 'Invalid parameters supplied for MEDIAL LTI launch request. Aborting.';
$string['invalid_mobile_token'] = 'The MoodleMobile access token for this video has expired, please use the menu on the top right of the page to refresh this activity and view the video.';
$string['invalidclientid'] = 'The supplied client ID does not match the MEDIAL activity configuration.';
$string['launch_url'] = 'MEDIAL URL';
$string['launch_url2'] = 'Put the LTI URL of the MEDIAL server here. For LTI 1.0 this should be the launch URL in the format: https://upload.mymedialserver.org/Lti/Launch, while for LTI 1.3 use: https://upload.mymedialserver.org ';
$string['launcher'] = 'MEDIAL LTI Launcher';
$string['log_launch'] = 'MEDIAL LTI Launch';
$string['log_launchedit'] = 'MEDIAL LTI Edit Launch';
$string['log_launcheditnew'] = 'MEDIAL LTI New Instance Edit Launch';
$string['lti10'] = 'LTI 1.0';
$string['lti13keywarn'] = 'There was an error generating the private key for LTI 1.3 connections.';
$string['lti_settings_title'] = 'MEDIAL LTI Settings';
$string['ltiauth'] = 'Authorising Connection';
$string['mobiletokens'] = 'Clean up MEDIAL MoodleMobile access tokens';
$string['modal_delay'] = 'Video add dialog box close delay in seconds';
$string['modal_delay2'] = 'By default the modal dialogue box used to add videos will automatically close once the video has been chosen. You can optionally delay the closing of this dialogue by the number of seconds specified here, or disable the auto-close by setting this value to -1. Please note, this setting will have no effect on the modal dialogs used by the plugins for the TinyMCE and ATTO editors which will continue to remain open until closed by the user.';
$string['modulename'] = 'MEDIAL';
$string['modulename_help'] = 'The MEDIAL module provides a customised LTI based Moodle for the integration of MEDIAL server into Moodle';
$string['modulename_link'] = 'mod/helixmedia/view';
$string['modulenameplural'] = 'MEDIAL';
$string['modulenamepluralformatted'] = 'MEDIAL Instances';
$string['moodlemobile'] = 'This MEDIAL video is not available via MoodleMobile. Please use your web browser to view this video.';
$string['mylibrary'] = 'My MEDIAL Library';
$string['nohelixmedias'] = 'No MEDIAL Instances found.';
$string['not_authorised'] = 'You are not authorised to perform this MEDIAL operation.';
$string['openlib'] = 'Open my MEDIAL library';
$string['org_id'] = 'Organisation ID';
$string['org_id2'] = 'The organisation ID or name which will be sent to the MEDIAL server. The URL of your Moodle installation will be sent if this is left blank.';
$string['other_settings_title'] = 'Other Settings';
$string['pleasewait'] = 'Media Loading - Please Wait';
$string['pleasewaitup'] = 'Loading - Please Wait';
$string['pluginadministration'] = 'MEDIAL';
$string['pluginname'] = 'MEDIAL';
$string['privacy:metadata'] = 'The mod_helixmedia plugin does not store any personal data.';
$string['restrictdebug'] = 'Restrict the LTI debugging information to admin users.';
$string['restrictdebug_help'] = 'If forced LTI debugging is on, enabling this will restrict the forced debug mode to admins so that you can debug live systems without confusing users.';
$string['search:activity'] = 'MEDIAL Activity Videos';
$string['selectioncomplete'] = 'You media selection is now complete, this dialogue should close automatically. If it does not, please click the icon on the top right.';
$string['shared_secret'] = 'MEDIAL LTI 1.0 Shared Secret';
$string['shared_secret2'] = 'The shared secret used for comunications between Moodle and the MEDIAL server via LTI 1.0, leave empty for LTI 1.3';
$string['sitewarning'] = 'The root URL of your Mooodle installation has changed from <em>{$a->old}</em> to <em>{$a->new}</em>, this might be because this is copy of another site. LTI Launches have been disabled until you confirm that LTI is correctly configured for your MEDIAL installation.';
$string['sitewarning10'] = 'Sharing LTI 1.0 connection credentials between Moodle sites will cause database corruption in MEDIAL. If this is a copy of an existing site and the original site is still operating, you must create a new LTI Site for this Moodle instalation in MEDIAL and update the configuration below.';
$string['sitewarning13'] = 'LTI 1.3 connection credentials cannot be shared between Moodle sites, the connection authentication will fail for the copy. If it is not a new/copied site and the URL has simply been changed, then you need to update the LTI site in MEDIAL with the new URL of this Moodle installation.  If this is a copy of an existing site and the original site is still operating, you must create a new LTI Site for this Moodle instalation in MEDIAL. You will need to reset the Moodle Client ID, this cannot be done directly via this page. To set a new Client ID, execute the following SQL on your database and then  perform a Moodle cache purge. The new Client ID will then be shown on this page.';
$string['sitewarningheader'] = 'WARNING: Moodle URL changed';
$string['sitewarningremove'] = 'This warning can be removed by executing the SQL below on your database followed by a Moodle cache purge, be sure that you have made the reccomended changes before doing so.';
$string['uploadedby'] = 'Video upload by {$a->name} on {$a->date}';
$string['version_check_fail'] = 'The MEDIAL server version could not be retrived. Please check the MEDIAL activity module is correctly configured.';
$string['version_check_message'] = 'This version of the MEDIAL plugin is reccomended for MEDIAL versions {$a->min} or better. You are using MEDIAL version {$a->actual}.';
$string['version_check_not_done'] = 'The MEDIAL activity has not been configured, version check skipped.';
$string['version_check_title'] = 'MEDIAL Version Check';
$string['version_check_upgrade'] = 'WARNING: You are recomended to upgrade your MEDIAL version for use with this plugin.';

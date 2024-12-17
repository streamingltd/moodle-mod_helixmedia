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
 * Search form renderable.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

use renderable;
use renderer_base;
use templatable;
use moodle_url;


/**
 * Container renderable class.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modal implements renderable, templatable {

    private $preid, $text, $library, $viewonly, $edit, $extraid, $thumblaunchurl, $icon, $frameid, $imgurl, $jsparams;

    /**
     * Gets the modal dialog using the supplied params
     * @param pre_id The resource link ID
     * @param paramsthumb The get request parameters for the thumbnail as an array
     * @param paramslink The get request parameters for the modal link as an array
     * @param image True if we want to use the graphical button
     * @param text The text to use for the button and frame title
     * @param c The course ID, or -1 if not known
     * @param statusCheck true if the statusCheck method should be used
     * @param flex Flex type for display. REDUNDANT
     * @param extraid An extra ID item to append on the div id
     * @return The HTML for the dialog
     **/
    public function __construct($preid, $paramsthumb, $paramslink, $image,
        $text = false, $c = false, $statuscheck = true, $flextype = 'row', $extraid = false, $library = false) {
        global $CFG, $COURSE, $DB, $USER, $OUTPUT;

        if (!$text) {
            $text = get_string('choosemedia_title', 'helixmedia');
        }

        $this->preid = $preid;
        $this->text = $text;
        if ($library) {
            $this->library = $OUTPUT->image_url('library', 'mod_helixmedia');
        } else {
            $this->library = false;
        }
        // We need to allow extra space in the dialog if we are in editing mode. statuscheck will be set to true when we are editing.
        if (!$statuscheck) {
            $this->viewonly = true;
            $this->edit = false;
        } else {
            $this->viewonly = false;
            $this->edit = true;
        }

        if ($extraid !== false) {
            $this->extraid = '_'.$extraid;
        } else {
            $this->extraid = '';
        }
        if ($c !== false) {
            $course = $DB->get_record("course", array("id" => $c));
        } else {
            $course = $COURSE;
        }

        $paramsthumb['course'] = $course->id;
        $paramslink['course'] = $course->id;
        $paramslink['ret'] = base64_encode(curpageurl());
        // Turn off the legacy resize, not needed for this type of embed
        $paramslink['responsive'] = 1;

        $this->thumblaunchurl = new moodle_url('/mod/helixmedia/launch.php', $paramsthumb);
        $this->thumblaunchurl = $this->thumblaunchurl->out(false);
        $launchurl = new moodle_url('/mod/helixmedia/launch.php', $paramslink);
        $launchurl = $launchurl->out(false);
        if ($image) {
            $this->imgurl = true;
            if ($image === true) {
                $this->icon = "upload";
            } else {
                $this->icon = $image;
            }
        } else {
            $this->imgurl = false;
            $this->icon = false;
        }
        if ($statuscheck != "true") {
            $this->frameid = "thumbframeview";
        } else {
            $this->frameid = "thumbframe";
        }

        $modconfig = get_config("helixmedia");
        $this->jsparams = array(
            $this->frameid,
            $launchurl,
            $this->thumblaunchurl,
            $preid,
            $USER->id,
            helixmedia_get_status_url(),
            $modconfig->consumer_key,
            $statuscheck,
            $CFG->wwwroot."/mod/helixmedia/session.php",
            ($CFG->sessiontimeout / 2) * 1000,
            intval($modconfig->modal_delay),
            $this->extraid,
            $this->text,
            $this->library
        );
    }

    public function inc_js() {
        global $PAGE;
        if ($this->viewonly || $this->library) {
            $PAGE->requires->js_call_amd('mod_helixmedia/embed', 'init', $this->jsparams);
        } else {
            $PAGE->requires->js_call_amd('mod_helixmedia/module', 'init', $this->jsparams);
        }
    }


    public function export_for_template(renderer_base $output) {
        global $CFG, $PAGE;

        $data = [
            'thumblaunchurl' => $this->thumblaunchurl,
            'medialurl' => get_config("helixmedia", "launchurl"),
            'imgurl' => $this->imgurl,
            'preid' => $this->preid,
            'text' => $this->text,
            'frameid' => $this->frameid,
            'extraid' => $this->extraid,
            'viewonly' => $this->viewonly,
            'edit' => $this->edit,
            'library' => $this->library,
        ];

        switch ($this->icon) {
            case 'upload':
                $data['uploadicon'] = true;
                break;
            case 'magnifier':
                $data['magnifiericon'] = true;
                break;
        }

        return $data;
    }
}

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

require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

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
    /**
     * @var The resource link ID.
     */
    private $preid;

    /**
     * @var Text to display while loading
     */
    private $text;

    /**
     * @var Is this a library launch?
     */
    private $library;

    /**
     * @var Are we only viewing the resource?
     */
    private $viewonly;

    /**
     * @var Is this launch for editing/setting the video.
     */
    private $edit;

    /**
     * @var An extra ID we may need at the MEDIAL end.
     */
    private $extraid;

    /**
     * @var Are we showing a thumbnail?
     */
    private $thumblaunchurl;

    /**
     * @var The icon to display in the button.
     */
    private $icon;

    /**
     * @var The ID to associate with the iframe.
     */
    private $frameid;

    /**
     * @var true if we are showing an image button
     */
    private $imgurl;

    /**
     * @var Javascript paramters we need for the launch
     */
    private $jsparams;

    /**
     * Gets the modal dialog using the supplied params
     * @param int $preid Resource link id
     * @param int $paramsthumb The get request parameters for the thumbnail as an array
     * @param string $paramslink The get request parameters for the modal link as an array
     * @param string $image True if we want to use the graphical button
     * @param string $text The text to use for the button and frame title
     * @param int $c The course ID, or -1 if not known
     * @param bool $statuscheck true if the statusCheck method should be used
     * @param bool $flextype Flex type for display. REDUNDANT
     * @param bool $extraid An extra ID item to append on the div id
     * @param bool $library true if this is a libary view request
     **/
    public function __construct(
        $preid,
        $paramsthumb,
        $paramslink,
        $image,
        $text = false,
        $c = false,
        $statuscheck = true,
        $flextype = 'row',
        $extraid = false,
        $library = false
    ) {
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
        // We need to allow extra space in the dialog if we are in editing mode.
        // Statuscheck will be set to true when we are editing.
        if (!$statuscheck) {
            $this->viewonly = true;
            $this->edit = false;
        } else {
            $this->viewonly = false;
            $this->edit = true;
        }

        if ($extraid !== false) {
            $this->extraid = '_' . $extraid;
        } else {
            $this->extraid = '';
        }
        if ($c !== false) {
            $course = $DB->get_record("course", ["id" => $c]);
        } else {
            $course = $COURSE;
        }

        $paramsthumb['course'] = $course->id;
        $paramslink['course'] = $course->id;
        $paramslink['ret'] = base64_encode(curpageurl());
        // Turn off the legacy resize, not needed for this type of embed.
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
        $this->jsparams = [
            $this->frameid,
            $launchurl,
            $this->thumblaunchurl,
            $preid,
            $USER->id,
            helixmedia_get_status_url(),
            $modconfig->consumer_key,
            $statuscheck,
            $CFG->wwwroot . "/mod/helixmedia/session.php",
            ($CFG->sessiontimeout / 2) * 1000,
            intval($modconfig->modal_delay),
            $this->extraid,
            $this->text,
            $this->library,
            $CFG->wwwroot,
            helixmedia_is_moodle_5(),
        ];
    }

    /**
     * Includes javascript modules on the page.
     */
    public function inc_js() {
        global $PAGE;
        if ($this->viewonly || $this->library) {
            $PAGE->requires->js_call_amd('mod_helixmedia/embed', 'init', $this->jsparams);
        } else {
            $PAGE->requires->js_call_amd('mod_helixmedia/module', 'init', $this->jsparams);
        }
    }

    /**
     * Exports data for rendering
     * @param renderer_base $output The renderer
     * @return array
     */
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
            'bs5' => helixmedia_is_moodle_5(),
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

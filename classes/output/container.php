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

use moodle_url;
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
class container implements renderable, templatable {

    private $param, $width, $height, $course;

    /**
     * Constructor.
     */
    public function __construct($course, $id, $aid, $l, $w, $h, $ret, $nassign, $eassign, 
        $nfeed, $efeed, $userid, $type, $name, $intro, $modtype) {

        if ($l > 0) {
            $this->param = "l=".$l;
        } else {
            if ($id > 0) {
                $this->param = "id=".$id;
            } else {
                if ($nassign > 0) {
                    $this->param = "n_assign=".$nassign."&amp;aid=".$aid;
                } else {
                    if ($eassign > 0) {
                        $this->param = "e_assign=".$eassign;
                    } else {
                        if ($nfeed > 0) {
                            $this->param = "n_feed=".$nfeed."amp;&aid=".$aid;
                        } else {
                            if ($efeed > 0) {
                                $this->param = "e_feed=".$efeed;
                            }
                        }
                    }
                }
            }
        }

        if ($userid > 0) {
            $this->param = "userid=".$userid."&amp;".$this->param;
        }
        if ($type > 0) {
            $this->param = "type=".$type."&amp;".$this->param;
        }
        if (strlen($ret) > 0) {
            $this->param = $this->param."&amp;ret=".$ret;
        }
        if (strlen($name) > 0) {
            $this->param = $this->param."&amp;name=".$name;
        }
        if (strlen($intro) > 0) {
            $this->param = $this->param."&amp;intro=".$intro;
        }
        if (strlen($modtype) > 0) {
            $this->param = $this->param."&amp;modtype=".$modtype;
        }

        $this->height = $h - 32;
        $this->width = $w - 14;
        $this->course = $course;
    }

    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = [
            'wwwroot' =>$CFG->wwwroot,
            'launchurl' => get_config("helixmedia", "launchurl"),
            'width' => $this->width,
            'height' => $this->height,
            'course' => $this->course,
            'param' => $this->param
        ];
        return $data;
    }

}

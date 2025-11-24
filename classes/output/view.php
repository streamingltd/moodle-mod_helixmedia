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

use renderable;
use renderer_base;
use templatable;

require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

/**
 * Container renderable class.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class view implements renderable, templatable {
    /**
     * @var The LTI launch URL
     */
    private $launchurl;

    /**
     * @var Is this audio only?
     */
    private $audioonly;

    /**
     * Constructor.
     * @param string $launchurl Launch URL
     * @param bool $audioonly
     */
    public function __construct($launchurl, $audioonly = false) {
        global $COURSE;
        $this->launchurl = $launchurl;
        $this->audioonly = $audioonly;

        if ($COURSE->id != 1) {
            $this->launchurl .= '&course=' . $COURSE->id;
        }
    }

    /**
     * Export data for rendering.
     * @param renderer_base $output The renderer.
     * @return arrray The data as an array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;

        $data = [
            'launchurl' => $this->launchurl,
            'bs5' => helixmedia_is_moodle_5(),
            'audioonly' => $this->audioonly,
            'medialurl' => get_config("helixmedia", "launchurl"),
        ];
        return $data;
    }
}

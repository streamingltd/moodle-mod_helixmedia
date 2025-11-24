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
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class launchmessage implements renderable, templatable {
    /**
     * @var The message we want to show
     */
    private $message;

    /**
     * @var A css class to apply to the message
     */
    private $class;

    /**
     * Constructor.
     * @param string $message The message we want to show
     * @param string $class A css class to apply to the message
     */
    public function __construct($message, $class = '') {
        $this->message = $message;
        $this->class = $class;
    }

    /**
     * Exports data for rendering
     * @param renderer_base $output The renderer
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return [
            'message' => $this->message,
            'class' => $this->class,
        ];
    }
}

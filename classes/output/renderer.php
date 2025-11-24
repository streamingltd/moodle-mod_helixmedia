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

use plugin_renderer_base;
use renderable;

/**
 * Block filter course search renderer.
 *
 * @package    mod_helixmedia
 * @copyright  2021 Tim Williams <tmw@autotrain.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Render LTI view
     *
     * @param renderable $fd
     * @return string
     */
    public function render_view(renderable $fd) {
        return $this->render_from_template('mod_helixmedia/view', $fd->export_for_template($this));
    }

    /**
     * Render LTI view with new window
     *
     * @param renderable $fd
     * @return string
     */
    public function render_viewwindow(renderable $fd) {
        return $this->render_from_template('mod_helixmedia/viewwindow', $fd->export_for_template($this));
    }

    /**
     * Render the Add/Update modal dialog button
     *
     * @param renderable $fd
     * @return string
     */
    public function render_modal(renderable $fd) {
        $data = $fd->export_for_template($this);
        $fd->inc_js();
        if (!$data['imgurl']) {
            if ($data['library'] !== false) {
                return $this->render_from_template('mod_helixmedia/modallib', $data);
            }
            return $this->render_from_template('mod_helixmedia/modallink', $data);
        } else {
            return $this->render_from_template('mod_helixmedia/modalbutton', $data);
        }
    }

    /**
     * Render launch code
     * @param renderable $fd
     * @return string
     */
    public function render_launcher(renderable $fd) {
        return $this->render_from_template('mod_helixmedia/launcher', $fd->export_for_template($this));
    }


    /**
     * Render launch code
     * @param renderable $fd
     * @return string
     */
    public function render_launcher13(renderable $fd) {
        return $this->render_from_template('mod_helixmedia/launch13', $fd->export_for_template($this));
    }

    /**
     * Render launch message
     * @param renderable $fd
     * @return string
     */
    public function render_launchmessage(renderable $fd) {
        return $this->render_from_template('mod_helixmedia/launchmessage', $fd->export_for_template($this));
    }

    /**
     * Render lti13 auth process
     * @param renderable $fd
     * @return string
     */
    public function render_auth(renderable $fd) {
        return $this->render_from_template('mod_helixmedia/launch13', $fd->export_for_template($this));
    }
}

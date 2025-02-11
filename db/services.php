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
 * This file contains helixmedia external webservices
 *
 * @package    mod_helixmedia
 * @subpackage helixmedia
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  MEDIAL
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'mod_helixmedia_get_launch_data' => [
        'classname'     => 'mod_helixmedia_external',
        'methodname'    => 'get_launch_data',
        'description'   => 'Returns launch data for the plugin',
        'type'          => 'read',
        'capabilities'  => 'mod/helixmedia:view',
        'services'      => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'],
    ],
];

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
 * This file contains a class definition for the LTI Gradebook Services
 *
 * @package    mod_helixmedia
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\services;

use ltiservice_gradebookservices\local\service;
use mod_helixmedia\resources;
use mod_lti\local\ltiservice\resource_base;
use mod_lti\local\ltiservice\service_base;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/mod/helixmedia/locallib.php');

/**
 * A service implementing LTI Gradebook Services.
 *
 * @package    helixmedia
 * @copyright  2025 Streaming LTD
 * @author     Tim Williams <tim@medial.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradebookservices extends \ltiservice_gradebookservices\local\service\gradebookservices {
    /**
     * Class constructor.
     */
    public function __construct() {
        // Call the service_base constructor without calling the intermediate parent which sets the wrong name.
        $reflectionmethod = new \ReflectionMethod(get_parent_class(get_parent_class($this)), '__construct');
        $reflectionmethod->invoke($this);

        $this->id = 'gradebookservices';
        $this->name = get_string('pluginname', 'helixmedia');
    }


    /**
     * Get the path for service requests.
     *
     * @return string
     */
    public static function get_service_path() {
        $url = new \moodle_url('/mod/helixmedia/services.php');
        return $url->out(false);
    }


    /**
     * Return an array of key/values to add to the launch parameters.
     *
     * @param string $requestparams The array to add the parameters to
     * @param stdclass $instance The hmli config
     */
    public function add_launch_parameters(&$requestparams, $instance) {
        global $DB;
        $launchparameters = [];

        if (!property_exists($instance, 'addgrades') || !$instance->addgrades) {
            return [];
        }

        $conditions = ['courseid' => $instance->course, 'itemtype' => 'mod',
            'itemmodule' => 'helixmedia', 'iteminstance' => $instance->id];

        $gradeitems = $DB->get_records('grade_items', $conditions);
        if (count($gradeitems) == 0) {
            return;
        }

        // There should only be one.
        $gradeitem = reset($gradeitems);

        $url = new \moodle_url('/mod/helixmedia/services.php/' . $instance->course . '/lineitems/' . $gradeitem->id . '/lineitem');
        $requestparams['custom_gradebookservices_scope'] = implode(',', $this->get_scopes());
        $requestparams['custom_lineitem_url'] = $url->out();
    }

    /**
     * Gets the service scopes
     *
     * @return array
     */
    public function get_scopes() {
        // TODO : Not sure if these are correct?
        return [self::SCOPE_GRADEBOOKSERVICES_SCORE];
    }

    /**
     * Get the resources for this service.
     *
     * @return resource_base[]
     */
    public function get_resources() {

        // The containers should be ordered in the array after their elements.
        // Lineitems should be after lineitem.
        if (empty($this->resources)) {
            $this->resources[] = new \mod_helixmedia\resources\scores($this);
        }

        return $this->resources;
    }

    /**
     * Fetch a lineitem instance.
     *
     * Returns the lineitem instance if found, otherwise false.
     *
     * @param string $courseid ID of course
     * @param string $itemid ID of lineitem
     * @param string $typeid
     *
     * @return \ltiservice_gradebookservices\local\resources\lineitem|bool
     */
    public function get_lineitem($courseid, $itemid, $typeid) {
        global $DB, $CFG;

        require_once($CFG->libdir . '/gradelib.php');
        $lineitem = \grade_item::fetch(['id' => $itemid]);
        return $lineitem;
    }


    /**
     * Check that the request has been properly signed and is permitted.
     *
     * @param string $body      Request body (null if none)
     * @param string[] $scopes  Array of required scope(s) for incoming request
     *
     * @return boolean
     */
    public function validate_tool($body = null, $scopes = null) {

        $ok = true;
        $toolproxy = null;
        $consumerkey = helixmedia_get_oauth_key_from_headers($scopes);
        if ($consumerkey === false) {
            $ok = $this->is_unsigned();
        }
        // TODO: Check nothing else needs to be done here.
        return $ok;
    }
}

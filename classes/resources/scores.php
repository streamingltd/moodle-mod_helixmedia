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
 * This file contains a class definition for the LISResult container resource
 *
 * @package    mod_helixmedia
 * @copyright  2017 Cengage Learning http://www.cengage.com, 2025 Streaming Ltd
 * @author     Dirk Singels, Diego del Blanco, Claude Vervoort, Tim Williams <tim@medial.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\resources;

use ltiservice_gradebookservices\local\service\gradebookservices;
use mod_lti\local\ltiservice\resource_base;

/**
 * A resource implementing LISResult container.
 *
 * @package    ltiservice_gradebookservices
 * @copyright  2017 Cengage Learning http://www.cengage.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scores extends resource_base {
    /**
     * Class constructor.
     *
     * @param \ltiservice_gradebookservices\local\service\gradebookservices $service Service instance
     */
    public function __construct($service) {

        parent::__construct($service);
        $this->id = 'Score.collection';
        $this->template = '/{context_id}/lineitems/{item_id}/lineitem/scores';
        $this->variables[] = 'Scores.url';
        $this->formats[] = 'application/vnd.ims.lis.v1.scorecontainer+json';
        $this->formats[] = 'application/vnd.ims.lis.v1.score+json';
        $this->methods[] = 'POST';
    }

    /**
     * Execute the request for this resource.
     *
     * @param \mod_lti\local\ltiservice\response $response  Response object for this request.
     */
    public function execute($response) {
        global $CFG, $DB;

        $params = $this->parse_template();
        $contextid = $params['context_id'];
        $itemid = $params['item_id'];

        // GET is disabled by the moment, but we have the code ready
        // for a future implementation.

        $isget = $response->get_request_method() === 'GET';
        if ($isget) {
            $contenttype = $response->get_accept();
        } else {
            $contenttype = $response->get_content_type();
        }
        $container = empty($contenttype) || ($contenttype === $this->formats[0]);

        $scope = gradebookservices::SCOPE_GRADEBOOKSERVICES_SCORE;

        try {
            if (!$this->validate_request($response->get_request_data(), [$scope])) {
                throw new \Exception(null, 401);
            }

            if (
                empty($contextid) || !($container ^ ($response->get_request_method() === self::HTTP_POST)) ||
                    (!empty($contenttype) && !in_array($contenttype, $this->formats))
            ) {
                throw new \Exception('No context or unsupported content type', 400);
            }
            if (!($course = $DB->get_record('course', ['id' => $contextid], 'id', IGNORE_MISSING))) {
                throw new \Exception("Not Found: Course {$contextid} doesn't exist", 404);
            }

            if (!$DB->record_exists('grade_items', ['id' => $itemid])) {
                throw new \Exception("Not Found: Grade item {$itemid} doesn't exist", 404);
            }
            $item = $this->get_service()->get_lineitem($contextid, $itemid, 0);
            if ($item === false) {
                throw new \Exception('Line item does not exist', 404);
            }
            $json = '[]';
            require_once($CFG->libdir . '/gradelib.php');
            switch ($response->get_request_method()) {
                case 'GET':
                    $response->set_code(405);
                    $response->set_reason("GET requests are not allowed.");
                    break;
                case 'POST':
                    try {
                        $json = $this->get_json_for_post_request(
                            $response,
                            $response->get_request_data(),
                            $item,
                            $contextid,
                            0
                        );
                        $response->set_content_type($this->formats[1]);
                    } catch (\Exception $e) {
                        $response->set_code($e->getCode());
                        $response->set_reason($e->getMessage());
                    }
                    break;
                default:  // Should not be possible.
                    $response->set_code(405);
                    $response->set_reason("Invalid request method specified.");
                    return;
            }
            $response->set_body($json);
        } catch (\Exception $e) {
            $response->set_code($e->getCode());
            $response->set_reason($e->getMessage());
        }
    }

    /**
     * Check that the request has been properly signed and is permitted.
     *
     * @param string $body      Request body (null if none)
     * @param string[] $scopes  Array of required scope(s) for incoming request
     *
     * @return boolean
     */
    public function validate_request($body = null, $scopes = null) {

        $ok = $this->get_service()->validate_tool($body, $scopes);
        // TODO: Check nothing else needs to be done here.
        return $ok;
    }

    /**
     * Generate the JSON for a POST request.
     *
     * @param \mod_lti\local\ltiservice\response $response Response object for this request.
     * @param string $body POST body
     * @param object $item Grade item instance
     * @param string $contextid
     *
     * @throws \Exception
     */
    private function get_json_for_post_request($response, $body, $item, $contextid) {
        $score = json_decode($body);
        if (
            empty($score) ||
                !isset($score->userId) ||
                !isset($score->timestamp) ||
                !isset($score->gradingProgress) ||
                !isset($score->activityProgress) ||
                !isset($score->timestamp) ||
                isset($score->timestamp) && !gradebookservices::validate_iso8601_date($score->timestamp) ||
                (isset($score->scoreGiven) && !is_numeric($score->scoreGiven)) ||
                (isset($score->scoreGiven) && !isset($score->scoreMaximum)) ||
                (isset($score->scoreMaximum) && !is_numeric($score->scoreMaximum)) ||
                (!gradebookservices::is_user_gradable_in_course($contextid, $score->userId))
        ) {
            throw new \Exception('Incorrect score received' . $body, 400);
        }
        $score->timemodified = intval($score->timestamp);

        if (!isset($score->scoreMaximum)) {
            $score->scoreMaximum = 1;
        }
        $response->set_code(200);
        $grade = \grade_grade::fetch(['itemid' => $item->id, 'userid' => $score->userId]);
        if ($grade &&  !empty($grade->timemodified)) {
            if ($grade->timemodified >= strtotime($score->timestamp)) {
                $exmsg = "Refusing score with an earlier timestamp for item " . $item->id . " and user " . $score->userId;
                throw new \Exception($exmsg, 409);
            }
        }
        if (isset($score->scoreGiven)) {
            if ($score->gradingProgress != 'FullyGraded') {
                $score->scoreGiven = null;
            }
        }
        $this->get_service()->save_grade_item($item, $score, $score->userId);
    }

    /**
     * Parse a value for custom parameter substitution variables.
     *
     * @param string $value String to be parsed
     *
     * @return string
     */
    public function parse_value($value) {
        global $COURSE, $CFG;

        if (strpos($value, '$Scores.url') !== false) {
            require_once($CFG->libdir . '/gradelib.php');

            $resolved = '';
            $this->params['context_id'] = $COURSE->id;
            $id = optional_param('id', 0, PARAM_INT); // Course Module ID.
            if (!empty($id)) {
                $cm = get_coursemodule_from_id('lti', $id, 0, false, MUST_EXIST);
                $id = $cm->instance;
                $item = grade_get_grades($COURSE->id, 'mod', 'lti', $id);
                if ($item && $item->items) {
                    $this->params['item_id'] = $item->items[0]->id;
                    $resolved = parent::get_endpoint();
                }
            }
            $value = str_replace('$Scores.url', $resolved, $value);
        }

        return $value;
    }
}

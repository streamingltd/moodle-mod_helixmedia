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

namespace mod_helixmedia\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for mod_helixmedia
 *
 * The {@see helixmedia_mobile} table records which user generated a MoodleMobile
 * launch token for which activity/course and when, so it is personal data and
 * must be covered by the full privacy API rather than null_provider.
 *
 * @package    mod_helixmedia
 * @copyright  MEDIAL
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items The reference to the collection to use to store the metadata.
     * @return collection The updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'helixmedia_mobile',
            [
                'userid' => 'privacy:metadata:helixmedia_mobile:userid',
                'instance' => 'privacy:metadata:helixmedia_mobile:instance',
                'course' => 'privacy:metadata:helixmedia_mobile:course',
                'token' => 'privacy:metadata:helixmedia_mobile:token',
                'timecreated' => 'privacy:metadata:helixmedia_mobile:timecreated',
            ],
            'privacy:metadata:helixmedia_mobile'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The userid.
     * @return contextlist The list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {helixmedia} hm ON hm.id = cm.instance
                  JOIN {helixmedia_mobile} hmm ON hmm.instance = hm.id
                 WHERE hmm.userid = :userid";

        $params = [
            'modname' => 'helixmedia',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT hmm.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {helixmedia} hm ON hm.id = cm.instance
                  JOIN {helixmedia_mobile} hmm ON hmm.instance = hm.id
                 WHERE cm.id = :instanceid";

        $params = [
            'modname' => 'helixmedia',
            'instanceid' => $context->instanceid,
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist The list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       hmm.token,
                       hmm.timecreated
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {helixmedia} hm ON hm.id = cm.instance
                  JOIN {helixmedia_mobile} hmm ON hmm.instance = hm.id
                 WHERE c.id {$contextsql}
                   AND hmm.userid = :userid
              ORDER BY cm.id";

        $params = ['userid' => $user->id, 'contextlevel' => CONTEXT_MODULE] + $contextparams;

        $lastcmid = null;
        $tokendata = [];

        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            if ($lastcmid !== null && $lastcmid != $record->cmid) {
                $context = \context_module::instance($lastcmid);
                self::export_helixmedia_data_for_user($tokendata, $context, $user);
                $tokendata = [];
            }
            $tokendata['mobile_tokens'][] = [
                'token' => $record->token,
                'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
            ];
            $lastcmid = $record->cmid;
        }
        $records->close();

        if (!empty($tokendata)) {
            $context = \context_module::instance($lastcmid);
            self::export_helixmedia_data_for_user($tokendata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single helixmedia activity.
     *
     * @param array $tokendata The personal data to export for the helixmedia activity.
     * @param \context_module $context The context of the helixmedia activity.
     * @param \stdClass $user The user record.
     */
    protected static function export_helixmedia_data_for_user(array $tokendata, \context_module $context, \stdClass $user) {
        $contextdata = helper::get_context_data($context, $user);
        $contextdata = (object) array_merge((array) $contextdata, $tokendata);
        writer::with_context($context)->export_data([], $contextdata);
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $instanceid = self::get_helixmedia_id_from_context($context);
        if (empty($instanceid)) {
            return;
        }

        $DB->delete_records('helixmedia_mobile', ['instance' => $instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $instanceid = self::get_helixmedia_id_from_context($context);
            if (empty($instanceid)) {
                continue;
            }

            $DB->delete_records('helixmedia_mobile', ['instance' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $instanceid = self::get_helixmedia_id_from_context($context);
        if (empty($instanceid)) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $sql = "instance = :instanceid AND userid {$insql}";
        $params = array_merge($inparams, ['instanceid' => $instanceid]);

        $DB->delete_records_select('helixmedia_mobile', $sql, $params);
    }

    /**
     * Get the helixmedia instance id from the context.
     *
     * @param \context_module $context The context to get the helixmedia instance id from.
     * @return int The helixmedia instance id, or 0 if not found.
     */
    protected static function get_helixmedia_id_from_context(\context_module $context): int {
        $cm = get_coursemodule_from_id('helixmedia', $context->instanceid);
        return $cm ? (int) $cm->instance : 0;
    }
}

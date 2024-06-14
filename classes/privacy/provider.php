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
 * Privacy Subsystem implementation for local_clara_sync.
 *
 * @package     mod_helixmedia  
 * @copyright   Catalyst IT Canada LTD
 * @author      Niko Hoogeveen <nikohoogeveen@catalyst-ca.net>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_helixmedia\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_userlist;

class provider implements   \core_privacy\local\metadata\provider,
                            \core_privacy\local\request\core_userlist_provider, 
                            \core_privacy\local\request\plugin\provider {
    
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        
        $collection->add_database_table(
            'helixmedia_mobile',
            [
                'userid' => 'privacy:metadata:helixmedia_mobile:userid'
            ],
            'privacy:metadata:helixmedia_mobile'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist{
        // Initialize contextlist object
        $contextlist = new \core_privacy\local\request\contextlist();

        // Define SQL query to find the contexts containing user information
        $sql = "SELECT ctx.id
                FROM {context} ctx
                JOIN {helixmedia_mobile} hmm ON hmm.course = ctx.instanceid
                WHERE hmm.userid = :userid";

        // Execute the query and get the list of context ID's
        $params = [ 'userid' => $userid ];
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
                FROM {helixmedia_mobile} hmm
                WHERE hmm.course = :courseid";
        
        $params = ['courseid' => $context->instanceid];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        debugging('The Helixmedia plugin does not currently support the exporting of user data. ', DEBUG_DEVELOPER);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        debugging('The Helixmedia plugin does not currently support the deleting of user data. ', DEBUG_DEVELOPER);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        debugging('The Helixmedia plugin does not currently support the deleting of user data. ', DEBUG_DEVELOPER);
    }
    
    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        debugging('The Helixmedia plugin does not currently support the deleting of user data. ', DEBUG_DEVELOPER);
    }


}

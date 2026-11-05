<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_qmatriz\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper as request_helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for mod_qmatriz.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, \core_privacy\local\request\core_userlist_provider, \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('qmatriz_submission', [
            'userid' => 'privacy:metadata:qmatriz_submission:userid',
            'listvalue' => 'privacy:metadata:qmatriz_submission:listvalue',
            'textvalue' => 'privacy:metadata:qmatriz_submission:textvalue',
            'timecreated' => 'privacy:metadata:qmatriz_submission:timecreated',
        ], 'privacy:metadata:qmatriz_submission');
        $collection->add_database_table('qmatriz_answer', [
            'rowitem' => 'privacy:metadata:qmatriz_answer:rowitem',
            'question' => 'privacy:metadata:qmatriz_answer:question',
            'answer' => 'privacy:metadata:qmatriz_answer:answer',
        ], 'privacy:metadata:qmatriz_answer');
        return $collection;
    }

    /**
     * Contexts with data for a user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :ctxlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {qmatriz_submission} s ON s.qmatrizid = cm.instance
                 WHERE s.userid = :userid";
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, ['ctxlevel' => CONTEXT_MODULE, 'modname' => 'qmatriz', 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Users with data in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $sql = "SELECT s.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {qmatriz_submission} s ON s.qmatrizid = cm.instance
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['modname' => 'qmatriz', 'cmid' => $context->instanceid]);
    }

    /**
     * Export a user's data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('qmatriz', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $submissions = $DB->get_records(
                'qmatriz_submission',
                ['qmatrizid' => $cm->instance, 'userid' => $userid],
                'timecreated ASC'
            );
            $export = [];
            foreach ($submissions as $s) {
                $answers = [];
                foreach ($DB->get_records('qmatriz_answer', ['submissionid' => $s->id], 'rowitem ASC, question ASC') as $a) {
                    $answers[] = ['row' => $a->rowitem, 'question' => (int)$a->question, 'answer' => $a->answer];
                }
                $export[] = [
                    'listvalue' => $s->listvalue,
                    'textvalue' => $s->textvalue,
                    'timecreated' => transform::datetime($s->timecreated),
                    'answers' => $answers,
                ];
            }
            if ($export) {
                $data = request_helper::get_context_data($context, $contextlist->get_user());
                $data->submissions = $export;
                writer::with_context($context)->export_data([], $data);
            }
        }
    }

    /**
     * Delete all data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('qmatriz', $context->instanceid);
        if ($cm) {
            self::delete_submissions($cm->instance, null);
        }
    }

    /**
     * Delete a user's data in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('qmatriz', $context->instanceid);
            if ($cm) {
                self::delete_submissions($cm->instance, [$userid]);
            }
        }
    }

    /**
     * Delete several users' data in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('qmatriz', $context->instanceid);
        if ($cm && $userlist->get_userids()) {
            self::delete_submissions($cm->instance, $userlist->get_userids());
        }
    }

    /**
     * Delete submissions of an instance, optionally only for some users.
     *
     * @param int $qmatrizid
     * @param int[]|null $userids
     */
    protected static function delete_submissions(int $qmatrizid, ?array $userids) {
        global $DB;
        $where = 'qmatrizid = :eid';
        $params = ['eid' => $qmatrizid];
        if ($userids !== null) {
            [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $where .= " AND userid {$insql}";
            $params += $inparams;
        }
        $ids = $DB->get_fieldset_select('qmatriz_submission', 'id', $where, $params);
        if ($ids) {
            [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
            $DB->delete_records_select('qmatriz_answer', "submissionid {$insql}", $inparams);
            $DB->delete_records_select('qmatriz_submission', "id {$insql}", $inparams);
        }
    }
}

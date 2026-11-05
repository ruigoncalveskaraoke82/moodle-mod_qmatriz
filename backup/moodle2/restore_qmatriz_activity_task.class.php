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

/**
 * Restore task for mod_qmatriz.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/qmatriz/backup/moodle2/restore_qmatriz_stepslib.php');

/**
 * Restore task.
 */
class restore_qmatriz_activity_task extends restore_activity_task {
    /**
     * No specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_qmatriz_activity_structure_step('qmatriz_structure', 'qmatriz.xml'));
    }

    /**
     * Contents to decode.
     *
     * @return array
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('qmatriz', ['intro', 'instructions'], 'qmatriz')];
    }

    /**
     * Decoding rules.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('QMATRIZVIEWBYID', '/mod/qmatriz/view.php?id=$1', 'course_module'),
            new restore_decode_rule('QMATRIZINDEX', '/mod/qmatriz/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Course log rules.
     *
     * @return array
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}

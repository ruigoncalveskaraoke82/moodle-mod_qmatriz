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
 * Restore structure for mod_qmatriz.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore structure step.
 */
class restore_qmatriz_activity_structure_step extends restore_activity_structure_step {
    /**
     * Paths.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [new restore_path_element('qmatriz', '/activity/qmatriz')];
        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('qmatriz_submission', '/activity/qmatriz/submissions/submission');
            $paths[] = new restore_path_element(
                'qmatriz_answer',
                '/activity/qmatriz/submissions/submission/answers/answer'
            );
        }
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore the instance.
     *
     * @param array $data
     */
    protected function process_qmatriz($data) {
        global $DB;
        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);
        $newid = $DB->insert_record('qmatriz', $data);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restore a submission.
     *
     * @param array $data
     */
    protected function process_qmatriz_submission($data) {
        global $DB;
        $data = (object)$data;
        $oldid = $data->id;
        $data->qmatrizid = $this->get_new_parentid('qmatriz');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (empty($data->userid)) {
            return;
        }
        $newid = $DB->insert_record('qmatriz_submission', $data);
        $this->set_mapping('qmatriz_submission', $oldid, $newid);
    }

    /**
     * Restore an answer.
     *
     * @param array $data
     */
    protected function process_qmatriz_answer($data) {
        global $DB;
        $data = (object)$data;
        $data->submissionid = $this->get_new_parentid('qmatriz_submission');
        if (empty($data->submissionid)) {
            return;
        }
        $DB->insert_record('qmatriz_answer', $data);
    }

    /**
     * Restore files.
     */
    protected function after_execute() {
        $this->add_related_files('mod_qmatriz', 'intro', null);
    }
}

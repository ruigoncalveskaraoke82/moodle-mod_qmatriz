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
 * Backup structure for mod_qmatriz.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup structure step.
 */
class backup_qmatriz_activity_structure_step extends backup_activity_structure_step {
    /**
     * Structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        $qmatriz = new backup_nested_element('qmatriz', ['id'], [
            'name', 'intro', 'introformat', 'identheading', 'listenabled', 'listlabel', 'listoptions', 'listrequired',
            'textenabled', 'textlabel', 'textrequired', 'matrixheading', 'instructions', 'instructionsformat', 'rowheader',
            'rowitems', 'sortrows', 'questions', 'questionlabels', 'answeroptions', 'conditionenabled', 'conditionvalue',
            'requirecomplete', 'multiplesubmissions', 'hideidentity', 'thankyou', 'timeopen', 'timeclose',
            'timecreated', 'timemodified']);
        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'userid', 'listvalue', 'textvalue', 'timecreated', 'timemodified']);
        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', ['id'], ['rowitem', 'question', 'answer']);

        $qmatriz->add_child($submissions);
        $submissions->add_child($submission);
        $submission->add_child($answers);
        $answers->add_child($answer);

        $qmatriz->set_source_table('qmatriz', ['id' => backup::VAR_ACTIVITYID]);
        if ($userinfo) {
            $submission->set_source_table('qmatriz_submission', ['qmatrizid' => backup::VAR_PARENTID], 'id ASC');
            $answer->set_source_table('qmatriz_answer', ['submissionid' => backup::VAR_PARENTID], 'id ASC');
        }

        $submission->annotate_ids('user', 'userid');
        $qmatriz->annotate_files('mod_qmatriz', 'intro', null);

        return $this->prepare_activity_structure($qmatriz);
    }
}

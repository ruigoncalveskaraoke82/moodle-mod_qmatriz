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
 * Library of functions for mod_qmatriz.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Features supported by this module.
 *
 * @param string $feature FEATURE_xx constant
 * @return mixed
 */
function qmatriz_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GRADE_OUTCOMES:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        default:
            return null;
    }
}

/**
 * Clean the instance data coming from the settings form.
 *
 * @param stdClass $data
 * @return stdClass
 */
function qmatriz_prepare_instance_data(stdClass $data): stdClass {
    $helper = \mod_qmatriz\local\helper::class;
    foreach (['listoptions', 'rowitems', 'questions', 'answeroptions'] as $field) {
        $data->{$field} = implode("\n", $helper::parse_list($data->{$field} ?? ''));
    }
    // Column headings keep empty lines so that line N stays aligned with question N.
    $labels = array_map('trim', preg_split('/\R/u', trim($data->questionlabels ?? '')));
    $data->questionlabels = trim(implode("\n", $labels));
    foreach (['identheading', 'listlabel', 'textlabel', 'matrixheading', 'rowheader', 'conditionvalue', 'thankyou'] as $field) {
        $data->{$field} = trim($data->{$field} ?? '');
    }
    if (isset($data->instructions_editor)) {
        $data->instructions = $data->instructions_editor['text'] ?? '';
        $data->instructionsformat = $data->instructions_editor['format'] ?? FORMAT_HTML;
    }
    foreach (
        ['listenabled', 'listrequired', 'textenabled', 'textrequired', 'sortrows', 'conditionenabled',
            'requirecomplete', 'multiplesubmissions', 'hideidentity'] as $field
    ) {
        $data->{$field} = empty($data->{$field}) ? 0 : 1;
    }
    $data->timeopen = empty($data->timeopen) ? 0 : (int)$data->timeopen;
    $data->timeclose = empty($data->timeclose) ? 0 : (int)$data->timeclose;
    $data->timemodified = time();
    return $data;
}

/**
 * Add a new instance.
 *
 * @param stdClass $data
 * @param mod_qmatriz_mod_form|null $mform
 * @return int new instance id
 */
function qmatriz_add_instance(stdClass $data, $mform = null) {
    global $DB;
    $data = qmatriz_prepare_instance_data($data);
    $data->timecreated = $data->timemodified;
    return $DB->insert_record('qmatriz', $data);
}

/**
 * Update an instance.
 *
 * @param stdClass $data
 * @param mod_qmatriz_mod_form|null $mform
 * @return bool
 */
function qmatriz_update_instance(stdClass $data, $mform = null) {
    global $DB;
    $data->id = $data->instance;
    $data = qmatriz_prepare_instance_data($data);
    return $DB->update_record('qmatriz', $data);
}

/**
 * Delete an instance and all its submissions.
 *
 * @param int $id instance id
 * @return bool
 */
function qmatriz_delete_instance($id) {
    global $DB;
    if (!$DB->record_exists('qmatriz', ['id' => $id])) {
        return false;
    }
    qmatriz_delete_submissions($id);
    $DB->delete_records('qmatriz', ['id' => $id]);
    return true;
}

/**
 * Delete all submissions (and answers) of an instance.
 *
 * @param int $qmatrizid
 */
function qmatriz_delete_submissions(int $qmatrizid): void {
    global $DB;
    $DB->delete_records_select(
        'qmatriz_answer',
        'submissionid IN (SELECT id FROM {qmatriz_submission} WHERE qmatrizid = ?)',
        [$qmatrizid]
    );
    $DB->delete_records('qmatriz_submission', ['qmatrizid' => $qmatrizid]);
}

/**
 * Add the "Results" link to the activity navigation.
 *
 * @param settings_navigation $settings
 * @param navigation_node $node
 */
function qmatriz_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    $cm = $settings->get_page()->cm;
    if (!$cm) {
        return;
    }
    if (has_capability('mod/qmatriz:viewreports', context_module::instance($cm->id))) {
        $node->add(
            get_string('results', 'qmatriz'),
            new moodle_url('/mod/qmatriz/report.php', ['id' => $cm->id]),
            navigation_node::TYPE_SETTING,
            null,
            'qmatrizresults',
            new pix_icon('i/report', '')
        );
    }
}

/**
 * Course reset form elements.
 *
 * @param MoodleQuickForm $mform
 */
function qmatriz_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'qmatrizheader', get_string('modulenameplural', 'qmatriz'));
    $mform->addElement('advcheckbox', 'reset_qmatriz', get_string('resetresponses', 'qmatriz'));
}

/**
 * Course reset defaults.
 *
 * @param stdClass $course
 * @return array
 */
function qmatriz_reset_course_form_defaults($course) {
    return ['reset_qmatriz' => 1];
}

/**
 * Delete user data on course reset.
 *
 * @param stdClass $data
 * @return array status
 */
function qmatriz_reset_userdata($data) {
    global $DB;
    $status = [];
    if (!empty($data->reset_qmatriz)) {
        foreach ($DB->get_fieldset_select('qmatriz', 'id', 'course = ?', [$data->courseid]) as $id) {
            qmatriz_delete_submissions((int)$id);
        }
        $status[] = [
            'component' => get_string('modulenameplural', 'qmatriz'),
            'item' => get_string('resetresponses', 'qmatriz'),
            'error' => false,
        ];
    }
    return $status;
}

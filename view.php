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
 * Questionnaire page: shows the matrix form and stores submissions.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_qmatriz\local\helper;

$id = required_param('id', PARAM_INT);
$done = optional_param('done', 0, PARAM_INT);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'qmatriz');
$qmatriz = $DB->get_record('qmatriz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qmatriz:view', $context);

$url = new moodle_url('/mod/qmatriz/view.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_title(format_string($qmatriz->name));
$PAGE->set_heading(format_string($course->fullname));

$cansubmit = has_capability('mod/qmatriz:submit', $context);
$closedmessage = helper::availability_message($qmatriz);
$alreadysubmitted = empty($qmatriz->multiplesubmissions)
    && $DB->record_exists('qmatriz_submission', ['qmatrizid' => $qmatriz->id, 'userid' => $USER->id]);
$canansweragain = $cansubmit && !$closedmessage && !$alreadysubmitted;
$state = ['listvalue' => '', 'textvalue' => '', 'a' => []];
$errors = [];

if ($canansweragain && data_submitted() && confirm_sesskey()) {
    $state = helper::read_state($qmatriz);
    $errors = helper::validate($qmatriz, $state);
    if (!$errors) {
        $submissionid = helper::save($qmatriz, $state, $USER->id);
        $event = \mod_qmatriz\event\submission_created::create([
            'objectid' => $submissionid,
            'context' => $context,
            'other' => ['rows' => count($state['a'])],
        ]);
        $event->add_record_snapshot('qmatriz', $qmatriz);
        $event->trigger();
        redirect(new moodle_url($url, ['done' => $submissionid]));
    }
} else if (!$done) {
    $event = \mod_qmatriz\event\course_module_viewed::create(['objectid' => $qmatriz->id, 'context' => $context]);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('qmatriz', $qmatriz);
    $event->trigger();
    (new completion_info($course))->set_module_viewed($cm);
}

echo $OUTPUT->header();

if (has_capability('mod/qmatriz:viewreports', $context)) {
    echo html_writer::div($OUTPUT->single_button(
        new moodle_url('/mod/qmatriz/report.php', ['id' => $cm->id]),
        get_string('viewresults', 'qmatriz'),
        'get'
    ), 'mb-3');
}

if ($done) {
    $submission = $DB->get_record('qmatriz_submission', ['id' => $done, 'qmatrizid' => $qmatriz->id, 'userid' => $USER->id]);
    if ($submission) {
        $n = $DB->count_records_sql(
            'SELECT COUNT(DISTINCT rowitem) FROM {qmatriz_answer} WHERE submissionid = ?',
            [$submission->id]
        );
        $thanks = $qmatriz->thankyou !== '' ? $qmatriz->thankyou : get_string('defaultthankyou', 'qmatriz');
        echo html_writer::tag('p', nl2br(s($thanks)), ['class' => 'qmatriz-thanks']);
        echo $OUTPUT->notification(get_string('submitted', 'qmatriz', $n), \core\output\notification::NOTIFY_SUCCESS, false);
        $who = array_values(array_filter([$submission->textvalue, $submission->listvalue], fn($v) => $v !== ''));
        if ($who) {
            echo html_writer::tag('p', s(implode(' · ', $who)));
        }
        echo helper::render_submission($qmatriz, $submission->id);
    }
    if ($canansweragain) {
        echo $OUTPUT->single_button(
            $url,
            get_string('newsubmission', 'qmatriz'),
            'get',
            ['type' => \single_button::BUTTON_PRIMARY]
        );
    }
} else if ($closedmessage) {
    echo $OUTPUT->notification($closedmessage, \core\output\notification::NOTIFY_INFO, false);
} else if (!$cansubmit) {
    echo $OUTPUT->notification(get_string('cannotsubmit', 'qmatriz'), \core\output\notification::NOTIFY_INFO, false);
} else if ($alreadysubmitted) {
    echo $OUTPUT->notification(get_string('alreadysubmitted', 'qmatriz'), \core\output\notification::NOTIFY_INFO, false);
} else {
    if (!empty($qmatriz->timeclose)) {
        echo html_writer::tag('p', get_string('closeson', 'qmatriz', userdate($qmatriz->timeclose)), ['class' => 'text-muted']);
    }
    echo helper::render_form($qmatriz, $cm, $context, $state, $errors);
    $cond = helper::condition_index($qmatriz);
    $PAGE->requires->js_call_amd('mod_qmatriz/matrix', 'init', [[
        'condition' => $cond === null ? -1 : $cond,
        'counttemplate' => get_string('countanswered', 'qmatriz', (object)['n' => '{n}', 'total' => '{total}']),
        'clearlabel' => get_string('clearrow', 'qmatriz'),
    ]]);
}

// The respondent's own previous submissions.
if ($cansubmit) {
    $mine = $DB->get_records_sql(
        "SELECT s.id, s.listvalue, s.textvalue, s.timecreated, COUNT(DISTINCT a.rowitem) AS nrows
           FROM {qmatriz_submission} s
      LEFT JOIN {qmatriz_answer} a ON a.submissionid = s.id
          WHERE s.qmatrizid = :qid AND s.userid = :uid
       GROUP BY s.id, s.listvalue, s.textvalue, s.timecreated
       ORDER BY s.timecreated DESC",
        ['qid' => $qmatriz->id, 'uid' => $USER->id]
    );
    if ($mine) {
        echo $OUTPUT->heading(get_string('mysubmissions', 'qmatriz'), 3, 'mt-5');
        $table = new html_table();
        $table->head = [get_string('date')];
        if (!empty($qmatriz->listenabled)) {
            $table->head[] = s(helper::label($qmatriz, 'list'));
        }
        if (!empty($qmatriz->textenabled)) {
            $table->head[] = s(helper::label($qmatriz, 'text'));
        }
        $table->head[] = s(helper::label($qmatriz, 'row'));
        $table->attributes['class'] = 'generaltable';
        foreach ($mine as $s) {
            $row = [userdate($s->timecreated, get_string('strftimedatetimeshort', 'langconfig'))];
            if (!empty($qmatriz->listenabled)) {
                $row[] = s($s->listvalue);
            }
            if (!empty($qmatriz->textenabled)) {
                $row[] = s($s->textvalue);
            }
            $row[] = html_writer::link(new moodle_url($url, ['done' => $s->id]), (int)$s->nrows);
            $table->data[] = $row;
        }
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();

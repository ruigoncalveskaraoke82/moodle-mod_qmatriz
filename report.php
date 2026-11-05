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
 * Results, export and management of submissions.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use mod_qmatriz\local\helper;

$id = required_param('id', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$type = optional_param('type', 'rows', PARAM_ALPHA);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

[$course, $cm] = get_course_and_cm_from_cmid($id, 'qmatriz');
$qmatriz = $DB->get_record('qmatriz', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/qmatriz:viewreports', $context);

$url = new moodle_url('/mod/qmatriz/report.php', ['id' => $cm->id]);
$PAGE->set_url($url);
$PAGE->set_title(format_string($qmatriz->name) . ': ' . get_string('results', 'qmatriz'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->activityheader->set_description('');

$questions = helper::questions($qmatriz);
$options = helper::options($qmatriz);
$rowheader = helper::label($qmatriz, 'row');
$showidentity = empty($qmatriz->hideidentity);

// Downloads.
if ($download) {
    $filename = clean_filename(format_string($qmatriz->name) . '-' .
        get_string($type === 'summary' ? 'filesummary' : 'fileanswers', 'qmatriz'));
    if ($type === 'summary') {
        $summary = helper::summary($qmatriz->id);
        $columns = ['row' => $rowheader, 'total' => get_string('submissions', 'qmatriz')];
        foreach ($questions as $n => $question) {
            foreach ($options as $i => $option) {
                $columns["q{$n}o{$i}"] = $question['label'] . ' · ' . $option;
            }
        }
        $lines = [];
        foreach ($summary as $row => $data) {
            $line = ['row' => $row, 'total' => $data['total'] ?? 0];
            foreach ($questions as $n => $question) {
                foreach ($options as $i => $option) {
                    $line["q{$n}o{$i}"] = $data['q'][$n][$option] ?? 0;
                }
            }
            $lines[] = $line;
        }
        \core\dataformat::download_data($filename, $download, $columns, $lines);
    } else {
        $columns = ['row' => $rowheader, 'submissionid' => get_string('submission', 'qmatriz'), 'date' => get_string('date')];
        if ($showidentity) {
            $columns['respondent'] = get_string('respondent', 'qmatriz');
            $columns['email'] = get_string('email');
        }
        if (!empty($qmatriz->listenabled)) {
            $columns['listvalue'] = helper::label($qmatriz, 'list');
        }
        if (!empty($qmatriz->textenabled)) {
            $columns['textvalue'] = helper::label($qmatriz, 'text');
        }
        foreach ($questions as $n => $question) {
            $columns['answer' . $n] = $question['label'] . '. ' . $question['text'];
        }
        \core\dataformat::download_data(
            $filename,
            $download,
            $columns,
            helper::answer_lines($qmatriz),
            function ($r) use ($columns) {
                $out = [];
                foreach (array_keys($columns) as $key) {
                    switch ($key) {
                        case 'row':
                            $out[$key] = $r->rowitem;
                            break;
                        case 'date':
                            $out[$key] = userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig'));
                            break;
                        case 'respondent':
                            $out[$key] = fullname($r);
                            break;
                        default:
                            $out[$key] = $r->{$key} ?? '';
                    }
                }
                return $out;
            }
        );
    }
    die();
}

// Delete one submission.
if ($delete) {
    require_capability('mod/qmatriz:deleteresponses', $context);
    $submission = $DB->get_record('qmatriz_submission', ['id' => $delete, 'qmatrizid' => $qmatriz->id], '*', MUST_EXIST);
    if ($confirm && confirm_sesskey()) {
        $DB->delete_records('qmatriz_answer', ['submissionid' => $submission->id]);
        $DB->delete_records('qmatriz_submission', ['id' => $submission->id]);
        redirect($url, get_string('deleted', 'qmatriz'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    $user = core_user::get_user($submission->userid);
    $a = (object)['id' => $submission->id, 'user' => ($user && $showidentity) ? fullname($user) : '-'];
    echo $OUTPUT->confirm(
        get_string('confirmdelete', 'qmatriz', $a),
        new moodle_url($url, ['delete' => $submission->id, 'confirm' => 1, 'sesskey' => sesskey()]),
        $url
    );
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->header();

$nsubmissions = $DB->count_records('qmatriz_submission', ['qmatrizid' => $qmatriz->id]);
if (!$nsubmissions) {
    echo $OUTPUT->notification(get_string('noresponses', 'qmatriz'), \core\output\notification::NOTIFY_INFO, false);
    echo $OUTPUT->footer();
    die();
}
$summary = helper::summary($qmatriz->id);

// Key figures.
$pairs = array_sum(array_map(fn($d) => (int)($d['total'] ?? 0), $summary));
$kpis = '';
foreach (
    [[$nsubmissions, get_string('submissions', 'qmatriz')],
          [count($summary), get_string('rowswithanswers', 'qmatriz', $rowheader)],
          [$pairs, get_string('rowanswers', 'qmatriz')]] as [$value, $label]
) {
    $kpis .= html_writer::div(html_writer::tag('strong', $value) . html_writer::span(s($label)), 'qmatriz-kpi');
}
echo html_writer::div($kpis, 'qmatriz-kpis');

// Summary per row.
echo $OUTPUT->heading(get_string('summarybyrow', 'qmatriz', $rowheader), 3);
$cell = function (array $counts) use ($options, $qmatriz) {
    $total = array_sum($counts);
    if (!$total) {
        return html_writer::span('—', 'text-muted');
    }
    $bar = '';
    $text = [];
    $showzero = count($options) <= 3;
    foreach ($options as $i => $option) {
        $c = $counts[$option] ?? 0;
        if ($c) {
            $key = helper::colour_key($qmatriz, $i);
            $style = 'width:' . max(2, round(70 * $c / $total)) . 'px';
            if ($key === 'a') {
                // Scales: one colour, darker for later options.
                $style .= ';opacity:' . round(0.3 + 0.7 * $i / max(1, count($options) - 1), 2);
            }
            $bar .= html_writer::span('', 'qmatriz-bar-seg qmatriz-bg-' . $key, ['style' => $style,
                'title' => $option . ': ' . $c]);
        }
        if ($c || $showzero) {
            $text[] = s($option) . ': ' . $c;
        }
    }
    // Answers given with options that were later removed from the settings.
    foreach ($counts as $option => $c) {
        if (!in_array((string)$option, $options, true)) {
            $text[] = s($option) . ': ' . $c;
        }
    }
    return html_writer::span($bar, 'qmatriz-bar') . ' ' . html_writer::span(implode(' · ', $text), 'qmatriz-bar-text');
};
$table = new html_table();
$table->head = [s($rowheader), get_string('submissions', 'qmatriz')];
foreach ($questions as $question) {
    $table->head[] = html_writer::span(s($question['label']), '', ['title' => $question['text']]);
}
$table->attributes['class'] = 'generaltable qmatriz-results';
foreach ($summary as $row => $data) {
    $line = [s($row), (int)($data['total'] ?? 0)];
    foreach (array_keys($questions) as $n) {
        $line[] = $cell($data['q'][$n] ?? []);
    }
    $table->data[] = $line;
}
echo html_writer::div(html_writer::table($table), 'table-responsive');

// Legend of the questions.
$legend = '';
foreach ($questions as $question) {
    $legend .= html_writer::tag('li', html_writer::tag('strong', s($question['label'])) . ' ' . s($question['text']));
}
echo html_writer::tag('ul', $legend, ['class' => 'qmatriz-reportlegend list-unstyled small text-muted']);

// Downloads.
echo $OUTPUT->heading(get_string('export', 'qmatriz'), 3, 'mt-4');
echo html_writer::start_div('qmatriz-downloads');
echo $OUTPUT->download_dataformat_selector(
    get_string('downloadanswers', 'qmatriz', $rowheader),
    'report.php',
    'download',
    ['id' => $cm->id, 'type' => 'rows']
);
echo $OUTPUT->download_dataformat_selector(
    get_string('downloadsummary', 'qmatriz', $rowheader),
    'report.php',
    'download',
    ['id' => $cm->id, 'type' => 'summary']
);
echo html_writer::end_div();

// All answers.
echo $OUTPUT->heading(get_string('allanswers', 'qmatriz'), 3, 'mt-4');
$candelete = has_capability('mod/qmatriz:deleteresponses', $context);
$table = new html_table();
$table->head = [s($rowheader), get_string('submission', 'qmatriz'), get_string('date')];
if ($showidentity) {
    $table->head[] = get_string('respondent', 'qmatriz');
}
if (!empty($qmatriz->listenabled)) {
    $table->head[] = s(helper::label($qmatriz, 'list'));
}
if (!empty($qmatriz->textenabled)) {
    $table->head[] = s(helper::label($qmatriz, 'text'));
}
foreach ($questions as $question) {
    $table->head[] = html_writer::span(s($question['label']), '', ['title' => $question['text']]);
}
if ($candelete) {
    $table->head[] = '';
}
$table->attributes['class'] = 'generaltable qmatriz-answers';
foreach (helper::answer_lines($qmatriz) as $r) {
    $line = [s($r->rowitem), '#' . $r->submissionid, userdate($r->timecreated, get_string('strftimedatetimeshort', 'langconfig'))];
    if ($showidentity) {
        $line[] = s(fullname($r));
    }
    if (!empty($qmatriz->listenabled)) {
        $line[] = s($r->listvalue);
    }
    if (!empty($qmatriz->textenabled)) {
        $line[] = s($r->textvalue);
    }
    foreach (array_keys($questions) as $n) {
        $line[] = helper::pill($qmatriz, $r->{'answer' . $n} ?? null);
    }
    if ($candelete) {
        $line[] = $OUTPUT->action_icon(
            new moodle_url($url, ['delete' => $r->submissionid]),
            new pix_icon('t/delete', get_string('deletesubmission', 'qmatriz', $r->submissionid))
        );
    }
    $table->data[] = $line;
}
echo html_writer::div(html_writer::table($table), 'table-responsive');

echo $OUTPUT->footer();

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

namespace mod_qmatriz\local;

use html_writer;
use moodle_url;
use stdClass;

/**
 * Helper functions for mod_qmatriz: configuration, validation, storage and rendering.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    /** Maximum number of questions (columns). */
    public const MAX_QUESTIONS = 10;

    /** Maximum number of answer options. */
    public const MAX_OPTIONS = 10;

    /**
     * Turn a textarea value into a clean list: trimmed, no empty lines, no duplicates.
     *
     * @param string $text
     * @return string[]
     */
    public static function parse_list(string $text): array {
        $out = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(preg_replace('/\s+/u', ' ', $line));
            if ($line !== '' && !in_array($line, $out, true)) {
                $out[] = \core_text::substr($line, 0, 255);
            }
        }
        return $out;
    }

    /**
     * Rows of the matrix, keyed by their position in the settings, optionally sorted alphabetically.
     *
     * @param stdClass $q
     * @return array index => label
     */
    public static function rows(stdClass $q): array {
        $list = self::parse_list($q->rowitems ?? '');
        if (!empty($q->sortrows)) {
            \core_collator::asort($list, \core_collator::SORT_NATURAL);
        }
        return $list;
    }

    /**
     * Questions (columns).
     *
     * @param stdClass $q
     * @return array position (1..n) => ['text' => ..., 'label' => ...]
     */
    public static function questions(stdClass $q): array {
        $texts = array_slice(self::parse_list($q->questions ?? ''), 0, self::MAX_QUESTIONS);
        $labels = preg_split('/\R/u', $q->questionlabels ?? '');
        $out = [];
        foreach ($texts as $i => $text) {
            $label = trim($labels[$i] ?? '');
            $out[$i + 1] = ['text' => $text, 'label' => $label !== '' ? $label : get_string('qn', 'qmatriz', $i + 1)];
        }
        return $out;
    }

    /**
     * Answer options.
     *
     * @param stdClass $q
     * @return string[] index (0..m-1) => text
     */
    public static function options(stdClass $q): array {
        $list = array_slice(self::parse_list($q->answeroptions ?? ''), 0, self::MAX_OPTIONS);
        return $list ?: [get_string('yes'), get_string('no')];
    }

    /**
     * Whether the answer options are a plain Yes / No pair (coloured green and red).
     *
     * @param stdClass $q
     * @return bool
     */
    public static function is_yesno(stdClass $q): bool {
        $o = array_map(fn($v) => \core_text::strtolower(trim($v)), self::options($q));
        if (count($o) !== 2) {
            return false;
        }
        $yes = ['sim', 'yes', \core_text::strtolower(get_string('yes'))];
        $no = ['não', 'nao', 'no', \core_text::strtolower(get_string('no'))];
        return in_array($o[0], $yes, true) && in_array($o[1], $no, true);
    }

    /**
     * CSS colour key for an option: 0 (yes) and 1 (no) for Yes / No, otherwise 'a' (single accent).
     *
     * @param stdClass $q
     * @param int|false $index
     * @return string
     */
    public static function colour_key(stdClass $q, $index): string {
        if ($index === false) {
            return 'x';
        }
        return self::is_yesno($q) ? (string)(int)$index : 'a';
    }

    /**
     * Options of the identification list.
     *
     * @param stdClass $q
     * @return string[]
     */
    public static function list_options(stdClass $q): array {
        return self::parse_list($q->listoptions ?? '');
    }

    /**
     * Index of the option that unlocks the other questions, or null when there is no condition.
     *
     * @param stdClass $q
     * @return int|null
     */
    public static function condition_index(stdClass $q): ?int {
        if (empty($q->conditionenabled) || count(self::questions($q)) < 2) {
            return null;
        }
        $index = array_search($q->conditionvalue, self::options($q), true);
        return $index === false ? null : (int)$index;
    }

    /**
     * Fingerprint of rows, questions and options, to detect changes while someone is answering.
     *
     * @param stdClass $q
     * @return string
     */
    public static function structure_hash(stdClass $q): string {
        return sha1(json_encode([self::parse_list($q->rowitems ?? ''), count(self::questions($q)), self::options($q)]));
    }

    /**
     * Whether the activity is open now. Returns null when open, or a message when not.
     *
     * @param stdClass $q
     * @return string|null
     */
    public static function availability_message(stdClass $q): ?string {
        $now = time();
        if (!empty($q->timeopen) && $now < $q->timeopen) {
            return get_string('notopen', 'qmatriz', userdate($q->timeopen));
        }
        if (!empty($q->timeclose) && $now > $q->timeclose) {
            return get_string('closed', 'qmatriz', userdate($q->timeclose));
        }
        return null;
    }

    /**
     * Read the submitted form into a state array (no validation).
     *
     * @param stdClass $q
     * @return array
     */
    public static function read_state(stdClass $q): array {
        $state = [
            'listvalue' => trim(optional_param('listvalue', '', PARAM_TEXT)),
            'textvalue' => trim(optional_param('textvalue', '', PARAM_TEXT)),
            'hash' => optional_param('structurehash', '', PARAM_ALPHANUM),
            'a' => [],
        ];
        $noptions = count(self::options($q));
        foreach (array_keys(self::questions($q)) as $n) {
            foreach (optional_param_array('a' . $n, [], PARAM_INT) as $k => $v) {
                if ($v >= 0 && $v < $noptions) {
                    $state['a'][(int)$k][$n] = (int)$v;
                }
            }
        }
        return $state;
    }

    /**
     * Validate a submitted state (and drop answers hidden by the condition).
     *
     * @param stdClass $q
     * @param array $state
     * @return string[] error messages (empty when valid)
     */
    public static function validate(stdClass $q, array &$state): array {
        if ($state['hash'] !== self::structure_hash($q)) {
            $state['a'] = [];
            return [get_string('errstructurechanged', 'qmatriz')];
        }
        $errors = [];
        $rows = self::rows($q);
        $questions = self::questions($q);
        $cond = self::condition_index($q);

        if (!empty($q->listenabled)) {
            if ($state['listvalue'] !== '' && !in_array($state['listvalue'], self::list_options($q), true)) {
                $state['listvalue'] = '';
            }
            if (!empty($q->listrequired) && $state['listvalue'] === '') {
                $errors[] = get_string('errlist', 'qmatriz', s(self::label($q, 'list')));
            }
        } else {
            $state['listvalue'] = '';
        }
        if (!empty($q->textenabled)) {
            if (!empty($q->textrequired) && $state['textvalue'] === '') {
                $errors[] = get_string('errtext', 'qmatriz', s(self::label($q, 'text')));
            }
        } else {
            $state['textvalue'] = '';
        }

        $missingfirst = [];
        $incomplete = [];
        foreach ($state['a'] as $k => $answers) {
            if (!isset($rows[$k]) || empty($answers)) {
                unset($state['a'][$k]);
                continue;
            }
            if ($cond !== null) {
                if (!isset($answers[1])) {
                    $missingfirst[] = $rows[$k];
                    continue;
                }
                if ($answers[1] !== $cond) {
                    $state['a'][$k] = [1 => $answers[1]];
                    continue;
                }
            }
            if (!empty($q->requirecomplete) && count($answers) < count($questions)) {
                $incomplete[] = $rows[$k];
            }
        }
        if (empty($state['a'])) {
            $errors[] = get_string('errnone', 'qmatriz', s(self::label($q, 'row')));
        }
        if ($missingfirst) {
            $errors[] = get_string('errmissingfirst', 'qmatriz', (object)[
                'q' => s($questions[1]['label']), 'rows' => s(implode(', ', $missingfirst))]);
        }
        if ($incomplete) {
            $errors[] = get_string('errincomplete', 'qmatriz', s(implode(', ', $incomplete)));
        }
        return $errors;
    }

    /**
     * Store a validated submission.
     *
     * @param stdClass $q
     * @param array $state
     * @param int $userid
     * @return int submission id
     */
    public static function save(stdClass $q, array $state, int $userid): int {
        global $DB;
        $rows = self::rows($q);
        $options = self::options($q);
        $tx = $DB->start_delegated_transaction();
        $now = time();
        $submissionid = $DB->insert_record('qmatriz_submission', (object)[
            'qmatrizid' => $q->id,
            'userid' => $userid,
            'listvalue' => \core_text::substr($state['listvalue'], 0, 255),
            'textvalue' => \core_text::substr($state['textvalue'], 0, 255),
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $records = [];
        foreach ($state['a'] as $k => $answers) {
            ksort($answers);
            foreach ($answers as $n => $optionindex) {
                $records[] = (object)['submissionid' => $submissionid, 'rowitem' => $rows[$k], 'question' => $n,
                    'answer' => $options[$optionindex]];
            }
        }
        $DB->insert_records('qmatriz_answer', $records);
        $tx->allow_commit();
        return $submissionid;
    }

    /**
     * Label texts with their defaults.
     *
     * @param stdClass $q
     * @param string $what list|text|row|ident|matrix
     * @return string
     */
    public static function label(stdClass $q, string $what): string {
        $map = [
            'list' => ['listlabel', 'defaultlistlabel'],
            'text' => ['textlabel', 'defaulttextlabel'],
            'row' => ['rowheader', 'defaultrowheader'],
            'ident' => ['identheading', 'defaultidentheading'],
            'matrix' => ['matrixheading', 'defaultmatrixheading'],
        ];
        [$field, $default] = $map[$what];
        return ($q->{$field} ?? '') !== '' ? $q->{$field} : get_string($default, 'qmatriz');
    }

    /**
     * Render the questionnaire form (the matrix).
     *
     * @param stdClass $q
     * @param \cm_info|stdClass $cm
     * @param \context_module $context
     * @param array $state
     * @param string[] $errors
     * @return string HTML
     */
    public static function render_form(stdClass $q, $cm, \context_module $context, array $state, array $errors): string {
        global $OUTPUT;
        $rows = self::rows($q);
        $questions = self::questions($q);
        $options = self::options($q);
        $cond = self::condition_index($q);
        $secnum = 0;
        $roman = ['I', 'II', 'III'];

        $o = '';
        if ($errors) {
            $o .= $OUTPUT->notification(implode('<br>', $errors), \core\output\notification::NOTIFY_ERROR, false);
        }
        $o .= html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/mod/qmatriz/view.php'),
            'id' => 'mod-qmatriz-form', 'class' => 'mod-qmatriz-form']);
        $o .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        $o .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $o .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'structurehash', 'value' => self::structure_hash($q)]);

        // Identification section (only when at least one field is on).
        if (!empty($q->listenabled) || !empty($q->textenabled)) {
            $o .= self::section_heading($roman[$secnum++], self::label($q, 'ident'));
            $o .= html_writer::start_div('qmatriz-ident');
            if (!empty($q->listenabled)) {
                $opts = ['' => get_string('choose', 'qmatriz')];
                foreach (self::list_options($q) as $opt) {
                    $opts[$opt] = $opt;
                }
                $attrs = ['id' => 'qmatriz-listvalue', 'class' => 'custom-select form-select'];
                if (!empty($q->listrequired)) {
                    $attrs['required'] = 'required';
                }
                $o .= html_writer::div(html_writer::label(s(self::label($q, 'list')), 'qmatriz-listvalue') .
                    html_writer::select($opts, 'listvalue', $state['listvalue'] ?? '', false, $attrs), 'qmatriz-field');
            }
            if (!empty($q->textenabled)) {
                $attrs = ['type' => 'text', 'name' => 'textvalue', 'id' => 'qmatriz-textvalue', 'class' => 'form-control',
                    'maxlength' => 255, 'value' => $state['textvalue'] ?? '', 'autocomplete' => 'off'];
                if (!empty($q->textrequired)) {
                    $attrs['required'] = 'required';
                }
                $o .= html_writer::div(html_writer::label(s(self::label($q, 'text')), 'qmatriz-textvalue') .
                    html_writer::empty_tag('input', $attrs), 'qmatriz-field');
            }
            $o .= html_writer::end_div();
        }

        // Matrix section.
        $o .= self::section_heading($roman[$secnum++], self::label($q, 'matrix'));
        if (trim(strip_tags($q->instructions ?? '', '<img>')) !== '') {
            $o .= html_writer::div(
                format_text($q->instructions, $q->instructionsformat, ['context' => $context]),
                'qmatriz-instructions'
            );
        }
        $items = '';
        foreach ($questions as $n => $question) {
            $extra = '';
            if ($cond !== null && $n > 1) {
                $extra = ' ' . html_writer::span(get_string('ifcondition', 'qmatriz', (object)[
                    'q' => s($questions[1]['label']), 'value' => s($options[$cond])]), 'text-muted small');
            }
            $items .= html_writer::tag('li', html_writer::span(s($question['label']), 'qmatriz-qtag') .
                html_writer::span(s($question['text']) . $extra));
        }
        $o .= html_writer::tag('ol', $items, ['class' => 'qmatriz-legend']);

        $o .= html_writer::start_div('qmatriz-toolbar');
        $o .= html_writer::empty_tag('input', ['type' => 'search', 'class' => 'form-control qmatriz-search',
            'placeholder' => get_string('search', 'qmatriz'), 'aria-label' => get_string('search', 'qmatriz')]);
        $o .= html_writer::tag('label', html_writer::empty_tag('input', ['type' => 'checkbox', 'class' => 'qmatriz-onlyanswered']) .
            ' ' . get_string('onlyanswered', 'qmatriz'), ['class' => 'qmatriz-only']);
        $o .= html_writer::span('', 'qmatriz-count', ['aria-live' => 'polite']);
        $o .= html_writer::end_div();

        $head = html_writer::tag('th', s(self::label($q, 'row')), ['scope' => 'col']);
        foreach ($questions as $n => $question) {
            $head .= html_writer::tag('th', s($question['label']), ['scope' => 'col', 'class' => 'qmatriz-q',
                'title' => $question['text']]);
        }
        $head .= html_writer::tag(
            'th',
            html_writer::span(get_string('clearrow', 'qmatriz'), 'sr-only visually-hidden'),
            ['scope' => 'col', 'class' => 'qmatriz-x']
        );

        $body = '';
        foreach ($rows as $k => $row) {
            $answers = $state['a'][$k] ?? [];
            $cells = html_writer::tag('td', s($row), ['class' => 'qmatriz-c']);
            foreach ($questions as $n => $question) {
                $na = ($cond !== null && $n > 1 && isset($answers[1]) && $answers[1] !== $cond);
                $seg = '';
                foreach ($options as $i => $option) {
                    $id = "qmatriz-a{$n}-{$k}-{$i}";
                    $attrs = ['type' => 'radio', 'name' => "a{$n}[{$k}]", 'value' => $i, 'id' => $id,
                        'class' => 'qmatriz-r qmatriz-o' . self::colour_key($q, $i), 'data-q' => $n];
                    if (isset($answers[$n]) && $answers[$n] === $i) {
                        $attrs['checked'] = 'checked';
                    }
                    $seg .= html_writer::empty_tag('input', $attrs) . html_writer::label(s($option), $id);
                }
                $cells .= html_writer::tag(
                    'td',
                    html_writer::span(s($question['label']), 'qmatriz-mlabel') .
                    html_writer::span($seg, 'qmatriz-seg' . ($na ? ' qmatriz-na' : ''), ['role' => 'radiogroup',
                        'aria-label' => $question['label'] . ' — ' . $row, 'data-na' => get_string('na', 'qmatriz')]),
                    ['class' => 'qmatriz-q']
                );
            }
            $cells .= html_writer::tag('td', '', ['class' => 'qmatriz-x']);
            $body .= html_writer::tag('tr', $cells, ['class' => $answers ? 'qmatriz-on' : '']);
        }
        $body .= html_writer::tag('tr', html_writer::tag(
            'td',
            get_string('nomatch', 'qmatriz'),
            ['colspan' => count($questions) + 2]
        ), ['class' => 'qmatriz-empty', 'hidden' => 'hidden']);

        $o .= html_writer::div(html_writer::tag('table', html_writer::tag('thead', html_writer::tag('tr', $head)) .
            html_writer::tag('tbody', $body), ['class' => 'qmatriz-matrix']), 'qmatriz-tablebox');
        $o .= html_writer::div(html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary',
            'value' => get_string('submitanswers', 'qmatriz')]), 'qmatriz-actions');
        $o .= html_writer::end_tag('form');
        return $o;
    }

    /**
     * Section heading.
     *
     * @param string $number
     * @param string $text
     * @return string
     */
    protected static function section_heading(string $number, string $text): string {
        return html_writer::tag(
            'h3',
            html_writer::span($number . '.', 'qmatriz-secnum') . ' ' . s($text),
            ['class' => 'qmatriz-section']
        );
    }

    /**
     * Summary table of one submission.
     *
     * @param stdClass $q
     * @param int $submissionid
     * @return string HTML
     */
    public static function render_submission(stdClass $q, int $submissionid): string {
        global $DB;
        $questions = self::questions($q);
        $grid = [];
        foreach ($DB->get_records('qmatriz_answer', ['submissionid' => $submissionid], 'rowitem ASC, question ASC') as $a) {
            $grid[$a->rowitem][$a->question] = $a->answer;
        }
        $table = new \html_table();
        $table->head = [s(self::label($q, 'row'))];
        foreach ($questions as $question) {
            $table->head[] = s($question['label']);
        }
        $table->attributes['class'] = 'generaltable qmatriz-summary';
        foreach ($grid as $row => $answers) {
            $line = [s($row)];
            foreach (array_keys($questions) as $n) {
                $line[] = self::pill($q, $answers[$n] ?? null);
            }
            $table->data[] = $line;
        }
        return html_writer::table($table);
    }

    /**
     * Coloured badge for an answer.
     *
     * @param stdClass $q
     * @param string|null $answer
     * @return string
     */
    public static function pill(stdClass $q, ?string $answer): string {
        if ($answer === null || $answer === '') {
            return html_writer::span('—', 'text-muted');
        }
        $index = array_search($answer, self::options($q), true);
        $class = 'qmatriz-pill-' . self::colour_key($q, $index);
        return html_writer::span(s($answer), 'badge qmatriz-pill ' . $class);
    }

    /**
     * Aggregated counts per row and question.
     *
     * @param int $qmatrizid
     * @return array rowitem => ['total' => submissions, 'q' => [question => [answer => count]]]
     */
    public static function summary(int $qmatrizid): array {
        global $DB;
        $out = [];
        $rs = $DB->get_recordset_sql(
            "SELECT a.rowitem, a.question, a.answer, COUNT(1) AS c
               FROM {qmatriz_answer} a
               JOIN {qmatriz_submission} s ON s.id = a.submissionid
              WHERE s.qmatrizid = :id
           GROUP BY a.rowitem, a.question, a.answer",
            ['id' => $qmatrizid]
        );
        foreach ($rs as $r) {
            $out[$r->rowitem]['q'][(int)$r->question][$r->answer] = (int)$r->c;
        }
        $rs->close();
        $totals = $DB->get_records_sql_menu(
            "SELECT a.rowitem, COUNT(DISTINCT a.submissionid)
               FROM {qmatriz_answer} a
               JOIN {qmatriz_submission} s ON s.id = a.submissionid
              WHERE s.qmatrizid = :id
           GROUP BY a.rowitem",
            ['id' => $qmatrizid]
        );
        foreach ($totals as $row => $total) {
            $out[$row]['total'] = (int)$total;
        }
        uksort($out, function ($a, $b) use ($out) {
            return ($out[$b]['total'] <=> $out[$a]['total']) ?: \core_text::strtolower($a) <=> \core_text::strtolower($b);
        });
        return $out;
    }

    /**
     * One record per row item per submission, with the answers as answerN fields, ordered by row item.
     *
     * @param stdClass $q
     * @return \Generator
     */
    public static function answer_lines(stdClass $q): \Generator {
        global $DB;
        $namefields = 'u.' . implode(', u.', \core_user\fields::get_name_fields());
        $rs = $DB->get_recordset_sql(
            "SELECT a.id, a.rowitem, a.question, a.answer,
                    s.id AS submissionid, s.userid, s.listvalue, s.textvalue, s.timecreated,
                    u.email, {$namefields}
               FROM {qmatriz_answer} a
               JOIN {qmatriz_submission} s ON s.id = a.submissionid
               JOIN {user} u ON u.id = s.userid
              WHERE s.qmatrizid = :id
           ORDER BY a.rowitem ASC, s.timecreated ASC, s.id ASC, a.question ASC",
            ['id' => $q->id]
        );
        $current = null;
        foreach ($rs as $r) {
            $key = $r->submissionid . '|' . $r->rowitem;
            if ($current === null || $current->key !== $key) {
                if ($current !== null) {
                    yield $current;
                }
                $current = clone $r;
                $current->key = $key;
            }
            $current->{'answer' . (int)$r->question} = $r->answer;
        }
        if ($current !== null) {
            yield $current;
        }
        $rs->close();
    }
}

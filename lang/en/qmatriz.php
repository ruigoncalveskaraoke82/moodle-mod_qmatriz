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
 * Strings for mod_qmatriz (en).
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allanswers'] = 'All answers';
$string['alreadysubmitted'] = 'You have already answered this questionnaire.';
$string['answeroptions'] = 'Answer options (one per line)';
$string['answeroptions_help'] = 'The same options are offered for every question, for example "Yes / No", "Yes / No / Not applicable" or a scale "1 / 2 / 3 / 4 / 5". Between 2 and 10 options.';
$string['cannotsubmit'] = 'You can see this questionnaire but you are not allowed to answer it.';
$string['choose'] = 'Select…';
$string['clearrow'] = 'Clear row';
$string['closed'] = 'This questionnaire closed on {$a}.';
$string['closeson'] = 'Open until {$a}.';
$string['conditionenabled'] = 'The first question is a condition';
$string['conditionenabled_help'] = 'When on, the other questions of a row only become available after the chosen answer is given to the first question. With any other answer they are marked n/a.';
$string['conditionvalue'] = 'Answer that unlocks the other questions';
$string['confirmdelete'] = 'Delete submission #{$a->id} ({$a->user})? This cannot be undone.';
$string['countanswered'] = '{$a->n} of {$a->total} answered';
$string['defaultansweroptions'] = "Yes\nNo";
$string['defaultidentheading'] = 'Identification';
$string['defaultlistlabel'] = 'Select';
$string['defaultmatrixheading'] = 'Items to evaluate';
$string['defaultrowheader'] = 'Item';
$string['defaulttextlabel'] = 'Name';
$string['defaultthankyou'] = 'Thank you for your participation!';
$string['deleted'] = 'Submission deleted.';
$string['deletesubmission'] = 'Delete submission #{$a}';
$string['downloadanswers'] = 'Download answers (one line per {$a})';
$string['downloadsummary'] = 'Download summary by {$a}';
$string['errincomplete'] = 'Answer every question in: {$a}.';
$string['errlist'] = 'Select an option in "{$a}".';
$string['errmissingfirst'] = 'Question {$a->q} is missing in: {$a->rows}.';
$string['errnone'] = 'Answer at least one row of the table ({$a}).';
$string['errorconditionquestions'] = 'A condition needs at least two questions.';
$string['errorconditionvalue'] = 'Write exactly one of the answer options.';
$string['errorlistempty'] = 'Add at least one line.';
$string['errortimeclose'] = 'The closing date must be after the opening date.';
$string['errortoomany'] = 'At most {$a} lines.';
$string['errortwooptions'] = 'Add at least two answer options.';
$string['errstructurechanged'] = 'The questionnaire was changed while you were answering. Please fill in the table again.';
$string['errtext'] = 'Fill in "{$a}".';
$string['eventsubmissioncreated'] = 'Questionnaire answered';
$string['export'] = 'Export';
$string['fieldrequired'] = 'Required';
$string['fileanswers'] = 'answers';
$string['filesummary'] = 'summary';
$string['hideidentity'] = 'Hide who answered in results and exports';
$string['hideidentity_help'] = 'Results and exports do not show the respondent\'s name or email. Moodle still records who answered, so that each person can see their own submissions and to respect the "one submission" setting.';
$string['identhdr'] = 'Identification section';
$string['identheading'] = 'Section title';
$string['ifcondition'] = '(if {$a->q} = {$a->value})';
$string['instructions'] = 'Instructions';
$string['instructions_help'] = 'Text shown above the table. Leave empty to show nothing.';
$string['listenabled'] = 'Show a list to choose from';
$string['listlabel'] = 'List label';
$string['listoptions'] = 'List options (one per line)';
$string['listoptions_help'] = 'For example posts, services or departments. Empty and repeated lines are ignored.';
$string['matrixhdr'] = 'Table section';
$string['matrixheading'] = 'Section title';
$string['modulename'] = 'Matrix questionnaire';
$string['modulename_help'] = 'A questionnaire shown as a table: one row per item (for example a training action) and the questions in columns. Each row is answered separately, so several items are answered in a single submission. Every text, the questions and the answer options are set in each activity.';
$string['modulenameplural'] = 'Matrix questionnaires';
$string['multiplesubmissions'] = 'Allow several submissions per person';
$string['multiplesubmissions_help'] = 'On: the same person can answer several times (for example, a manager evaluating several employees). Off: one submission per person.';
$string['mysubmissions'] = 'My submissions';
$string['na'] = 'n/a';
$string['newsubmission'] = 'Answer again';
$string['nomatch'] = 'Nothing matches the search.';
$string['noresponses'] = 'No answers yet.';
$string['notopen'] = 'This questionnaire opens on {$a}.';
$string['onlyanswered'] = 'Show only answered rows';
$string['pluginadministration'] = 'Matrix questionnaire administration';
$string['pluginname'] = 'Matrix questionnaire';
$string['privacy:metadata:qmatriz_answer'] = 'Answers given in each row of a submission.';
$string['privacy:metadata:qmatriz_answer:answer'] = 'The option chosen.';
$string['privacy:metadata:qmatriz_answer:question'] = 'The question (column) answered.';
$string['privacy:metadata:qmatriz_answer:rowitem'] = 'The row answered.';
$string['privacy:metadata:qmatriz_submission'] = 'Questionnaires submitted by users.';
$string['privacy:metadata:qmatriz_submission:listvalue'] = 'The option chosen in the identification list.';
$string['privacy:metadata:qmatriz_submission:textvalue'] = 'The text typed in the identification field.';
$string['privacy:metadata:qmatriz_submission:timecreated'] = 'When the questionnaire was submitted.';
$string['privacy:metadata:qmatriz_submission:userid'] = 'The user who submitted.';
$string['qmatriz:addinstance'] = 'Add a new matrix questionnaire';
$string['qmatriz:deleteresponses'] = 'Delete submissions';
$string['qmatriz:submit'] = 'Answer a matrix questionnaire';
$string['qmatriz:view'] = 'View a matrix questionnaire';
$string['qmatriz:viewreports'] = 'View results and export answers';
$string['qn'] = 'Q{$a}';
$string['questionlabels'] = 'Column headings (optional, one per line)';
$string['questionlabels_help'] = 'Short heading for each question, on the same line number as the question. Empty lines use Q1, Q2, …';
$string['questions'] = 'Questions (one per line)';
$string['questions_help'] = 'Each line becomes a column of the table, in this order. Up to 10 questions.';
$string['questionshdr'] = 'Questions and answers';
$string['requirecomplete'] = 'Every question is required in an answered row';
$string['requirecomplete_help'] = 'When a row is answered, all its questions must be answered. With a condition, this only applies when the condition is met.';
$string['resetresponses'] = 'Delete all matrix questionnaire answers';
$string['respondent'] = 'Respondent';
$string['results'] = 'Results';
$string['rowanswers'] = 'Answered rows';
$string['rowheader'] = 'Row heading';
$string['rowitems'] = 'Rows (one per line)';
$string['rowitems_help'] = 'Each line becomes a row of the table, for example the training actions. Empty and repeated lines are ignored. Changing this list does not change answers already given.';
$string['rowswithanswers'] = '{$a} with answers';
$string['search'] = 'Search';
$string['sortrows'] = 'Sort rows alphabetically';
$string['submission'] = 'Submission';
$string['submissions'] = 'Submissions';
$string['submissionshdr'] = 'Submissions, availability and final message';
$string['submitanswers'] = 'Submit';
$string['submitted'] = 'Answers submitted: {$a} row(s) in a single submission.';
$string['summarybyrow'] = 'Summary by {$a}';
$string['textenabled'] = 'Show a text field';
$string['textlabel'] = 'Text field label';
$string['thankyou'] = 'Final message';
$string['timeclose'] = 'Available until';
$string['timeopen'] = 'Available from';
$string['viewresults'] = 'View results';

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
 * Settings form for mod_qmatriz.
 *
 * @package    mod_qmatriz
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

use mod_qmatriz\local\helper;

/**
 * Activity settings form.
 */
class mod_qmatriz_mod_form extends moodleform_mod {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        // I. Identification.
        $mform->addElement('header', 'identhdr', get_string('identhdr', 'qmatriz'));
        $mform->setExpanded('identhdr');
        $this->add_text('identheading', 'identheading', get_string('defaultidentheading', 'qmatriz'));
        $mform->addElement('advcheckbox', 'listenabled', get_string('listenabled', 'qmatriz'));
        $mform->setDefault('listenabled', 1);
        $this->add_text('listlabel', 'listlabel', get_string('defaultlistlabel', 'qmatriz'));
        $mform->addElement('textarea', 'listoptions', get_string('listoptions', 'qmatriz'), ['rows' => 10, 'cols' => 70]);
        $mform->setType('listoptions', PARAM_TEXT);
        $mform->addHelpButton('listoptions', 'listoptions', 'qmatriz');
        $mform->addElement('advcheckbox', 'listrequired', get_string('fieldrequired', 'qmatriz'));
        $mform->setDefault('listrequired', 1);
        foreach (['listlabel', 'listoptions', 'listrequired'] as $f) {
            $mform->hideIf($f, 'listenabled', 'notchecked');
        }
        $mform->addElement('advcheckbox', 'textenabled', get_string('textenabled', 'qmatriz'));
        $mform->setDefault('textenabled', 0);
        $this->add_text('textlabel', 'textlabel', get_string('defaulttextlabel', 'qmatriz'));
        $mform->addElement('advcheckbox', 'textrequired', get_string('fieldrequired', 'qmatriz'));
        $mform->setDefault('textrequired', 1);
        foreach (['textlabel', 'textrequired'] as $f) {
            $mform->hideIf($f, 'textenabled', 'notchecked');
        }

        // II. Matrix.
        $mform->addElement('header', 'matrixhdr', get_string('matrixhdr', 'qmatriz'));
        $mform->setExpanded('matrixhdr');
        $this->add_text('matrixheading', 'matrixheading', get_string('defaultmatrixheading', 'qmatriz'));
        $mform->addElement(
            'editor',
            'instructions_editor',
            get_string('instructions', 'qmatriz'),
            ['rows' => 5],
            ['maxfiles' => 0, 'noclean' => false, 'context' => $this->context]
        );
        $mform->setType('instructions_editor', PARAM_RAW);
        $mform->addHelpButton('instructions_editor', 'instructions', 'qmatriz');
        $this->add_text('rowheader', 'rowheader', get_string('defaultrowheader', 'qmatriz'));
        $mform->addElement('textarea', 'rowitems', get_string('rowitems', 'qmatriz'), ['rows' => 15, 'cols' => 70]);
        $mform->setType('rowitems', PARAM_TEXT);
        $mform->addRule('rowitems', null, 'required', null, 'client');
        $mform->addHelpButton('rowitems', 'rowitems', 'qmatriz');
        $mform->addElement('advcheckbox', 'sortrows', get_string('sortrows', 'qmatriz'));
        $mform->setDefault('sortrows', 1);

        // Questions and answers.
        $mform->addElement('header', 'questionshdr', get_string('questionshdr', 'qmatriz'));
        $mform->setExpanded('questionshdr');
        $mform->addElement('textarea', 'questions', get_string('questions', 'qmatriz'), ['rows' => 5, 'cols' => 70]);
        $mform->setType('questions', PARAM_TEXT);
        $mform->addRule('questions', null, 'required', null, 'client');
        $mform->addHelpButton('questions', 'questions', 'qmatriz');
        $mform->addElement('textarea', 'questionlabels', get_string('questionlabels', 'qmatriz'), ['rows' => 4, 'cols' => 40]);
        $mform->setType('questionlabels', PARAM_TEXT);
        $mform->addHelpButton('questionlabels', 'questionlabels', 'qmatriz');
        $mform->addElement('textarea', 'answeroptions', get_string('answeroptions', 'qmatriz'), ['rows' => 4, 'cols' => 40]);
        $mform->setType('answeroptions', PARAM_TEXT);
        $mform->setDefault('answeroptions', get_string('defaultansweroptions', 'qmatriz'));
        $mform->addRule('answeroptions', null, 'required', null, 'client');
        $mform->addHelpButton('answeroptions', 'answeroptions', 'qmatriz');
        $mform->addElement('advcheckbox', 'conditionenabled', get_string('conditionenabled', 'qmatriz'));
        $mform->addHelpButton('conditionenabled', 'conditionenabled', 'qmatriz');
        $this->add_text('conditionvalue', 'conditionvalue', get_string('yes'));
        $mform->hideIf('conditionvalue', 'conditionenabled', 'notchecked');
        $mform->addElement('advcheckbox', 'requirecomplete', get_string('requirecomplete', 'qmatriz'));
        $mform->setDefault('requirecomplete', 1);
        $mform->addHelpButton('requirecomplete', 'requirecomplete', 'qmatriz');

        // Submissions, availability and final message.
        $mform->addElement('header', 'submissionshdr', get_string('submissionshdr', 'qmatriz'));
        $mform->addElement('advcheckbox', 'multiplesubmissions', get_string('multiplesubmissions', 'qmatriz'));
        $mform->setDefault('multiplesubmissions', 1);
        $mform->addHelpButton('multiplesubmissions', 'multiplesubmissions', 'qmatriz');
        $mform->addElement('advcheckbox', 'hideidentity', get_string('hideidentity', 'qmatriz'));
        $mform->addHelpButton('hideidentity', 'hideidentity', 'qmatriz');
        $mform->addElement('date_time_selector', 'timeopen', get_string('timeopen', 'qmatriz'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'timeclose', get_string('timeclose', 'qmatriz'), ['optional' => true]);
        $mform->addElement('textarea', 'thankyou', get_string('thankyou', 'qmatriz'), ['rows' => 3, 'cols' => 70]);
        $mform->setType('thankyou', PARAM_TEXT);
        $mform->setDefault('thankyou', get_string('defaultthankyou', 'qmatriz'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Add a single-line text setting with a default.
     *
     * @param string $name
     * @param string $string
     * @param string $default
     */
    protected function add_text(string $name, string $string, string $default) {
        $mform = $this->_form;
        $mform->addElement('text', $name, get_string($string, 'qmatriz'), ['size' => 64, 'maxlength' => 255]);
        $mform->setType($name, PARAM_TEXT);
        $mform->setDefault($name, $default);
    }

    /**
     * Load the instructions into the editor.
     *
     * @param array $defaults
     */
    public function data_preprocessing(&$defaults) {
        parent::data_preprocessing($defaults);
        if ($this->current->instance) {
            $defaults['instructions_editor'] = [
                'text' => $defaults['instructions'] ?? '',
                'format' => $defaults['instructionsformat'] ?? FORMAT_HTML,
            ];
        }
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty(helper::parse_list($data['rowitems'] ?? ''))) {
            $errors['rowitems'] = get_string('errorlistempty', 'qmatriz');
        }
        if (!empty($data['listenabled']) && empty(helper::parse_list($data['listoptions'] ?? ''))) {
            $errors['listoptions'] = get_string('errorlistempty', 'qmatriz');
        }
        $questions = helper::parse_list($data['questions'] ?? '');
        if (!$questions) {
            $errors['questions'] = get_string('errorlistempty', 'qmatriz');
        } else if (count($questions) > helper::MAX_QUESTIONS) {
            $errors['questions'] = get_string('errortoomany', 'qmatriz', helper::MAX_QUESTIONS);
        }
        $options = helper::parse_list($data['answeroptions'] ?? '');
        if (count($options) < 2) {
            $errors['answeroptions'] = get_string('errortwooptions', 'qmatriz');
        } else if (count($options) > helper::MAX_OPTIONS) {
            $errors['answeroptions'] = get_string('errortoomany', 'qmatriz', helper::MAX_OPTIONS);
        }
        if (!empty($data['conditionenabled'])) {
            if (count($questions) < 2) {
                $errors['conditionenabled'] = get_string('errorconditionquestions', 'qmatriz');
            } else if (!in_array(trim($data['conditionvalue'] ?? ''), $options, true)) {
                $errors['conditionvalue'] = get_string('errorconditionvalue', 'qmatriz');
            }
        }
        if (!empty($data['timeopen']) && !empty($data['timeclose']) && $data['timeclose'] <= $data['timeopen']) {
            $errors['timeclose'] = get_string('errortimeclose', 'qmatriz');
        }
        return $errors;
    }
}

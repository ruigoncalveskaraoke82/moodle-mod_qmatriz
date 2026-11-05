# Matrix questionnaire (mod_qmatriz)

An activity module for Moodle that shows a questionnaire as a table: one row per item
(for example, a training action, a service or a department) and the questions in columns.
Each row is answered separately, so a respondent can evaluate many items in a single submission.

## Features

- Rows, questions (up to 10 columns) and answer options (2 to 10) are set per activity, one per line.
- Optional identification section: a drop-down list and/or a free-text field, each optional or required.
- Optional condition: the other questions of a row only unlock when a chosen answer is given to the first question.
- Search box, "only answered rows" filter, row clearing and an answered-rows counter (the form also works without JavaScript).
- One or several submissions per person, opening and closing dates and a custom final message.
- Results page with a summary per row, all answers, and downloads in every Moodle data format (CSV, Excel, ODS, JSON, HTML, PDF).
- Option to hide respondents' identity in results and exports.
- Backup and restore, course reset, completion on view, events and a full Privacy API implementation.

## Requirements

Moodle 4.5 or later.

## Installation

1. Copy this folder to `mod/qmatriz` in your Moodle installation
   (or install the ZIP from *Site administration > Plugins > Install plugins*).
2. Visit *Site administration > Notifications* to complete the installation.
3. Add a "Matrix questionnaire" activity to a course.

## Capabilities

| Capability | Default roles |
|---|---|
| `mod/qmatriz:addinstance` | Editing teacher, Manager |
| `mod/qmatriz:view` | Student, Teacher, Editing teacher, Manager |
| `mod/qmatriz:submit` | Student |
| `mod/qmatriz:viewreports` | Teacher, Editing teacher, Manager |
| `mod/qmatriz:deleteresponses` | Editing teacher, Manager |

## License

2026 Rui Pratas

This program is free software: you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version. See <https://www.gnu.org/licenses/>.

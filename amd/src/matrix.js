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
 * Interactive behaviour of the matrix: optional condition on the first question, search,
 * "only answered" filter, row clearing and counter. The form also works without JavaScript.
 *
 * @module     mod_qmatriz/matrix
 * @copyright  2026 Rui Pratas
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    var norm = function(s) {
        return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    };

    var checkedValue = function(tr, q) {
        var el = tr.querySelector('input.qmatriz-r[data-q="' + q + '"]:checked');
        return el ? parseInt(el.value, 10) : null;
    };

    var updateRow = function(tr, cfg) {
        if (cfg.condition >= 0) {
            var first = checkedValue(tr, 1);
            var open = (first === cfg.condition);
            tr.querySelectorAll('.qmatriz-seg').forEach(function(seg) {
                var input = seg.querySelector('input.qmatriz-r');
                if (!input || input.getAttribute('data-q') === '1') {
                    return;
                }
                seg.querySelectorAll('input.qmatriz-r').forEach(function(r) {
                    if (!open) {
                        r.checked = false;
                    }
                    r.disabled = !open;
                });
                seg.classList.toggle('qmatriz-off', !open);
                seg.classList.toggle('qmatriz-na', first !== null && !open);
            });
        }
        var answered = !!tr.querySelector('input.qmatriz-r:checked');
        tr.classList.toggle('qmatriz-on', answered);
        var cell = tr.querySelector('td.qmatriz-x');
        if (cell) {
            cell.innerHTML = '';
            if (answered) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-link btn-sm qmatriz-clear';
                btn.setAttribute('aria-label', cfg.clearlabel);
                btn.title = cfg.clearlabel;
                btn.textContent = '×';
                cell.appendChild(btn);
            }
        }
    };

    var refresh = function(root, cfg) {
        var rows = root.querySelectorAll('table.qmatriz-matrix tbody tr:not(.qmatriz-empty)');
        var term = norm(root.querySelector('.qmatriz-search').value.trim());
        var only = root.querySelector('.qmatriz-onlyanswered').checked;
        var shown = 0;
        var answered = 0;
        rows.forEach(function(tr) {
            var isOn = tr.classList.contains('qmatriz-on');
            if (isOn) {
                answered++;
            }
            var name = norm(tr.querySelector('td.qmatriz-c').textContent);
            var visible = (!term || name.indexOf(term) !== -1) && (!only || isOn);
            tr.hidden = !visible;
            if (visible) {
                shown++;
            }
        });
        root.querySelector('tr.qmatriz-empty').hidden = shown > 0;
        root.querySelector('.qmatriz-count').textContent =
            cfg.counttemplate.replace('{n}', answered).replace('{total}', rows.length);
    };

    return {
        /**
         * Initialise.
         *
         * @param {Object} cfg condition (option index or -1), counttemplate, clearlabel
         */
        init: function(cfg) {
            var root = document.getElementById('mod-qmatriz-form');
            if (!root) {
                return;
            }
            root.classList.add('qmatriz-js');
            root.querySelectorAll('table.qmatriz-matrix tbody tr:not(.qmatriz-empty)').forEach(function(tr) {
                updateRow(tr, cfg);
            });
            refresh(root, cfg);

            root.addEventListener('change', function(e) {
                if (e.target.classList.contains('qmatriz-r')) {
                    updateRow(e.target.closest('tr'), cfg);
                    refresh(root, cfg);
                    e.target.focus();
                } else if (e.target.classList.contains('qmatriz-onlyanswered')) {
                    refresh(root, cfg);
                }
            });
            root.addEventListener('click', function(e) {
                var btn = e.target.closest('.qmatriz-clear');
                if (!btn) {
                    return;
                }
                var tr = btn.closest('tr');
                tr.querySelectorAll('input.qmatriz-r').forEach(function(input) {
                    input.checked = false;
                });
                updateRow(tr, cfg);
                refresh(root, cfg);
            });
            var search = root.querySelector('.qmatriz-search');
            search.addEventListener('input', function() {
                refresh(root, cfg);
            });
            search.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
        }
    };
});

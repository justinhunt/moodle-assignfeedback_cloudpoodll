<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace assignfeedback_cloudpoodll;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use context_module;

/**
 * Web services for the corrections feedback.
 *
 * @package    assignfeedback_cloudpoodll
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {
    /**
     * Get AI grammar corrections of a student's text, for the teacher's corrections feedback.
     *
     * Returns a JSON object. "error" is empty on success, or says why no corrections could be fetched.
     * With no error, grammarerrors, grammarmatches, suggestions, markedupsuggestions and insertioncount
     * describe the corrections; grammarerrors is "[]" when the text needs none.
     *
     * @param int $cmid the assignment's course module id
     * @param string $text the student's text
     * @param string $language the language of the text, such as en-US
     * @return string JSON
     */
    public static function check_grammar($cmid, $text, $language) {
        $params = self::validate_parameters(self::check_grammar_parameters(), [
            'cmid' => $cmid,
            'text' => $text,
            'language' => $language,
        ]);
        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('assignfeedback/cloudpoodll:use', $context);

        $text = $params['text'];
        $ret = [
            'error' => '',
            'grammarerrors' => '[]',
            'grammarmatches' => '[]',
            'suggestions' => '',
            'markedupsuggestions' => '',
            'insertioncount' => 0,
        ];

        $siteconfig = get_config(constants::M_COMPONENT);
        // A Moodle AI provider needs no Cloud Poodll token.
        $token = aimanager::uses_moodle_ai() ? '' : utils::fetch_token($siteconfig->apiuser, $siteconfig->apisecret);
        $textanalyser = new textanalyser($token, $text, $siteconfig->awsregion, $params['language'], false, false, $context->id);
        $suggestions = $textanalyser->fetch_grammar_correction();
        if ($suggestions === false) {
            $reason = $textanalyser->get_error_message();
            $ret['error'] = empty($reason)
                ? get_string('grammarcheckfailed', constants::M_COMPONENT)
                : get_string('grammarcheckfailedreason', constants::M_COMPONENT, $reason);
            return json_encode($ret);
        }
        if ($suggestions == $text || empty($suggestions)) {
            return json_encode($ret);
        }

        // We have suggestions, so mark those up and return them.
        $direction = "r2l";
        [$grammarerrors, $grammarmatches, $insertioncount] = utils::fetch_grammar_correction_diff($text, $suggestions, $direction);
        $ret['grammarerrors'] = $grammarerrors;
        $ret['grammarmatches'] = $grammarmatches;
        $ret['suggestions'] = $suggestions;
        $ret['markedupsuggestions'] = aitranscriptutils::render_passage($suggestions, 'corrections');
        $ret['insertioncount'] = $insertioncount;
        return json_encode($ret);
    }

    /**
     * Parameters for check_grammar.
     *
     * @return external_function_parameters
     */
    public static function check_grammar_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'The assignment course module id'),
            'text' => new external_value(PARAM_TEXT, 'The text to check'),
            'language' => new external_value(PARAM_TEXT, 'The language of the text, such as en-US'),
        ]);
    }

    /**
     * Return value of check_grammar.
     *
     * @return external_value
     */
    public static function check_grammar_returns() {
        return new external_value(PARAM_RAW, 'JSON: error, and the corrections when there is no error');
    }

    /**
     * Mark up the differences between a student's text and the teacher's corrections, for the preview.
     *
     * @param string $passage the student's text
     * @param string $corrections the corrected text
     * @return string JSON: grammarerrors, grammarmatches, suggestions, markedupsuggestions and insertioncount
     */
    public static function render_diffs($passage, $corrections) {
        $params = self::validate_parameters(self::render_diffs_parameters(), [
            'passage' => $passage,
            'corrections' => $corrections,
        ]);

        $direction = "r2l";
        [$grammarerrors, $grammarmatches, $insertioncount] =
            utils::fetch_grammar_correction_diff($params['passage'], $params['corrections'], $direction);
        $ret = [];
        $ret['grammarerrors'] = $grammarerrors;
        $ret['grammarmatches'] = $grammarmatches;
        $ret['suggestions'] = $params['corrections'];
        $ret['markedupsuggestions'] = aitranscriptutils::render_passage($params['corrections'], 'corrections');
        $ret['insertioncount'] = $insertioncount;
        return json_encode($ret);
    }

    /**
     * Parameters for render_diffs.
     *
     * @return external_function_parameters
     */
    public static function render_diffs_parameters() {
        return new external_function_parameters([
            'passage' => new external_value(PARAM_TEXT, 'The student\'s text'),
            'corrections' => new external_value(PARAM_TEXT, 'The corrected text'),
        ]);
    }

    /**
     * Return value of render_diffs.
     *
     * @return external_value
     */
    public static function render_diffs_returns() {
        return new external_value(PARAM_RAW, 'JSON: the marked up differences');
    }
}

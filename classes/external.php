<?php
/**
 * External.
 *
 * @package mod_solo
 * @author  Justin Hunt - Poodll.com
 */


namespace assignfeedback_cloudpoodll;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_module;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use assignfeedback_cloudpoodll\utils;
use assignfeedback_cloudpoodll\aitranscript;

/**
 * External class.
 *
 * @package assignsubmission_cloudpoodll
 * @author  Justin Hunt - Poodll.com
 */
class external extends external_api {

    public static function check_grammar($text,$language) {
        global $DB, $USER;

        $params = self::validate_parameters(self::check_grammar_parameters(), [
            'text' => $text,
            'language' => $language]);
        extract($params);

        $siteconfig = get_config(constants::M_COMPONENT);
        $region = $siteconfig->awsregion;
        $token = utils::fetch_token($siteconfig->apiuser, $siteconfig->apisecret);
        $textanalyser = new textanalyser($token,$text,$region,$language);
        $suggestions = $textanalyser->fetch_grammar_correction();
        if($suggestions==$text || empty($suggestions)){
            return "";
        }

        //if we have suggestions, mark those up and return them
        $direction="r2l";//"l2r";
        list($grammarerrors,$grammarmatches,$insertioncount) = \assignfeedback_cloudpoodll\utils::fetch_grammar_correction_diff($text, $suggestions,$direction);
        $markedupsuggestions = \assignfeedback_cloudpoodll\aitranscriptutils::render_passage($suggestions,'corrections');
        $ret = [];
        $ret['grammarerrors'] = $grammarerrors;
        $ret['grammarmatches'] = $grammarmatches;
        $ret['suggestions'] = $suggestions;
        $ret['markedupsuggestions'] = $markedupsuggestions;
        $ret['insertioncount'] = $insertioncount;

        return json_encode($ret);

    }

    public static function check_grammar_parameters() {
        return new external_function_parameters([
            'text' => new external_value(PARAM_TEXT),
            'language' => new external_value(PARAM_TEXT)
        ]);
    }

    public static function check_grammar_returns() {
        return new external_value(PARAM_RAW);
    }

    public static function render_diffs($passage,$corrections) {
        global $DB, $USER;

        $params = self::validate_parameters(self::render_diffs_parameters(), [
            'passage' => $passage,
            'corrections' => $corrections]);
        extract($params);

        //if we have suggestions, mark those up and return them
        $direction="r2l";//"l2r";
        list($grammarerrors,$grammarmatches,$insertioncount) = \assignfeedback_cloudpoodll\utils::fetch_grammar_correction_diff($passage, $corrections,$direction);
        $markedupsuggestions = \assignfeedback_cloudpoodll\aitranscriptutils::render_passage($corrections,'corrections');
        $ret = [];
        $ret['grammarerrors'] = $grammarerrors;
        $ret['grammarmatches'] = $grammarmatches;
        $ret['suggestions'] = $suggestions;
        $ret['markedupsuggestions'] = $markedupsuggestions;
        $ret['insertioncount'] = $insertioncount;

        return json_encode($ret);

    }

    public static function render_diffs_parameters() {
        return new external_function_parameters([
            'passage' => new external_value(PARAM_TEXT),
            'corrections' => new external_value(PARAM_TEXT)
        ]);
    }

    public static function render_diffs_returns() {
        return new external_value(PARAM_RAW);
    }



}

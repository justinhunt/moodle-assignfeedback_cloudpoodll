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

/**
 * Fetches the AI grammar correction of a student's text, from Cloud Poodll or a Moodle AI provider.
 *
 * @package    assignfeedback_cloudpoodll
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class textanalyser {
    /** @var string $token The Cloud Poodll token. */
    protected $token;

    /** @var string $region The AWS region. */
    protected $region;

    /** @var string $passage The student's text. */
    protected $passage;

    /** @var string $language The language of the text, such as en-US. */
    protected $language;

    /** @var int|null $contextid The context the analysis is done in, for the Moodle AI action register. */
    protected $contextid;

    /** @var aimanager|null $aimanager The AI manager used for the last Moodle AI request. */
    protected $aimanager = null;

    /**
     * The class constructor.
     *
     * @param string $token the Cloud Poodll token, not needed when a Moodle AI provider is used
     * @param string $passage the student's text
     * @param string $region the AWS region
     * @param string $language the language of the text, such as en-US
     * @param int|null $contextid the context the analysis is done in, for the Moodle AI action register
     */
    public function __construct($token, $passage, $region, $language, $contextid = null) {
        $this->token = $token;
        $this->passage = $passage;
        $this->region = $region;
        $this->language = $language;
        $this->contextid = $contextid;
    }

    /**
     * The Cloud Poodll server URL.
     *
     * @return string
     */
    public static function get_cloud_poodll_server() {
        $conf = get_config(constants::M_COMPONENT);
        if (!empty($conf->cloudpoodllserver)) {
            return 'https://' . $conf->cloudpoodllserver;
        }
        return 'https://' . constants::M_DEFAULT_CLOUDPOODLL;
    }

    /**
     * The AI manager for a Moodle AI provider, set up for this analysis.
     *
     * @return aimanager
     */
    protected function get_aimanager(): aimanager {
        $this->aimanager = new aimanager($this->contextid, $this->language);
        return $this->aimanager;
    }

    /**
     * Why the last Moodle AI request failed, or '' if it did not fail or Cloud Poodll was used.
     *
     * @return string
     */
    public function get_error_message(): string {
        return $this->aimanager ? $this->aimanager->get_error_message() : '';
    }

    /**
     * Tidy a grammar correction: trim it and drop punctuation left at the start.
     *
     * @param string $correction
     * @return string
     */
    public static function tidy_correction($correction) {
        $correction = (string) $correction;
        if (\core_text::strlen($correction) > 0) {
            $correction = trim(\core_text::trim_utf8_bom($correction));
            $charone = substr($correction, 0, 1);
            if (preg_match('/^[.,:!?;-]/', $charone)) {
                $correction = ltrim(substr($correction, 1));
            }
        }
        return $correction;
    }

    /**
     * Fetch the grammar correction of a passage.
     *
     * @param string $passage the text to correct, or '' for the text given to the constructor
     * @return string|false the corrected text, or false on failure
     */
    public function fetch_grammar_correction($passage = '') {
        global $USER;

        if (empty($passage)) {
            $passage = $this->passage;
        }

        if (aimanager::uses_moodle_ai()) {
            return $this->get_aimanager()->grammar_correction($passage);
        }

        $params = [];
        $params['wstoken'] = $this->token;
        $params['wsfunction'] = 'local_cpapi_call_ai';
        $params['moodlewsrestformat'] = 'json';
        $params['action'] = 'request_grammar_correction';
        $params['appid'] = 'assignfeedback_cloudpoodll';
        $params['prompt'] = $passage;
        $params['language'] = $this->language;
        $params['subject'] = 'none';
        $params['region'] = $this->region;
        $params['owner'] = hash('md5', $USER->username);

        $serverurl = self::get_cloud_poodll_server() . '/webservice/rest/server.php';
        $response = self::curl_fetch($serverurl, $params);
        if (!self::is_json($response)) {
            return false;
        }
        $payloadobject = json_decode($response);

        // A returnCode above 0 is an error.
        if (isset($payloadobject->returnCode) && $payloadobject->returnCode === 0) {
            return self::tidy_correction($payloadobject->returnMessage);
        }
        return false;
    }

    /**
     * Fetch a URL with Moodle's curl.
     *
     * @param string $url
     * @param array|false $postdata
     * @param string $method get or post
     * @return string
     */
    public static function curl_fetch($url, $postdata = false, $method = 'get') {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        if ($method == 'post') {
            return $curl->post($url, $postdata);
        }
        return $curl->get($url, $postdata);
    }

    /**
     * Whether a string is valid JSON.
     *
     * @param string $string
     * @return bool
     */
    public static function is_json($string) {
        if (empty($string)) {
            return false;
        }
        json_decode($string);
        return json_last_error() == JSON_ERROR_NONE;
    }
}

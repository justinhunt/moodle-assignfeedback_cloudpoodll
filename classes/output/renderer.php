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

/**
 * This file defines the renderer for this plugin
 *
 * @package   assignfeedback_cloudpoodll
 * @copyright 2019 Justin Hunt {@link https://poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_cloudpoodll\output;

use assignfeedback_cloudpoodll\constants;
use assignfeedback_cloudpoodll\utils;

defined('MOODLE_INTERNAL') || die();

class renderer extends \plugin_renderer_base {

    public function fetch_delete_feedback() {

        $ds = \html_writer::tag('button',
                get_string('deletefeedback', constants::M_COMPONENT),
                array('type' => 'button', 'id' => constants::M_COMPONENT . '_deletefeedbackbutton',
                        'class' => constants::M_COMPONENT . '_deletefeedbackbutton btn btn-secondary'));

        return $ds;
    }

    public function prepare_current_feedback($feedbackplayer, $deletefeedback) {
        $toggletext = \html_writer::tag('span', get_string('clicktoshow', constants::M_COMPONENT), array('class' => 'toggletext'));
        $togglebutton =
                \html_writer::tag('span', '', array('class' => 'fa fa-2x fa-toggle-off togglebutton', 'aria-hidden' => 'true'));
        $toggle = \html_writer::div($togglebutton . $toggletext, constants::M_COMPONENT . '_togglecontainer');
        $cs = \html_writer::div($feedbackplayer . $deletefeedback, constants::M_COMPONENT . '_currentfeedback',
                array('style' => 'display: none;'));
        return $toggle . $cs;
    }

    /**
     * The html part of the recorder
     */
    public function fetch_recorder($r_options, $token) {
        global $CFG,$USER;

        switch ($r_options->recordertype) {
            case constants::REC_AUDIO:
                // fresh
                if ($r_options->recorderskin == constants::SKIN_FRESH) {
                    $width = "400";
                    $height = "300";

                } else if ($r_options->recorderskin == constants::SKIN_PLAIN) {
                    $width = "360";
                    $height = "190";

                } else if ($r_options->recorderskin == constants::SKIN_UPLOAD) {
                    $width = "360";
                    $height = "150";

                    // bmr 123 once standard
                } else {
                    $width = "360";
                    $height = "240";
                }
                break;
            case constants::REC_VIDEO:
            default:
                // bmr 123 once
                if ($r_options->recorderskin == constants::SKIN_BMR) {
                    $width = "360";
                    $height = "450";
                } else if ($r_options->recorderskin == constants::SKIN_123 || $r_options->recorderskin == constants::SKIN_SCREEN) {
                    $width = "450";// "360";
                    $height = "550";// "410";
                } else if ($r_options->recorderskin == constants::SKIN_ONCE) {
                    $width = "350";
                    $height = "290";
                } else if ($r_options->recorderskin == constants::SKIN_UPLOAD) {
                    $width = "350";
                    $height = "310";
                    // standard
                } else {
                    $width = "360";
                    $height = "410";
                }
        }

        // transcribe.
        $can_transcribe = utils::can_transcribe($r_options);
        $transcribe = "0";
        if ($can_transcribe && $r_options->transcribe) {
            if ($r_options->recordertype == constants::REC_AUDIO) {
                $transcribe = $r_options->transcribe;
            } else {
                $transcribe = constants::TRANSCRIBER_AMAZONTRANSCRIBE;
            }
        }

        // any recorder hints ... go here.
        // Set encoder to stereoaudio if TRANSCRIBER_GOOGLECLOUDSPEECH.
        $hints = new \stdClass();
        if ($transcribe == constants::TRANSCRIBER_GOOGLECLOUDSPEECH) {
            $hints->encoder = 'stereoaudio';
        } else {
            $hints->encoder = 'auto';
        }
        $string_hints = base64_encode(json_encode($hints));

        // Set subtitles.
        switch ($transcribe) {
            case constants::TRANSCRIBER_AMAZONTRANSCRIBE:
            case constants::TRANSCRIBER_GOOGLECLOUDSPEECH:
                $subtitle = "1";
                break;
            default:
                $subtitle = "0";
                break;
        }

        // transcode.
        $transcode = $r_options->transcode ? "1" : "0";

        $recorderdiv = \html_writer::div('', constants::M_COMPONENT . '_notcenter',
                array('id' => constants::ID_REC,
                        'data-id' => 'therecorder',
                        'data-parent' => $CFG->wwwroot,
                        'data-localloader' => '/mod/assign/feedback/cloudpoodll/poodllloader.html',
                        'data-owner' => hash('md5',$USER->username),
                        'data-media' => $r_options->recordertype,
                        'data-appid' => constants::APPID,
                        'data-type' => $r_options->recorderskin,
                        'data-width' => $width,
                        'data-height' => $height,
                        'data-updatecontrol' => constants::ID_UPDATE_CONTROL,
                        'data-timelimit' => $r_options->timelimit,
                        'data-transcode' => $transcode,
                        'data-transcribe' => $transcribe,
                        'data-subtitle' => $subtitle,
                        'data-language' => $r_options->language,
                        'data-expiredays' => $r_options->expiredays,
                        'data-region' => $r_options->awsregion,
                        'data-fallback' => $r_options->fallback,
                        'data-hints' => $string_hints,
                        'data-token' => $token
                )
        );

        $containerdiv = \html_writer::div($recorderdiv, constants::CLASS_REC_CONTAINER . " ",
                array('id' => constants::CLASS_REC_CONTAINER));

        // this is the final html.
        $recorderhtml = \html_writer::div($containerdiv, constants::CLASS_REC_OUTER);

        // return html.
        return $recorderhtml;
    }

    /**
     * Return HTML to display message about problem
     */
    public function show_problembox($msg) {
        $output = '';
        $output .= $this->output->box_start(constants::M_COMPONENT . '_problembox');
        $output .= $this->notification($msg, 'warning');
        $output .= $this->output->box_end();
        return $output;
    }

}
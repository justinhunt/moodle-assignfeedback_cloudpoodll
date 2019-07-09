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
 * This file contains the definition for the library class for comment feedback plugin
 *
 * @package   assignfeedback_cloudpoodll
 * @copyright 2019 Justin Hunt {@link https://poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assignfeedback_cloudpoodll\constants;
use assignfeedback_cloudpoodll\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Library class for comment feedback plugin extending feedback plugin base class.
 *
 * @package   assignfeedback_cloudpoodll
 * @copyright 2019 Justin Hunt {@link https://poodll.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_cloudpoodll extends assign_feedback_plugin {

    /**
     * Get the name of the online comment feedback plugin.
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', constants::M_COMPONENT);
    }

    /**
     * Get the feedback comment from the database.
     *
     * @param int $gradeid
     * @return stdClass|false The feedback cloudpoodll for the given grade if it exists.
     *                        False if it doesn't.
     */
    public function get_feedback_cloudpoodll($gradeid) {
        global $DB;
        return $DB->get_record(constants::M_TABLE, array('grade'=>$gradeid));
    }



    /**
     * Has the comment feedback been modified?
     *
     * @param stdClass $grade The grade object.
     * @param stdClass $data Data from the form submission.
     * @return boolean True if the comment feedback has been modified, else false.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        $url = '';
        if ($grade) {
            $feedbackcloudpoodll = $this->get_feedback_cloudpoodll($grade->id);
            if ($feedbackcloudpoodll) {
                $url = $feedbackcloudpoodll->{constants::NAME_UPDATE_CONTROL};
            }
        }

        if ($url == $data->{constants::NAME_UPDATE_CONTROL}) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Override to indicate a plugin supports quickgrading.
     *
     * @return boolean - True if the plugin supports quickgrading
     */
    public function supports_quickgrading() {
        return false;
    }

    /**
     * Return a list of the text fields that can be imported/exported by this plugin.
     *
     * @return array An array of field names and descriptions. (name=>description, ...)
     */
    public function get_editor_fields() {
        return array('cloudpoodll' => get_string('pluginname', constants::M_COMPONENT));
    }



    /**
     * Save quickgrading changes.
     *
     * @param int $userid The user id in the table this quickgrading element relates to
     * @param stdClass $grade The grade
     * @return boolean - true if the grade changes were saved correctly
     */
    /*
    public function save_quickgrading_changes($userid, $grade) {
        global $DB;
        $feedbackcomment = $this->get_feedback_cloudpoodll($grade->id);
        $quickgradecloudpoodll = optional_param('quickgrade_cloudpoodll_' . $userid, null, PARAM_RAW);
        if (!$quickgradecloudpoodll && $quickgradecloudpoodll !== '') {
            return true;
        }
        if ($feedbackcomment) {
            $feedbackcomment->commenttext = $quickgradecloudpoodll;
            return $DB->update_record(constants::M_TABLE, $feedbackcomment);
        } else {
            $feedbackcomment = new stdClass();
            $feedbackcomment->commenttext = $quickgradecloudpoodll;
            $feedbackcomment->commentformat = FORMAT_HTML;
            $feedbackcomment->grade = $grade->id;
            $feedbackcomment->assignment = $this->assignment->get_instance()->id;
            return $DB->insert_record(constants::M_TABLE, $feedbackcomment) > 0;
        }
    }
    */

    /**
     * Save the settings for feedback cloudpoodll plugin
     *
     * @param stdClass $data
     * @return bool
     */
    public function save_settings(stdClass $data) {
        //recorder type
        $this->set_config('recordertype', $data->{constants::M_COMPONENT . '_recordertype'});
        //recorder skin
        $this->set_config('recorderskin', $data->{constants::M_COMPONENT . '_recorderskin'});

        //if we have a time limit, set it
        if(isset($data->{constants::M_COMPONENT . '_timelimit'})){
            $this->set_config('timelimit', $data->{constants::M_COMPONENT . '_timelimit'});
        }else{
            $this->set_config('timelimit', 0);
        }
        //expiredays
        $this->set_config('expiredays', $data->{constants::M_COMPONENT . '_expiredays'});

        //language
        $this->set_config('language', $data->{constants::M_COMPONENT . '_language'});
        //trancribe
        $this->set_config('enabletranscription', $data->{constants::M_COMPONENT . '_enabletranscription'});
        //transcode
        $this->set_config('enabletranscode', $data->{constants::M_COMPONENT . '_enabletranscode'});
        //playertype
        $this->set_config('playertype', $data->{constants::M_COMPONENT . '_playertype'});

        //playertype student
        $this->set_config('playertypestudent', $data->{constants::M_COMPONENT . '_playertypestudent'});

        return true;
    }

    /**
     * Get the default setting for feedback cloudpoodll plugin
     *
     * @param MoodleQuickForm $mform The form to add elements to
     * @return void
     */
    public function get_settings(MoodleQuickForm $mform) {

            global $CFG, $COURSE;

            $adminconfig = get_config(constants::M_COMPONENT);
            $recordertype = $this->get_config('recordertype') ? $this->get_config('recordertype') :  $adminconfig->defaultrecorder;
            $recorderskin = $this->get_config('recorderskin') ? $this->get_config('recorderskin') : constants::SKIN_BMR;
            $timelimit = $this->get_config('timelimit') ? $this->get_config('timelimit') :  0;
            $expiredays = $this->get_config('expiredays') ? $this->get_config('expiredays') : $adminconfig->expiredays;
            $language = $this->get_config('language') ? $this->get_config('language') : $adminconfig->language;
            $playertype = $this->get_config('playertype') ? $this->get_config('playertype') : $adminconfig->defaultplayertype;
            $playertypestudent = $this->get_config('playertypestudent') ? $this->get_config('playertypestudent') : $adminconfig->defaultplayertypestudent;
            $enabletranscription = $this->get_config('enabletranscription') ? $this->get_config('enabletranscription') : $adminconfig->enabletranscription;
            $enabletranscode = $this->get_config('enabletranscode') ? $this->get_config('enabletranscode') : $adminconfig->enabletranscode;

            $rec_options = utils::fetch_options_recorders();
            $mform->addElement('select', constants::M_COMPONENT . '_recordertype', get_string("recordertype", constants::M_COMPONENT), $rec_options);
            $mform->setDefault(constants::M_COMPONENT . '_recordertype',$recordertype);
            $mform->disabledIf(constants::M_COMPONENT . '_recordertype', constants::M_COMPONENT . '_enabled', 'notchecked');


            $skin_options = utils::fetch_options_skins();
            $mform->addElement('select', constants::M_COMPONENT . '_recorderskin', get_string("recorderskin", constants::M_COMPONENT), $skin_options);
            $mform->setDefault(constants::M_COMPONENT . '_recorderskin', $recorderskin);
            $mform->disabledIf(constants::M_COMPONENT . '_recorderskin', constants::M_COMPONENT . '_enabled', 'notchecked');


            //Add a place to set a maximum recording time.
            $mform->addElement('duration', constants::M_COMPONENT . '_timelimit', get_string('timelimit', constants::M_COMPONENT));
            $mform->setDefault(constants::M_COMPONENT . '_timelimit', $timelimit);
            $mform->disabledIf(constants::M_COMPONENT . '_timelimit', constants::M_COMPONENT . '_enabled', 'notchecked');

            //Add expire days
            $expire_options = utils::get_expiredays_options();
            $mform->addElement('select', constants::M_COMPONENT . '_expiredays', get_string("expiredays", constants::M_COMPONENT), $expire_options);
            $mform->setDefault(constants::M_COMPONENT . '_expiredays', $expiredays);
            $mform->disabledIf(constants::M_COMPONENT . '_expiredays', constants::M_COMPONENT . '_enabled', 'notchecked');

            //transcode settings
            $mform->addElement('advcheckbox', constants::M_COMPONENT . '_enabletranscode', get_string("enabletranscode", constants::M_COMPONENT));
            $mform->setDefault(constants::M_COMPONENT . '_enabletranscode', $enabletranscode);
            $mform->disabledIf(constants::M_COMPONENT . '_enabletranscode', constants::M_COMPONENT . '_enabled', 'notchecked');

            //transcription settings
            //here add googlecloudspeech or amazontranscrobe options
            $transcriber_options = utils::get_transcriber_options();
            $mform->addElement('select', constants::M_COMPONENT . '_enabletranscription', get_string("enabletranscription", constants::M_COMPONENT), $transcriber_options);
            $mform->setDefault(constants::M_COMPONENT . '_enabletranscription', $enabletranscription);
            $mform->disabledIf(constants::M_COMPONENT . '_enabletranscription', constants::M_COMPONENT . '_enabled', 'notchecked');

            //lang options
            $lang_options = utils::get_lang_options();
            $mform->addElement('select', constants::M_COMPONENT . '_language', get_string("language", constants::M_COMPONENT), $lang_options);
            $mform->setDefault(constants::M_COMPONENT . '_language', $language);
            $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabletranscription', 'eq',0);
            $mform->disabledIf(constants::M_COMPONENT . '_language', constants::M_COMPONENT . '_enabletranscode', 'notchecked');


            //playertype : teacher
            $playertype_options = utils::fetch_options_interactivetranscript();
            $mform->addElement('select', constants::M_COMPONENT . '_playertype', get_string("playertype", constants::M_COMPONENT), $playertype_options);
            $mform->setDefault(constants::M_COMPONENT . '_playertype', $playertype);
            $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabletranscription', 'eq',0);
            $mform->disabledIf(constants::M_COMPONENT . '_playertype', constants::M_COMPONENT . '_enabletranscode', 'notchecked');


            //playertype: student
            $playertype_options = utils::fetch_options_interactivetranscript();
            $mform->addElement('select', constants::M_COMPONENT . '_playertypestudent', get_string("playertypestudent", constants::M_COMPONENT), $playertype_options);
            $mform->setDefault(constants::M_COMPONENT . '_playertypestudent', $playertypestudent);
            $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabled', 'notchecked');
            $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabletranscription', 'eq',0);
            $mform->disabledIf(constants::M_COMPONENT . '_playertypestudent', constants::M_COMPONENT . '_enabletranscode', 'notchecked');


        }


    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements_for_user($grade, MoodleQuickForm $mform, stdClass $data, $userid) {

        $submission = $this->assignment->get_user_submission($userid, false);
        $feedbackcloudpoodll = false;

        if ($grade) {
            $feedbackcloudpoodll = $this->get_feedback_cloudpoodll($grade->id);
        }

        $this->fetch_cloudpoodll_feedback_form($mform,$feedbackcloudpoodll);

        return true;
    }

    public function fetch_cloudpoodll_feedback_form(MoodleQuickForm $mform, $feedbackcloudpoodll=false){
        global $CFG, $USER, $PAGE;

        //prepare the AMD javascript for deletesubmission and showing the recorder
        $opts = array(
                "component"=> constants::M_COMPONENT
        );
        $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/feedbackhelper", 'init', array($opts));
        $PAGE->requires->strings_for_js(array('reallydeletefeedback','clicktohide','clicktoshow'),constants::M_COMPONENT);

        //Get our renderers
        $renderer = $PAGE->get_renderer(constants::M_COMPONENT);


        if ($feedbackcloudpoodll && !empty($feedbackcloudpoodll->filename)) {

            $deletefeedback = $renderer->fetch_delete_feedback();

            //show current submission
            //show the previous response in a player or whatever and a delete button
            $feedbackplayer =  $this->fetch_feedback_player($feedbackcloudpoodll);
            $currentfeedback = $renderer->prepare_current_feedback($feedbackplayer,$deletefeedback);

            $mform->addElement('static', 'currentfeedback',
                    get_string('currentfeedback', constants::M_COMPONENT) ,
                    $currentfeedback);

        }

        //output our hidden field which has the filename
        $mform->addElement('hidden', constants::NAME_UPDATE_CONTROL, '',array('id' => constants::ID_UPDATE_CONTROL));
        $mform->setType(constants::NAME_UPDATE_CONTROL,PARAM_TEXT);

        //recorder data
        $r_options = new stdClass();
        $r_options->recordertype=$this->get_config('recordertype');
        $r_options->recorderskin=$this->get_config('recorderskin');
        $r_options->timelimit=$this->get_config('timelimit');
        $r_options->expiredays=$this->get_config('expiredays');
        $r_options->transcode=$this->get_config('enabletranscode');
        $r_options->transcribe=$this->get_config('enabletranscription');
        $r_options->language=$this->get_config('language');
        $r_options->awsregion= get_config(constants::M_COMPONENT, 'awsregion');
        $r_options->fallback= get_config(constants::M_COMPONENT, 'fallback');

        //fetch API token
        $api_user = get_config(constants::M_COMPONENT,'apiuser');
        $api_secret = get_config(constants::M_COMPONENT,'apisecret');
        $token = utils::fetch_token($api_user,$api_secret);

        //fetch recorder html
        $recorderhtml = $renderer->fetch_recorder($r_options,$token);
        $mform->addElement('static', 'description', '',$recorderhtml);

        return true;
    }

    /**
     * Saving the cloud poodll content into database.
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;


        //if filename is false, or empty, no update. possibly used changed something else on page
        //possibly they did not record ... that will be caught elsewhere
        $filename = $data->filename;
        if($filename === false || empty($filename)){return true;}

        //get expiretime of this record
        $expiredays = $this->get_config("expiredays");
        if($expiredays < 9999) {
            $fileexpiry = time() + DAYSECS * $expiredays;
        }else{
            $fileexpiry = 0;
        }

        $thefeedback = $this->get_feedback_cloudpoodll($grade->id);
        $savedok=false;
        if ($thefeedback) {
            if($filename=='-1'){
                //this is a flag to delete the feedback
                $thefeedback->filename = '';
                $thefeedback->fileexpiry = 0;
                $thefeedback->vttdata = '';
                $thefeedback->transcript = '';
                $savedok = $DB->update_record(constants::M_TABLE,$thefeedback);
                return $savedok;
            }else {
                $thefeedback->{constants::NAME_UPDATE_CONTROL} = $data->{constants::NAME_UPDATE_CONTROL};
                $thefeedback->fileexpiry = $fileexpiry;
                $savedok = $DB->update_record(constants::M_TABLE, $thefeedback);
            }
        } else {
            $thefeedback = new stdClass();
            $thefeedback->{constants::NAME_UPDATE_CONTROL} = $data->{constants::NAME_UPDATE_CONTROL};
            $thefeedback->fileexpiry = $fileexpiry;
            $thefeedback->grade = $grade->id;
            $thefeedback->assignment = $this->assignment->get_instance()->id;
            $feedbackid = $DB->insert_record(constants::M_TABLE, $thefeedback);
            if($feedbackid > 0){
                $thefeedback->id = $feedbackid;
                $savedok=true;
            }
        }
        if($savedok){$this->register_fetch_transcript_task($thefeedback);}
        return $savedok;
    }

    //register an adhoc task to pick up transcripts
    public function register_fetch_transcript_task($cloudpoodllfeedback){
        $fetch_task = new \assignfeedback_cloudpoodll\task\cloudpoodll_s3_adhoc();
        $fetch_task->set_component(constants::M_COMPONENT);

        $customdata = new \stdClass();
        $customdata->feedback = $cloudpoodllfeedback;
        $customdata->taskcreationtime = time();

        $fetch_task->set_custom_data($customdata);
        // queue it
        \core\task\manager::queue_adhoc_task($fetch_task);
        return true;
    }


    /**
     * Display the comment in the feedback table.
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        $feedbackcloudpoodll = $this->get_feedback_cloudpoodll($grade->id);
        if ($feedbackcloudpoodll) {
            return  $this->fetch_feedback_player($feedbackcloudpoodll);
        }
        return '';
    }

    /**
     * Display the recording in the feedback table.
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {

        $feedbackcloudpoodll = $this->get_feedback_cloudpoodll($grade->id);
        $playerstring = $this->fetch_feedback_player($feedbackcloudpoodll);

        return $playerstring;
    }

    public function fetch_feedback_player($feedbackcloudpoodll){
        global $PAGE;

        $playerstring = "";
        if($feedbackcloudpoodll){
            //The path to any media file we should play
            $filename= $feedbackcloudpoodll->filename;
            $rawmediapath =$feedbackcloudpoodll->filename;
            $mediapath = urlencode($rawmediapath);
            if(empty($feedbackcloudpoodll->vttdata)){
                $vttdata = false;
            }else{
                $vttdata = $feedbackcloudpoodll->vttdata;
            }

            //are we a person who can grade?
            $isgrader=false;
            if(has_capability('mod/assign:grade',$this->assignment->get_context())){
                $isgrader=true;
            }
            //is this a list page
            $islist = optional_param('action','',PARAM_TEXT)=='grading';
        } else {
            return '';
        }

        //size params for our response players/images
        //audio is a simple 1 or 0 for display or not
        $size = $this->fetch_player_size($this->get_config('recordertype'));

        //player type
        $playertype = constants::PLAYERTYPE_DEFAULT;
        if($vttdata && !$islist) {
            switch($isgrader) {
                case true:
                    $playertype = $this->get_config('playertype');
                    break;
                case false:
                    $playertype = $this->get_config('playertypestudent');
                    break;
            }
        }


        //if this is a playback area, for teacher, show a string if no file
        if ( (empty($filename) || strlen($filename)<3)){
            $playerstring .= "";

        }else{

            //prepare our response string, which will parsed and replaced with the necessary player
            switch($this->get_config('recordertype')){

                case constants::REC_AUDIO:
                    //get player
                    $playerid= html_writer::random_id(constants::M_COMPONENT . '_');
                    $containerid= html_writer::random_id(constants::M_COMPONENT . '_');
                    $container = html_writer::div('',constants::M_COMPONENT . '_transcriptcontainer',array('id'=>$containerid));

                    //player template
                    $randomid = html_writer::random_id('cloudpoodll_');
                    $audioplayer = "<audio id='@PLAYERID@' crossorigin='anonymous' controls='true'>";
                    $audioplayer .= "<source src='@MEDIAURL@'>";
                    $audioplayer .= "<track src='@VTTURL@' kind='captions' srclang='@LANG@' label='@LANG@' default='true'>";
                    $audioplayer .= "</audio>";
                    //template -> player
                    $audioplayer =str_replace('@PLAYERID@',$playerid,$audioplayer);
                    $audioplayer =str_replace('@MEDIAURL@',$rawmediapath . '?cachekiller=' . $randomid,$audioplayer);
                    $audioplayer =str_replace('@LANG@',$this->get_config('language'),$audioplayer);
                    $audioplayer =str_replace('@VTTURL@',$rawmediapath . '.vtt',$audioplayer);


                    if($size) {
                        switch($playertype) {
                            case constants::PLAYERTYPE_DEFAULT:
                                //$playerstring .= format_text("<a href='$rawmediapath'>$filename</a>", FORMAT_HTML);
                                //just use the raw audio tags response string
                                $playerstring .= $audioplayer;
                                break;
                            case constants::PLAYERTYPE_INTERACTIVETRANSCRIPT:

                                $playerstring .= $audioplayer . $container;

                                //prepare AMD javascript for displaying submission
                                $transcriptopts=array( 'component'=>constants::M_COMPONENT,'playerid'=>$playerid,'containerid'=>$containerid,
                                        'cssprefix'=>constants::M_COMPONENT .'_transcript');
                                $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/interactivetranscript", 'init', array($transcriptopts));
                                $PAGE->requires->strings_for_js(array('transcripttitle'),constants::M_COMPONENT);
                                break;

                            case constants::PLAYERTYPE_STANDARDTRANSCRIPT:

                                $playerstring .= $audioplayer . $container;
                                //prepare AMD javascript for displaying submission
                                $transcriptopts=array( 'component'=>constants::M_COMPONENT,'playerid'=>$playerid,'containerid'=>$containerid,
                                        'cssprefix'=>constants::M_COMPONENT .'_transcript', 'transcripturl'=>$rawmediapath . '.txt');
                                $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/standardtranscript", 'init', array($transcriptopts));
                                $PAGE->requires->strings_for_js(array('transcripttitle'),constants::M_COMPONENT);
                                break;
                        }
                    }else{
                        $playerstring=get_string('audioplaceholder',constants::M_COMPONENT);
                    }
                    break;

                case constants::REC_VIDEO:
                    if($size) {


                        $playerid= html_writer::random_id(constants::M_COMPONENT . '_');
                        $containerid= html_writer::random_id(constants::M_COMPONENT . '_');
                        $container = html_writer::div('',constants::M_COMPONENT . '_transcriptcontainer',array('id'=>$containerid));

                        //player template
                        $randomid = html_writer::random_id('cloudpoodll_');

                        switch ($playertype) {
                            case constants::PLAYERTYPE_INTERACTIVETRANSCRIPT:
                                if ($size->width == 0) {
                                    $playerstring = get_string('videoplaceholder', constants::M_COMPONENT);
                                    break;
                                }

                                $videoplayer = "<video id='@PLAYERID@' class='nomediaplugin' crossorigin='anonymous' controls='true' width='$size->width' height='$size->height'>";
                                $videoplayer .= "<source src='@MEDIAURL@'>";
                                $videoplayer .= "<track src='@VTTURL@' kind='captions' srclang='@LANG@' label='@LANG@' default='true'>";
                                $videoplayer .= "</video>";
                                //template -> player
                                $videoplayer =str_replace('@PLAYERID@',$playerid,$videoplayer);
                                $videoplayer =str_replace('@MEDIAURL@',$rawmediapath . '?cachekiller=' . $randomid,$videoplayer);
                                $videoplayer =str_replace('@LANG@',$this->get_config('language'),$videoplayer);
                                $videoplayer =str_replace('@VTTURL@',$rawmediapath . '.vtt',$videoplayer);
                                $playerstring .= $videoplayer . $container;

                                //prepare AMD javascript for displaying submission
                                $transcriptopts=array( 'component'=>constants::M_COMPONENT,'playerid'=>$playerid,'containerid'=>$containerid, 'cssprefix'=>constants::M_COMPONENT .'_transcript');
                                $PAGE->requires->js_call_amd(constants::M_COMPONENT . "/interactivetranscript", 'init', array($transcriptopts));
                                $PAGE->requires->strings_for_js(array('transcripttitle'),constants::M_COMPONENT);
                                break;

                            case constants::PLAYERTYPE_DEFAULT:
                            default:
                                if ($size->width == 0) {
                                    $playerstring = get_string('videoplaceholder', constants::M_COMPONENT);
                                    break;
                                }

                                //$playerstring .= format_text("<a href='$rawmediapath?d=$size->width" . 'x' . "$size->height'>$filename</a>", FORMAT_HTML);
                                $videoplayer = "<video id='@PLAYERID@' crossorigin='anonymous' controls='true' width='$size->width' height='$size->height'>";
                                $videoplayer .= "<source src='@MEDIAURL@'>";
                                if($vttdata) {
                                    $videoplayer .= "<track src='@VTTURL@' kind='captions' srclang='@LANG@' label='@LANG@' default='true'>";
                                }
                                $videoplayer .= "</video>";
                                //template -> player
                                $videoplayer =str_replace('@PLAYERID@',$playerid,$videoplayer);
                                $videoplayer =str_replace('@MEDIAURL@',$rawmediapath . '?cachekiller=' . $randomid,$videoplayer);
                                $videoplayer =str_replace('@LANG@',$this->get_config('language'),$videoplayer);
                                $videoplayer =str_replace('@VTTURL@',$rawmediapath . '.vtt',$videoplayer);
                                $playerstring .= $videoplayer;
                        }
                    }else{
                        $playerstring=get_string('videoplaceholder',constants::M_COMPONENT);
                    }
                    break;

                default:
                    $playerstring .= format_text("<a href='$rawmediapath'>$filename</a>", FORMAT_HTML);
                    break;

            }//end of switch
        }//end of if (checkfordata ...)
        return $playerstring;

    }

    public function	fetch_player_size($recordertype){

        //is this a list view
        $islist = optional_param('action','',PARAM_TEXT)=='grading';

        //build our sizes array
        $sizes=array();
        $sizes['0']=new stdClass();$sizes['0']->width=0;$sizes['0']->height=0;
        $sizes['160']=new stdClass();$sizes['160']->width=160;$sizes['160']->height=120;
        $sizes['320']=new stdClass();$sizes['320']->width=320;$sizes['320']->height=240;
        $sizes['480']=new stdClass();$sizes['480']->width=480;$sizes['480']->height=360;
        $sizes['640']=new stdClass();$sizes['640']->width=640;$sizes['640']->height=480;
        $sizes['800']=new stdClass();$sizes['800']->width=800;$sizes['800']->height=600;
        $sizes['1024']=new stdClass();$sizes['1024']->width=1024;$sizes['1024']->height=768;

        $size=$sizes[0];
        $config=get_config(constants::M_COMPONENT);

        //prepare our response string, which will parsed and replaced with the necessary player
        switch($recordertype){
            case constants::REC_VIDEO:
                $size=$islist ? $sizes[$config->displaysize_list] : $sizes[$config->displaysize_single] ;
                break;
            case constants::REC_AUDIO:
                $size=$islist ? $config->displayaudioplayer_list : $config->displayaudioplayer_single ;
                break;
            default:
                break;

        }//end of switch
        return $size;

    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {

        return false;
    }

    /**
     * Upgrade the settings from the old assignment to the new plugin based one
     *
     * @param context $oldcontext - the context for the old assignment
     * @param stdClass $oldassignment - the data for the old assignment
     * @param string $log - can be appended to by the upgrade
     * @return bool was it a success? (false will trigger a rollback)
     */
    public function upgrade_settings(context $oldcontext, stdClass $oldassignment, & $log) {

        return true;
    }

    /**
     * Upgrade the feedback from the old assignment to the new one
     *
     * @param context $oldcontext - the database for the old assignment context
     * @param stdClass $oldassignment The data record for the old assignment
     * @param stdClass $oldsubmission The data record for the old submission
     * @param stdClass $grade The data record for the new grade
     * @param string $log Record upgrade messages in the log
     * @return bool true or false - false will trigger a rollback
     */
    public function upgrade(context $oldcontext,
                            stdClass $oldassignment,
                            stdClass $oldsubmission,
                            stdClass $grade,
                            & $log) {

        return true;
    }

    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records(constants::M_TABLE,
                            array('assignment'=>$this->assignment->get_instance()->id));
        return true;
    }

    /**
     * Returns true if there are no feedback cloudpoodll for the given grade.
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }

    /*

    /**
     * Return a description of external params suitable for uploading an feedback comment from a webservice.
     *
     * @return external_description|null
     */
    /*
    public function get_external_parameters() {
        $editorparams = array('text' => new external_value(PARAM_RAW, 'The text for this feedback.'),
                              'format' => new external_value(PARAM_INT, 'The format for this feedback'));
        $editorstructure = new external_single_structure($editorparams, 'Editor structure', VALUE_OPTIONAL);
        return array('assignfeedbackcloudpoodll_editor' => $editorstructure);
    }
    */

    /**
     * Return the plugin configs for external functions.
     *
     * @return array the list of settings
     * @since Moodle 3.2
     */

    /*
    public function get_config_for_external() {
        return (array) $this->get_config();
    }
    */
}

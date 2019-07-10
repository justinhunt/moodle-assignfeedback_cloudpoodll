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
 * A assignfeedback_cloudpoodll adhoc task
 *
 * @package    assignfeedback_cloudpoodll
 * @copyright  2019 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_cloudpoodll\task;

defined('MOODLE_INTERNAL') || die();

use \assignfeedback_cloudpoodll\constants;
use \assignfeedback_cloudpoodll\utils;

/**
 * Assignfeedback_cloudpoodll adhoc task to fetch back transcriptions from Amazon S3
 *
 * @package    assignfeedback_cloudpoodll
 * @since      Moodle 3.1
 * @copyright  2015 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cloudpoodll_s3_adhoc extends \core\task\adhoc_task {

    /**
     *  Run the tasks
     */
    public function execute() {
        global $DB;
        $trace = new \text_progress_trace();

        // CD should contain activityid / attemptid and modulecontextid.
        $cd = $this->get_custom_data();
        $feedback = $cd->feedback;
        // $trace->output($cd->somedata).

        $transcript = utils::fetch_transcriptdata($feedback->filename . '.txt');
        $fulltranscript = false;
        $vttdata = false;
        if ($transcript) {
            $fulltranscript = utils::fetch_transcriptdata($feedback->filename . '.json');
            $vttdata = utils::fetch_transcriptdata($feedback->filename . '.vtt');
        }
        if ($transcript === false) {
            if ($cd->taskcreationtime + (HOURSECS * 24) < time()) {
                $this->do_forever_fail('No transcript could be found', $trace);
                return;
            } else {
                $this->do_retry_soon('Transcript appears to not be ready yet', $trace, $cd);
                return;
            }
        } else {
            // yay we have transcript data, let us save that shall we .
            $feedback->transcript = $transcript;
            if ($fulltranscript) {
                $feedback->fulltranscript = $fulltranscript;
            }
            if ($vttdata) {
                $feedback->vttdata = $vttdata;
            }
            $DB->update_record(constants::M_TABLE, $feedback);
            return;
        }
    }

    protected function do_retry_soon($reason, $trace, $customdata) {
        if ($customdata->taskcreationtime + (MINSECS * 15) < time()) {
            $this->do_retry_delayed($reason, $trace);
        } else {
            $trace->output($reason . ": will try again next cron ");
            $fetch_task = new \assignfeedback_cloudpoodll\task\cloudpoodll_s3_adhoc();
            $fetch_task->set_component(constants::M_COMPONENT);
            $fetch_task->set_custom_data($customdata);
            // queue it.
            \core\task\manager::queue_adhoc_task($fetch_task);
        }
    }

    protected function do_retry_delayed($reason, $trace) {
        $trace->output($reason . ": will retry after a delay ");
        throw new \file_exception('retrievefileproblem', 'could not fetch transcript.');
    }

    protected function do_forever_fail($reason, $trace) {
        $trace->output($reason . ": will not retry ");
    }

}


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
 * Tests for the web services.
 *
 * @package    assignfeedback_cloudpoodll
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(external::class)]
final class external_test extends \advanced_testcase {
    public function test_check_grammar_needs_the_capability(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $assign = $gen->create_module('assign', ['course' => $course->id]);
        $this->setUser($gen->create_and_enrol($course, 'student'));

        $this->expectException(\required_capability_exception::class);
        external::check_grammar($assign->cmid, 'I go home.', 'en-US');
    }

    public function test_check_grammar_needs_an_assignment(): void {
        $this->resetAfterTest();
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $page = $gen->create_module('page', ['course' => $course->id]);
        $this->setUser($gen->create_and_enrol($course, 'editingteacher'));

        $this->expectException(\dml_missing_record_exception::class);
        external::check_grammar($page->cmid, 'I go home.', 'en-US');
    }
}

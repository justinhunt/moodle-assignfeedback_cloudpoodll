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

use core_ai\aiactions\generate_text;
use GuzzleHttp\Psr7\Response;

/**
 * Tests for sending student work to a Moodle AI provider instead of Cloud Poodll.
 *
 * Each core provider is given a mocked HTTP reply, so these tests check what the plugin sends and how it reads
 * the reply, without calling any AI service.
 *
 * @package    assignfeedback_cloudpoodll
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(aimanager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\assignfeedback_cloudpoodll\local\aiprompts::class)]
final class aimanager_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        if (!class_exists(\core_ai\manager::class) || !class_exists(\core_ai\provider::class)) {
            $this->markTestSkipped('Core AI subsystem not available.');
        }
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Turn on a core provider and send student work to it.
     *
     * Only providers that ship with this Moodle version are tested: a provider installed as an add-on
     * (aiprovider_gemini before Moodle 5.2, for example) may be set up and reply differently.
     *
     * @param string $plugin openai, gemini or ollama
     * @return \core_ai\provider
     */
    private function use_provider(string $plugin): \core_ai\provider {
        global $CFG;
        if (!in_array($plugin, \core_plugin_manager::standard_plugins_list('aiprovider') ?: [])) {
            $this->markTestSkipped("aiprovider_{$plugin} is not a core plugin in this Moodle version.");
        }
        $settings = [
            'openai' => ['model' => 'gpt-4o', 'endpoint' => 'https://api.openai.com/v1/chat/completions'],
            'gemini' => ['model' => 'gemini-2.0-flash',
                'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent'],
            'ollama' => ['model' => 'llama3.2'],
        ][$plugin];
        $settings['systeminstruction'] = 'Admin system instruction';
        $config = $plugin === 'ollama' ? ['endpoint' => 'http://localhost:11434'] : ['apikey' => '123'];

        if ($CFG->branch < 500) {
            // Moodle 4.5 has one provider per plugin, set up in the plugin's own config.
            $component = "aiprovider_{$plugin}";
            foreach ($config as $name => $value) {
                set_config($name, $value, $component);
            }
            foreach ($settings as $name => $value) {
                set_config('action_generate_text_' . $name, $value, $component);
            }
            \core\plugininfo\aiprovider::enable_plugin($plugin, 1);
            $classname = "\\{$component}\\provider";
            $provider = new $classname();
        } else {
            $manager = \core\di::get(\core_ai\manager::class);
            $provider = $manager->create_provider_instance(
                classname: "\\aiprovider_{$plugin}\\provider",
                name: $plugin,
                enabled: true,
                config: $config,
                actionconfig: [generate_text::class => ['enabled' => true, 'settings' => $settings]],
            );
        }
        set_config(
            'aiaction_' . aimanager::OPTION_GRADE_STUDENT_SUBMISSION,
            $this->setting_value($provider),
            constants::M_COMPONENT
        );
        return $provider;
    }

    /**
     * The value that chooses a provider in the plugin's settings: its name on Moodle 4.5, its id later.
     *
     * @param \core_ai\provider $provider
     * @return string
     */
    private function setting_value(\core_ai\provider $provider): string {
        global $CFG;
        return $CFG->branch < 500 ? $provider->get_name() : (string) $provider->id;
    }

    /**
     * Turn a provider off after it was chosen.
     *
     * @param \core_ai\provider $provider
     */
    private function disable_provider(\core_ai\provider $provider): void {
        global $CFG;
        if ($CFG->branch < 500) {
            \core\plugininfo\aiprovider::enable_plugin(substr($provider->get_name(), strlen('aiprovider_')), 0);
        } else {
            \core\di::get(\core_ai\manager::class)->disable_provider_instance($provider);
        }
    }

    /**
     * A reply body in the shape the given provider's API returns.
     *
     * @param string $plugin
     * @param string $text what the model wrote
     * @return Response
     */
    private function reply(string $plugin, string $text): Response {
        switch ($plugin) {
            case 'openai':
                $body = [
                    'id' => 'chatcmpl-1',
                    'model' => 'gpt-4o',
                    'choices' => [['message' => ['role' => 'assistant', 'content' => $text], 'finish_reason' => 'stop']],
                    'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20],
                    'system_fingerprint' => 'fp_1',
                ];
                break;
            case 'gemini':
                $body = [
                    'responseId' => 'r1',
                    'candidates' => [['content' => ['parts' => [['text' => $text]]], 'finishReason' => 'STOP']],
                    'usageMetadata' => ['promptTokenCount' => 10, 'totalTokenCount' => 30],
                ];
                break;
            default:
                $body = [
                    'model' => 'llama3.2',
                    'response' => $text,
                    'done_reason' => 'stop',
                    'prompt_eval_count' => 10,
                    'eval_count' => 20,
                ];
        }
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body));
    }

    /**
     * The text of the prompt in a recorded request.
     *
     * @param array $history from get_mocked_http_client()
     * @param int $index
     * @return string
     */
    private function sent(array $history, int $index): string {
        return json_encode(json_decode((string) $history[$index]['request']->getBody(), true), JSON_UNESCAPED_UNICODE);
    }

    public function test_extract_json_object(): void {
        $this->assertEquals('B1', aimanager::extract_json_object('{"cefr": "B1"}')->cefr);
        $fence = str_repeat(chr(96), 3);
        $this->assertEquals('B1', aimanager::extract_json_object("{$fence}json\n{\"cefr\": \"B1\"}\n{$fence}")->cefr);
        $this->assertEquals('B1', aimanager::extract_json_object("Here you go:\n{\"cefr\": \"B1\"}\nHope that helps.")->cefr);
        // Nested objects and braces inside strings survive.
        $nested = aimanager::extract_json_object('Sure! {"a": {"b": "x}y"}, "c": [1, 2]}');
        $this->assertEquals('x}y', $nested->a->b);
        // Paragraph breaks written as real newlines inside a string are repaired.
        $paragraphs = aimanager::extract_json_object("{\"feedback\": \"Well done.\n\nNext time, slow down.\"}");
        $this->assertEquals("Well done.\n\nNext time, slow down.", $paragraphs->feedback);
        $this->assertNull(aimanager::extract_json_object('The level is B1.'));
        $this->assertNull(aimanager::extract_json_object('[1, 2, 3]'));
        $this->assertNull(aimanager::extract_json_object(''));
    }

    public function test_cloudpoodll_is_default(): void {
        $this->assertFalse(aimanager::uses_moodle_ai());
        $this->assertEquals(
            aimanager::CLOUDPOODLL_OPTION,
            aimanager::get_action_provider_setting(aimanager::OPTION_GRADE_STUDENT_SUBMISSION)
        );
        $this->assertArrayHasKey(
            aimanager::CLOUDPOODLL_OPTION,
            aimanager::get_provider_options(aimanager::AIMANAGER_ACTIONS[aimanager::OPTION_GRADE_STUDENT_SUBMISSION])
        );
    }

    public function test_provider_is_offered_in_settings(): void {
        $provider = $this->use_provider('openai');
        $options = aimanager::get_provider_options(generate_text::class);
        $this->assertArrayHasKey($this->setting_value($provider), $options);
        $this->assertTrue(aimanager::uses_moodle_ai());
    }


    /**
     * An assignment, and a teacher in it as the current user.
     *
     * @return \stdClass the assignment, with cmid
     */
    private function create_assignment(): \stdClass {
        $gen = $this->getDataGenerator();
        $course = $gen->create_course();
        $assign = $gen->create_module('assign', ['course' => $course->id]);
        $this->setUser($gen->create_and_enrol($course, 'editingteacher'));
        return $assign;
    }

    /**
     * Grammar correction works with each core provider through the web service the grading form calls,
     * and what is sent is the stock request: no provider specific parameters, the admin's settings kept.
     *
     * @dataProvider provider_names
     * @param string $plugin
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('provider_names')]
    public function test_grammar_correction_each_provider(string $plugin): void {
        global $DB;
        $this->use_provider($plugin);
        $assign = $this->create_assignment();
        $history = [];
        ['mock' => $mock] = $this->get_mocked_http_client($history);
        $fence = str_repeat(chr(96), 3);
        $mock->append($this->reply($plugin, "{$fence}json\n{\"correctedtext\": \"I went home yesterday.\"}\n{$fence}"));

        $result = json_decode(external::check_grammar($assign->cmid, 'I go home yesterday.', 'fr-FR'));

        $this->assertSame('', $result->error);
        $this->assertSame('I went home yesterday.', $result->suggestions);
        $this->assertGreaterThan(2, strlen($result->grammarerrors));
        $this->assertNotEmpty($result->markedupsuggestions);

        $this->assertCount(1, $history);
        $body = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertArrayNotHasKey('response_format', $body);
        $this->assertArrayNotHasKey('max_tokens', $body);
        $sent = $this->sent($history, 0);
        if ($plugin !== 'gemini') {
            // Core's Gemini provider always sends core's default system instruction, not the admin's.
            $this->assertStringContainsString('Admin system instruction', $sent);
        }
        $this->assertStringContainsString('I go home yesterday.', $sent);
        $this->assertStringContainsString('French', $sent);

        // Logged in the core AI action register, in the assignment's context.
        $this->assertEquals(1, $DB->count_records('ai_action_register', [
            'actionname' => 'generate_text',
            'success' => 1,
            'contextid' => \context_module::instance($assign->cmid)->id,
        ]));
    }

    /**
     * Provider names for the data provider.
     *
     * @return array
     */
    public static function provider_names(): array {
        return [['openai'], ['gemini'], ['ollama']];
    }

    public function test_text_needing_no_corrections_gives_an_empty_list(): void {
        $this->use_provider('openai');
        $assign = $this->create_assignment();
        ['mock' => $mock] = $this->get_mocked_http_client();
        $mock->append($this->reply('openai', '{"correctedtext": "I went home."}'));

        $result = json_decode(external::check_grammar($assign->cmid, 'I went home.', 'en-US'));
        $this->assertSame('', $result->error);
        $this->assertSame('[]', $result->grammarerrors);
    }

    public function test_correction_is_tidied_as_cloudpoodll_does(): void {
        $this->use_provider('openai');
        ['mock' => $mock] = $this->get_mocked_http_client();
        $mock->append($this->reply('openai', '{"correctedtext": ". I went home. "}'));

        $textanalyser = new textanalyser('', 'I go home.', 'useast1', 'en-US');
        $this->assertSame('I went home.', $textanalyser->fetch_grammar_correction());
    }

    public function test_prose_reply_is_retried_once(): void {
        $this->use_provider('openai');
        ['mock' => $mock] = $this->get_mocked_http_client();
        $mock->append($this->reply('openai', 'Here is the corrected text: I went home.'));
        $mock->append($this->reply('openai', '{"correctedtext": "I went home."}'));

        $textanalyser = new textanalyser('', 'I go home.', 'useast1', 'en-US');
        $this->assertSame('I went home.', $textanalyser->fetch_grammar_correction());
        $this->assertDebuggingCalled();
    }

    public function test_prose_reply_twice_is_reported_to_the_teacher(): void {
        $this->use_provider('ollama');
        $assign = $this->create_assignment();
        ['mock' => $mock] = $this->get_mocked_http_client();
        $mock->append($this->reply('ollama', 'Good effort!'));
        $mock->append($this->reply('ollama', 'Really, good effort!'));

        $result = json_decode(external::check_grammar($assign->cmid, 'I go home.', 'en-US'));
        // Two "not the JSON asked for" messages, then the failure.
        $this->assertDebuggingCalledCount(3);
        $this->assertSame(get_string(
            'grammarcheckfailedreason',
            constants::M_COMPONENT,
            get_string('aiprovider_badreply', constants::M_COMPONENT)
        ), $result->error);
    }

    public function test_disabled_provider_fails_cleanly(): void {
        $provider = $this->use_provider('openai');
        $this->disable_provider($provider);

        $aimanager = new aimanager(null, 'en-US');
        $this->assertFalse($aimanager->grammar_correction('Some text.'));
        $this->assertDebuggingCalled();
        $this->assertEquals(get_string('aiprovider_unavailable', constants::M_COMPONENT), $aimanager->get_error_message());
    }

    public function test_provider_error_is_not_retried(): void {
        $this->use_provider('openai');
        ['mock' => $mock] = $this->get_mocked_http_client();
        $mock->append(new Response(
            401,
            ['Content-Type' => 'application/json'],
            json_encode(['error' => ['message' => 'Invalid API key']])
        ));
        $mock->append($this->reply('openai', '{"correctedtext": "Not asked for."}'));

        $textanalyser = new textanalyser('', 'Some text.', 'useast1', 'en-US');
        $this->assertFalse($textanalyser->fetch_grammar_correction());
        $this->assertDebuggingCalled();
        $this->assertNotEmpty($textanalyser->get_error_message());
        $this->assertCount(1, $mock);
    }
}

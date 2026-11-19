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

namespace assignfeedback_cloudpoodll\local;

/**
 * Prompts for the requests the AI manager sends to a Moodle AI provider.
 *
 * Every prompt is plain text for the stock core_ai generate_text action, so it works the same with any
 * provider. The role the model should take is written at the top of the prompt rather than set as a
 * system instruction, because the provider's system instruction belongs to the site admin. Each prompt
 * asks for one JSON object and says exactly which keys it must have; the AI manager checks the reply
 * against those keys. The wording follows the prompt Cloud Poodll uses.
 *
 * @package    assignfeedback_cloudpoodll
 * @copyright  2026 Justin Hunt (poodllsupport@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class aiprompts {
    /** @var string Said at the end of every prompt. */
    const JSON_ONLY = 'Reply with the JSON object only: no code fences, no explanations, no other text.';

    /**
     * The English name of a language code, for use in a prompt.
     *
     * @param string|null $language a language code such as en-US or ja
     * @return string
     */
    public static function language_name(?string $language): string {
        if (empty($language)) {
            return 'English';
        }
        if (class_exists(\Locale::class)) {
            $name = \Locale::getDisplayLanguage(str_replace('_', '-', $language), 'en');
            if (!empty($name) && strcasecmp($name, $language) !== 0) {
                return $name;
            }
        }
        return $language;
    }

    /**
     * Correct the grammar and spelling of a passage.
     *
     * Reply: {"correctedtext": "..."}
     *
     * @param string $passage
     * @param string|null $language
     * @return string
     */
    public static function grammar_correction(string $passage, ?string $language): string {
        $lang = self::language_name($language);
        $prompt = "You are a language teacher." . PHP_EOL;
        $prompt .= "Correct the grammar and spelling of the following {$lang} passage. Write in {$lang}." . PHP_EOL;
        $prompt .= 'Return a JSON object of the form {"correctedtext": "the corrected passage"}.' . PHP_EOL;
        $prompt .= self::JSON_ONLY . PHP_EOL;
        $prompt .= 'The passage is:' . PHP_EOL . $passage;
        return $prompt;
    }
}

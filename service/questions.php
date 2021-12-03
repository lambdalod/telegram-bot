<?php
class BotQuestions {
    private static array $tag_words = ['контакты?'];
    private static array $answers = ["Контакты руководителей вы можете найти на сайте в разделе \"Контакты\"."];
    public static function checkAnswer(string $string): ?string {
        for ($i = 0; $i < count(self::$tag_words); $i++) {
            $tw = self::$tag_words[$i];
            if (preg_match("/{$tw}/", $string)) {
                return self::$answers[$i];
            }
        }
        return NULL;
    }
}
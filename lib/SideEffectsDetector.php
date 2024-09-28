<?php

namespace staabm\SideEffectsDetector;

final class SideEffectsDetector {
    /**
     * @var array<int>
     */
    private array $sideEffectTokens = [
        T_CLASS,
        T_FUNCTION,
        T_NEW,
        T_EVAL,
        T_EXIT,
        T_GLOBAL,
        T_GOTO,
        T_HALT_COMPILER,
        T_INCLUDE,
        T_INCLUDE_ONCE,
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_THROW,
        T_UNSET,
        T_UNSET_CAST
    ];

    private const OUTPUT_TOKENS = [
        T_PRINT,
        T_ECHO,
        T_INLINE_HTML
    ];

    /**
     * @var array<string, array{'hasSideEffects': bool}>
     */
    private array $functionMetadata;

    public function __construct() {
        $functionMeta = require __DIR__ . '/functionMetadata.php';
        if (!is_array($functionMeta)) {
            throw new \RuntimeException('Invalid function metadata');
        }
        $this->functionMetadata = $functionMeta;

        if (defined('T_ENUM')) {
            $this->sideEffectTokens[] = T_ENUM;
        }
    }

    /**
     * @api
     *
     * @param bool $ignoreOutput Whether to ignore output functions like echo, print, etc.
     *
     * @return bool|null true if side effects are detected, false if no side effects are detected, null if it cannot be determined.
     */
    public function hasSideEffects(string $code, bool $ignoreOutput = false): ?bool {
        $tokens = token_get_all($code);

        $maybeSideEffects = false;
        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if ($this->isAnonymousFunction($tokens, $i)) {
                continue;
            }

            if (in_array($token[0], $this->sideEffectTokens, true)) {
                return true;
            }
            if (!$ignoreOutput && in_array($token[0], self::OUTPUT_TOKENS, true)) {
                return true;
            }

            $functionCall = $this->getFunctionCall($tokens, $i);
            if ($functionCall !== null) {
                if (array_key_exists($functionCall, $this->functionMetadata)) {
                    if ($this->functionMetadata[$functionCall]['hasSideEffects'] === true) {
                        return true;
                    }
                } else {
                    $maybeSideEffects = true;
                }
            }
        }

        return $maybeSideEffects ? null : false;
    }

    /**
     * @param array<int, array{0:int,1:string,2:int}|string|int> $tokens
     */
    private function getFunctionCall(array $tokens, int $index): ?string {
        if (
            array_key_exists($index, $tokens)
            && is_array($tokens[$index])
            && $tokens[$index][0] === T_STRING
        ) {
            $nextIndex = $index+1;
            while (
                array_key_exists($nextIndex, $tokens)
                && is_int($tokens[$nextIndex])
                && $tokens[$nextIndex] === T_WHITESPACE
            ) {
                $nextIndex++;
            }

            if (
                array_key_exists($nextIndex, $tokens)
                && $tokens[$nextIndex] === '('
            ) {
                return $tokens[$index][1];
            }
        }

        return null;
    }

    /**
     * @param array<int, array{0:int,1:string,2:int}|string|int> $tokens
     */
    private function isAnonymousFunction(array $tokens, int $index): bool
    {
        if (
            array_key_exists($index, $tokens)
            && is_array($tokens[$index])
            && $tokens[$index][0] === T_FUNCTION
        ) {
            $nextIndex = $index+1;
            while (
                array_key_exists($nextIndex, $tokens)
                && is_array($tokens[$nextIndex])
                && $tokens[$nextIndex][0] === T_WHITESPACE
            ) {
                $nextIndex++;
            }

            if (
                array_key_exists($nextIndex, $tokens)
                && $tokens[$nextIndex] === '('
            ) {
                return true;
            }
        }

        return false;
    }
}

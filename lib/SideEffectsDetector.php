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
     */
    public function hasSideEffects(string $code): bool {
        $tokens = token_get_all($code);

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if (in_array($token[0], $this->sideEffectTokens, true)) {
                return true;
            }
            if (in_array($token[0], self::OUTPUT_TOKENS, true)) {
                return true;
            }

            $functionCall = $this->getFunctionCall($tokens, $i);
            if ($functionCall !== null) {
                if (array_key_exists($functionCall, $this->functionMetadata)) {
                    if ($this->functionMetadata[$functionCall]['hasSideEffects'] === true) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @api
     */
    public function hasSideEffectsIgnoreOutput(string $code): bool {
        $tokens = token_get_all($code);

        foreach ($tokens as $i => $token) {
            if (!is_array($token)) {
                continue;
            }

            if (in_array($token[0], $this->sideEffectTokens, true)) {
                return true;
            }
            if (in_array($token[0], self::OUTPUT_TOKENS, true)) {
                continue; // ignore output
            }

            $functionCall = $this->getFunctionCall($tokens, $i);
            if ($functionCall !== null) {
                if (array_key_exists($functionCall, $this->functionMetadata)) {
                    if ($this->functionMetadata[$functionCall]['hasSideEffects'] === true) {
                        return true;
                    }
                }
            }
        }

        return false;
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
}

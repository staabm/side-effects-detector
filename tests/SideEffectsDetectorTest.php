<?php

namespace staabm\SideEffectsDetector\Tests;

use PHPUnit\Framework\TestCase;
use staabm\SideEffectsDetector\SideEffect;
use staabm\SideEffectsDetector\SideEffectsDetector;

class SideEffectsDetectorTest extends TestCase {

    /**
     * @dataProvider dataHasSideEffects
     */
    public function testHasSideEffects(string $code, array $expected): void {
        $detector = new SideEffectsDetector();
        self::assertSame($expected, $detector->getSideEffects($code));
    }

    static public function dataHasSideEffects():iterable
    {
        yield ['<?php function abc() {}', [SideEffect::SCOPE_POLLUTION]];

        if (PHP_VERSION_ID < 80000) {
            // PHP7.x misses accurate reflection information
            yield ['<?php gc_enable();', [SideEffect::MAYBE]];
            yield ['<?php gc_enabled();', []];
            yield ['<?php gc_disable();', [SideEffect::MAYBE]];
        } else {
            yield ['<?php gc_enable();', [SideEffect::UNKNOWN_CLASS]];
            yield ['<?php gc_enabled();', []];
            yield ['<?php gc_disable();', [SideEffect::UNKNOWN_CLASS]];
        }
        yield ['<?php $_GET["A"] = 1;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $_POST["A"] = 1;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $_COOKIE["A"] = 1;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $_REQUEST["A"] = 1;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $this->x = 1;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php MyClass::$x = 1;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $this->doFoo();', [SideEffect::MAYBE]];
        yield ['<?php MyClass::doFooBar();', [SideEffect::MAYBE]];
        yield ['<?php putenv("MY_X=1");', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $x = getenv("MY_X");', []];
        yield ['<?php ini_set("memory_limit", "1024M");', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php $x = ini_get("memory_limit");', []];
        yield ['<?php echo "Hello World";', [SideEffect::STANDARD_OUTPUT]];
        yield ['<?php print("Hello World");', [SideEffect::STANDARD_OUTPUT]];
        yield ['<?php fopen("file.txt");', [SideEffect::INPUT_OUTPUT]];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or die("skip because attributes are only available since PHP 8.0");', [SideEffect::PROCESS_EXIT]];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or echo("skip because attributes are only available since PHP 8.0");', [SideEffect::STANDARD_OUTPUT]];
        yield ['<?php die(0);', [SideEffect::PROCESS_EXIT]];
        yield ['<?php exit(0);', [SideEffect::PROCESS_EXIT]];
        yield ['<?php eval($x);', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php global $x; $x = [];', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php goto somewhere;', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php include "some-file.php";', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php include_once "some-file.php";', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php require "some-file.php";', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php require_once "some-file.php";', [SideEffect::SCOPE_POLLUTION]];
        // constructor might have side-effects
        yield ['<?php throw new RuntimeException("foo");', [SideEffect::SCOPE_POLLUTION, SideEffect::MAYBE]];
        yield ['<?php unknownFunction($x);', [SideEffect::MAYBE]];
        yield ['<?php echo unknownFunction($x);', [SideEffect::STANDARD_OUTPUT, SideEffect::MAYBE]];
        yield ['<?php unset($x);', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php (unset)$x;', [SideEffect::SCOPE_POLLUTION]];
        // constructor might have side-effects
        yield ['<?php new SomeClass();', [SideEffect::SCOPE_POLLUTION, SideEffect::MAYBE]];
        yield ['<?php function abc() {}', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php class abc {}', [SideEffect::SCOPE_POLLUTION]];
        yield ['<?php (function (){})();', []];
        yield ['<?php (function(){})();', []];
        yield ['<?php (function(){echo "hi";})();', [SideEffect::STANDARD_OUTPUT]];
        yield ['<?php (function (){echo "hi";})();', [SideEffect::STANDARD_OUTPUT]];

        yield ['<?php include "some-file.php"; echo "hello world"; exit(1);',
            [SideEffect::SCOPE_POLLUTION, SideEffect::STANDARD_OUTPUT, SideEffect::PROCESS_EXIT],
        ];
    }
}
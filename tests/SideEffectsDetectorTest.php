<?php

namespace staabm\SideEffectsDetector\Tests;

use PHPUnit\Framework\TestCase;
use staabm\SideEffectsDetector\SideEffectsDetector;

class SideEffectsDetectorTest extends TestCase {

    /**
     * @dataProvider dataHasSideEffects
     */
    public function testHasSideEffects(string $code, ?bool $expected, ?bool $expectedIgnoreOutput): void {
        $detector = new SideEffectsDetector();
        self::assertEquals($expected, $detector->hasSideEffects($code));

        self::assertEquals($expectedIgnoreOutput, $detector->hasSideEffects($code, true));
    }

    static public function dataHasSideEffects():iterable
    {
        yield ['<?php gc_enable();', true, true];
        yield ['<?php gc_disable();', true, true];
        yield ['<?php $_GET["A"] = 1;', true, true];
        yield ['<?php $_POST["A"] = 1;', true, true];
        yield ['<?php $_COOKIE["A"] = 1;', true, true];
        yield ['<?php $_REQUEST["A"] = 1;', true, true];
        yield ['<?php $this->x = 1;', true, true];
        yield ['<?php $this->doFoo();', true, true];
        yield ['<?php putenv("MY_X=1");', true, true];
        yield ['<?php $x = getenv("MY_X");', false, false];
        yield ['<?php ini_set("memory_limit", "1024M");', true, true];
        yield ['<?php $x = ini_get("memory_limit");', false, false];
        yield ['<?php echo "Hello World";', true, false];
        yield ['<?php print("Hello World");', true, false];
        yield ['<?php fopen("file.txt");', true, true];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or die("skip because attributes are only available since PHP 8.0");', true, true];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or echo("skip because attributes are only available since PHP 8.0");', true, false];
        yield ['<?php die(0);', true, true];
        yield ['<?php exit(0);', true, true];
        yield ['<?php eval($x);', true, true];
        yield ['<?php global $x; $x = false;', true, true];
        yield ['<?php goto somewhere;', true, true];
        yield ['<?php include "some-file.php";', true, true];
        yield ['<?php include_once "some-file.php";', true, true];
        yield ['<?php require "some-file.php";', true, true];
        yield ['<?php require_once "some-file.php";', true, true];
        yield ['<?php throw new RuntimeException("foo");', true, true];
        yield ['<?php unknownFunction($x);', null, null];
        yield ['<?php echo unknownFunction($x);', true, null];
        yield ['<?php unset($x);', true, true];
        yield ['<?php (unset)$x;', true, true];
        yield ['<?php new SomeClass();', true, true];
        yield ['<?php function abc() {}', true, true];
        yield ['<?php class abc() {}', true, true];
        yield ['<?php (function (){})();', false, false];
        yield ['<?php (function(){})();', false, false];
        yield ['<?php (function(){echo "hi";})();', true, false];
        yield ['<?php (function (){echo "hi";})();', true, false];
    }
}
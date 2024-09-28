<?php

namespace staabm\SideEffectsDetector\Tests;

use PHPUnit\Framework\TestCase;
use staabm\SideEffectsDetector\SideEffectsDetector;

class SideEffectsDetectorTest extends TestCase {

    /**
     * @dataProvider dataHasSideEffects
     */
    public function testHasSideEffects(string $code, ?bool $expected): void {
        $detector = new SideEffectsDetector();
        self::assertEquals($expected, $detector->hasSideEffects($code));
    }

    static public function dataHasSideEffects():iterable
    {
        yield ['<?php echo "Hello World";', true];
        yield ['<?php print("Hello World");', true];
        yield ['<?php fopen("file.txt");', true];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or die("skip because attributes are only available since PHP 8.0");', true];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or echo("skip because attributes are only available since PHP 8.0");', true];
        yield ['<?php exit(0);', true];
        yield ['<?php eval($x);', true];
        yield ['<?php global $x; $x = false;', true];
        yield ['<?php goto somewhere;', true];
        yield ['<?php include "some-file.php";', true];
        yield ['<?php include_once "some-file.php";', true];
        yield ['<?php require "some-file.php";', true];
        yield ['<?php require_once "some-file.php";', true];
        yield ['<?php throw new RuntimeException("foo");', true];
        yield ['<?php unknownFunction($x);', null];
        yield ['<?php unset($x);', true];
        yield ['<?php (unset)$x;', true];
        yield ['<?php new SomeClass();', true];
        yield ['<?php function abc() {}', true];
        yield ['<?php class abc() {}', true];
    }

    /**
     * @dataProvider dataHasSideEffectsIgnoreOutput
     */
    public function testHasSideEffectsIgnoreOutput(string $code, ?bool $expected): void {
        $detector = new SideEffectsDetector();
        self::assertEquals($expected, $detector->hasSideEffects($code, true));
    }

    static public function dataHasSideEffectsIgnoreOutput():iterable
    {
        yield ['<?php echo "Hello World";', false];
        yield ['<?php print("Hello World");', false];
        yield ['<?php fopen("file.txt");', true];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or die("skip because attributes are only available since PHP 8.0");', true];
        yield ['<?php version_compare(PHP_VERSION, "8.0", ">=") or echo("skip because attributes are only available since PHP 8.0");', false];
        yield ['<?php exit(0);', true];
        yield ['<?php eval($x);', true];
        yield ['<?php global $x; $x = false;', true];
        yield ['<?php goto somewhere;', true];
        yield ['<?php include "some-file.php";', true];
        yield ['<?php include_once "some-file.php";', true];
        yield ['<?php require "some-file.php";', true];
        yield ['<?php require_once "some-file.php";', true];
        yield ['<?php throw new RuntimeException("foo");', true];
        yield ['<?php unknownFunction($x);', null];
        yield ['<?php unset($x);', true];
        yield ['<?php (unset)$x;', true];
        yield ['<?php new SomeClass();', true];
        yield ['<?php function abc() {}', true];
        yield ['<?php class abc() {}', true];
    }
}
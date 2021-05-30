<?php

namespace Gzhegow\VarDumper\Tests;

use PHPUnit\Framework\TestCase;
use Gzhegow\VarDumper\VarDumper;
use Gzhegow\VarDumper\Tests\Services\BService;
use Gzhegow\VarDumper\Tests\Services\AService;
use Gzhegow\VarDumper\Exceptions\Runtime\ShutdownException;


class VarDumperTest extends TestCase
{
    public function getVarDumper() : VarDumper
    {
        return VarDumper::getInstance();
    }


    /**
     * @return void
     */
    protected function setUp() : void
    {
        $this->getVarDumper()->nonInteractive(true);
    }


    public function testGb()
    {
        $vd = $this->getVarDumper();

        $this->assertEquals("\"hello\"", $vd->dumpGet('hello'));
        $this->assertEquals("\"hello\" \"hello\"", $vd->dumpGet('hello', 'hello'));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]", $vd->dumpGet([ 'hello' ]));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]\n\"hello\"", $vd->dumpGet([ 'hello' ], 'hello'));
    }

    public function testGgn()
    {
        $vd = $this->getVarDumper();

        for ( $i = 0; $i < 2; $i++ ) {
            $result = $vd->ggn('test', 3, 'hello');

            if ($i === 0) {
                $this->assertEquals([ 'hello' ], $result);
            }
        }

        $this->expectException(ShutdownException::class);

        $vd->ggn('test', 3, 'hello');
    }

    public function testGgt()
    {
        $vd = $this->getVarDumper();

        for ( $i = 0; $i < 4; $i++ ) {
            $result = $vd->ggt('test', [ 2, 2 ], 'hello');

            if ($i === 0) {
                $this->assertEquals(null, $result);
            }
            if ($i === 1) {
                $this->assertEquals(null, $result);
            }
            if ($i === 2) {
                $this->assertEquals([ 'hello' ], $result);
            }
            if ($i === 3) {
                $this->assertEquals([ 'hello' ], $result);
            }
        }

        $this->expectException(ShutdownException::class);

        $vd->ggt('test', [ 2, 2 ], 'hello');
    }

    public function testGgr()
    {
        $vd = $this->getVarDumper();

        $func = function () use ($vd) {
            return [
                $vd->gdump('hello'),
                $vd->ggdump('world'),
            ];
        };

        $this->assertEquals([
            [ 'hello' ],
            [ 'world' ],
        ], $func());

        $vd->ggroup(1);

        [ $arguments, $curry ] = $func();

        $this->assertEquals([ 'hello' ], $arguments);
        $this->assertInstanceOf(\Closure::class, $curry);

        $this->assertEquals(null, $curry());
        $this->assertEquals([ 'world' ], $curry(1));

        $vd->ggroupFlush();

        $this->assertEquals([
            [ 'hello' ],
            [ 'world' ],
        ], $func());
    }

    public function testGcast()
    {
        $vd = $this->getVarDumper();

        $a = new AService();
        $b = $a->getB();

        $idA = spl_object_id($a);
        $idB = spl_object_id($b);

        $expected = <<<DOC
Gzhegow\VarDumper\Tests\Services\AService {#$idA
  #b: Gzhegow\VarDumper\Tests\Services\BService {#$idB
    #value: 123
  }
}
DOC;

        $expectedCasters = <<<DOC
Gzhegow\VarDumper\Tests\Services\AService {#$idA
  #b: Gzhegow\VarDumper\Tests\Services\BService {#$idB}
}
DOC;

        $this->assertEquals($expected, $vd->dumpGet($a));

        gcast($casters = [
            BService::class => 'is_null',
        ]);

        $this->assertEquals($expectedCasters, $vd->dumpGet($a));

        gcast();

        $this->assertEquals($expected, $vd->dumpGet($a));
    }
}

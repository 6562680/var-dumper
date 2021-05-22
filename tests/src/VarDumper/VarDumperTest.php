<?php

namespace Gzhegow\VarDumper\Tests;

use PHPUnit\Framework\TestCase;
use Gzhegow\VarDumper\VarDumper;
use Gzhegow\VarDumper\ShutdownException;


class VarDumperTest extends TestCase
{
    public function getVarDumper() : VarDumper
    {
        return VarDumper::getInstance();
    }


    public function testGb()
    {
        $vd = $this->getVarDumper();

        $this->assertEquals("\"hello\"", $vd->dumpGet('hello'));
        $this->assertEquals("\"hello\" \"hello\"", $vd->dumpGet('hello', 'hello'));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]", $vd->dumpGet([ 'hello' ]));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]\n\"hello\"", $vd->dumpGet([ 'hello' ], 'hello'));
    }

    public function testGcast()
    {
        $vd = $this->getVarDumper();

        $a = new AService();
        $b = $a->getB();

        $idA = spl_object_id($a);
        $idB = spl_object_id($b);

        $expected = <<<DOC
Gzhegow\VarDumper\Tests\AService {#$idA
  #b: Gzhegow\VarDumper\Tests\BService {#$idB
    #value: 123
  }
}
DOC;

        $expectedCasters = <<<DOC
Gzhegow\VarDumper\Tests\AService {#$idA
  #b: Gzhegow\VarDumper\Tests\BService {#$idB}
}
DOC;

        $this->assertEquals($expected, $vd->dumpGet($a));

        gcast($casters = [
            BService::class => 'is_null',
        ]);

        $this->assertEquals($expectedCasters, $vd->dumpGet($a));

        $pop = gpop();
        $this->assertIsCallable($pop[ BService::class ]);

        $this->assertEquals($expected, $vd->dumpGet($a));
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


    /**
     * @return void
     */
    protected function setUp() : void
    {
        $this->getVarDumper()->nonInteractive(true);
    }
}

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


    public function testDumpGet()
    {
        $vd = $this->getVarDumper();

        $this->assertEquals("\"hello\"", $vd->dumpGet('hello'));
        $this->assertEquals("\"hello\" \"hello\"", $vd->dumpGet('hello', 'hello'));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]", $vd->dumpGet([ 'hello' ]));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]\n\"hello\"", $vd->dumpGet([ 'hello' ], 'hello'));
    }


    public function testGgn()
    {
        $dumper = $this->getVarDumper();

        $this->expectException(ShutdownException::class);

        for ( $i = 0; $i < 2; $i++ ) {
            $dumper->ggn('test', 3, 'hello');
        }

        $this->expectException(ShutdownException::class);

        $dumper->ggn('test', 3, 'hello');
    }

    public function testGgt()
    {
        $dumper = $this->getVarDumper();

        for ( $i = 0; $i < 4; $i++ ) {
            $dumper->ggt('test', [ 2, 2 ], 'hello');
        }

        $this->expectException(ShutdownException::class);

        $dumper->ggt('test', [ 2, 2 ], 'hello');
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

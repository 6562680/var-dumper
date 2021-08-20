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


    protected function setUp() : void
    {
        $this->getVarDumper()->nonInteractive(true);
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

        $this->assertEquals($expected, $vd->buff($a));

        gcast($casters = [
            BService::class => 'is_null',
        ]);

        $this->assertEquals($expectedCasters, $vd->buff($a));

        gcast();

        $this->assertEquals($expected, $vd->buff($a));
    }


    public function testBuff()
    {
        $vd = $this->getVarDumper();

        $this->assertEquals("\"hello\"", $vd->buff('hello'));
        $this->assertEquals("\"hello\" \"hello\"", $vd->buff('hello', 'hello'));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]", $vd->buff([ 'hello' ]));
        $this->assertEquals("array:1 [\n  0 => \"hello\"\n]\n\"hello\"", $vd->buff([ 'hello' ], 'hello'));
    }


    public function testGd()
    {
        $dumper = $this->getVarDumper();

        $dumper->pauseDump('hello');

        $this->assertTrue(true);
    }

    public function testGdd()
    {
        $dumper = $this->getVarDumper();

        $this->expectException(ShutdownException::class);

        $dumper->pauseDumpExit('hello');

        $this->assertTrue(true);
    }

    public function testGdr()
    {
        $dumper = $this->getVarDumper();

        $key = 'key';
        $range = [ 2, 4 ];

        for ( $i = 0; $i < 4; $i++ ) {
            $dumper->pauseDumpRange($key, $range, 'hello');
        }

        $this->expectException(ShutdownException::class);

        $dumper->pauseDumpRange($key, $range, 'hello');

        $this->assertTrue(true);
    }


    public function testGgd()
    {
        $dumper = $this->getVarDumper();

        $closure = $dumper->funcDecorateGroup([ $dumper, 'pauseDump' ]);

        $dumper->ggroup('test');

        $closure($group = null, 'hello');
        $closure($group = '', 'hello');
        $closure($group = 'test', 'hello');

        $dumper->reset();

        $this->assertTrue(true);
    }

    public function testGgdd()
    {
        $dumper = $this->getVarDumper();

        $closure = $dumper->funcDecorateGroup([ $dumper, 'pauseDumpExit' ]);

        $this->expectException(ShutdownException::class);

        $dumper->ggroup('test');

        $closure($group = null, 'hello');
        $closure($group = '', 'hello');
        $closure($group = 'test', 'hello');

        $dumper->reset();

        $this->assertTrue(true);
    }

    public function testGgdr()
    {
        $dumper = $this->getVarDumper();

        $key = 'key';
        $range = [ 2, 4 ];

        $closure = $dumper->funcDecorateGroup([ $dumper, 'pauseDumpRange' ]);

        $dumper->ggroup('test');

        for ( $i = 0; $i < 4; $i++ ) {
            $closure($group = 'test', $key, $range, 'hello');
        }

        $this->expectException(ShutdownException::class);

        $closure($group = 'test', $key, $range, 'hello');

        $dumper->reset();

        $this->assertTrue(true);
    }
}

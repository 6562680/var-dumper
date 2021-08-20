<?php

use Gzhegow\VarDumper\VarDumper;
use Gzhegow\VarDumper\Exceptions\Runtime\ShutdownException;


if (! function_exists('gbuff')) {
    /**
     * буферизует аргументы для вывода в лог, откуда отладка была вызвана, завершает программу
     *
     * @param mixed ...$arguments
     *
     * @return string
     */
    function gbuff(...$arguments) : string
    {
        $result = VarDumper::getInstance()
            ->buff(...$arguments);

        return $result;
    }
}


if (! function_exists('gcast')) {
    /**
     * настраивает casters для последующего вызова gdump
     *
     * @param null|array $casters
     *
     * @return VarDumper
     */
    function gcast(array $casters = null)
    {
        $key = VarDumper::dumperKey(2);

        $dumper = VarDumper::getInstance();

        if (func_num_args() === 0) {
            $dumper->gcastPop();

            return $dumper;
        }

        $dumper->gcast($casters, $key);

        return $dumper;
    }
}


if (! function_exists('ggroup')) {
    function ggroup(string $group = null)
    {
        $dumper = VarDumper::getInstance();

        if (func_num_args() === 0) {
            $dumper->reset();

            return $dumper;
        }

        $dumper->ggroup($group);

        return $dumper;
    }
}


if (! function_exists('gd')) {
    function gd(...$arguments) : void
    {
        VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->pauseDump(...$arguments);
    }
}

if (! function_exists('gdd')) {
    function gdd(...$arguments) : void
    {
        try {
            VarDumper::getInstance()
                ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
                ->pauseDumpExit(...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}

if (! function_exists('gdr')) {
    function gdr($range, ...$arguments) : void
    {
        $key = VarDumper::dumperKey(2);

        try {
            VarDumper::getInstance()
                ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
                ->pauseDumpRange($key, $range, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}


if (! function_exists('ggd')) {
    function ggd(string $group = null, ...$arguments) : void
    {
        $dumper = VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ]);

        $closure = $dumper->funcDecorateGroup([ $dumper, 'pauseDump' ]);

        try {
            $closure($group, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}

if (! function_exists('ggdd')) {
    function ggdd(string $group = null, ...$arguments) : void
    {
        $dumper = VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ]);

        $closure = $dumper->funcDecorateGroup([ $dumper, 'pauseDumpExit' ]);

        try {
            $closure($group, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}

if (! function_exists('ggdr')) {
    function ggdr(string $group = null, ...$arguments) : void
    {
        $dumper = VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ]);

        $closure = $dumper->funcDecorateGroup([ $dumper, 'pauseDumpRange' ]);

        try {
            $closure($group, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}

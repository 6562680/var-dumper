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
        $result = VarDumper::getInstance()->dumpGet(...$arguments);

        return $result;
    }
}

if (! function_exists('gpause')) {
    /**
     * после каждого вывода ожидает нажатия клавиши от пользователя консоли
     *
     * @param mixed ...$arguments
     *
     * @return void
     */
    function gpause(...$arguments) : void
    {
        VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->pause(...$arguments);
    }
}

if (! function_exists('gdump')) {
    /**
     * выводит аргументы для просмотра при отладке
     *
     * @param mixed ...$arguments
     *
     * @return void
     */
    function gdump(...$arguments) : void
    {
        VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->dumpPause(...$arguments);
    }
}


if (! function_exists('g')) {
    /**
     * выводит аргументы для просмотра при отладке
     *
     * @param mixed ...$arguments
     */
    function g(...$arguments) : void
    {
        VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->dumpPause(...$arguments);
    }
}

if (! function_exists('gg')) {
    /**
     * выводит аргументы для просмотра при отладке, откуда отладка была вызвана, завершает программу
     *
     * @param mixed ...$arguments
     *
     * @return null|Closure
     */
    function gg(...$arguments) : ?\Closure
    {
        VarDumper::getInstance()
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->dumpPause(...$arguments);

        die(1);
    }
}


if (! function_exists('ggn')) {
    /**
     * выводит аргументы для просмотра при отладке
     * выводит и завершает программу только на $n-той итерации
     *
     * @param int   $n
     * @param mixed ...$arguments
     *
     * @return void
     */
    function ggn(int $n, ...$arguments) : void
    {
        $key = VarDumper::gkey(2);

        try {
            VarDumper::getInstance()
                ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
                ->ggn($key, $n, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}

if (! function_exists('ggt')) {
    /**
     * выводит аргументы для просмотра при отладке
     * позволяет задать диапазон вывода в итерациях - [1,5] - вывести 4 раза, 1 пропустить
     * завершает программу в случае если функция вызвана $limit раз
     *
     * @param int|int[] $limit
     * @param mixed     ...$arguments
     *
     * @return void
     */
    function ggt($limit, ...$arguments) : void
    {
        $key = VarDumper::gkey(2);

        try {
            VarDumper::getInstance()
                ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
                ->ggt($key, $limit, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }
    }
}


if (! function_exists('ggr')) {
    /**
     * устанавливает новую группу
     *
     * @param null|string $group
     *
     * @return VarDumper
     */
    function ggr(string $group = null)
    {
        $dumper = VarDumper::getInstance();

        if (func_num_args() === 0) {
            $dumper->ggroupFlush();

            return $dumper;
        }

        $dumper->ggroup($group);

        return $dumper;
    }
}

if (! function_exists('gd')) {
    /**
     * выводит аргументы для просмотра при отладке, откуда отладка была вызвана, завершает программу
     *
     * @param mixed ...$arguments
     *
     * @return Closure
     */
    function gd(...$arguments) : \Closure
    {
        $dumper = VarDumper::getInstance();

        $result = $dumper
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->dumpPauseGroup(...$arguments);

        $result = ! $dumper->hasGroups()
            ? function (string $group = null) { }
            : $result;

        return $result;
    }
}

if (! function_exists('ggd')) {
    /**
     * выводит аргументы для просмотра при отладке, откуда отладка была вызвана, завершает программу
     *
     * @param mixed ...$arguments
     *
     * @return Closure
     */
    function ggd(...$arguments) : \Closure
    {
        $dumper = VarDumper::getInstance();

        $result = $dumper
            ->withTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[ 0 ])
            ->dumpPauseGroup(...$arguments);

        $result = ! $dumper->hasGroups()
            ? function (string $group = null) { }
            : function (string $group = null) use ($result) {
                $result($group);

                die(1);
            };

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
        $dumper = VarDumper::getInstance();

        if (func_num_args() === 0) {
            $dumper->gcastPop();

            return $dumper;
        }

        $dumper->gcast($casters, VarDumper::gkey(2));

        return $dumper;
    }
}

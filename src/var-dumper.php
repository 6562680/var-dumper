<?php

use Gzhegow\VarDumper\VarDumper;
use Gzhegow\VarDumper\Exceptions\Runtime\ShutdownException;


if (! function_exists('gpause')) {
    /**
     * после каждого вывода ожидает нажатия клавиши от пользователя консоли
     *
     * @param mixed ...$arguments
     *
     * @return array
     */
    function gpause(...$arguments) : array
    {
        $result = VarDumper::getInstance()->gpause(...$arguments);

        return $result;
    }
}

if (! function_exists('gdump')) {
    /**
     * выводит аргументы для просмотра при отладке
     *
     * @param mixed ...$arguments
     *
     * @return array
     */
    function gdump(...$arguments) : array
    {
        $result = VarDumper::getInstance()->gdump(...$arguments);

        return $result;
    }
}


if (! function_exists('g')) {
    /**
     * выводит аргументы для просмотра при отладке
     *
     * @param mixed ...$arguments
     *
     * @return array
     */
    function g(...$arguments) : array
    {
        VarDumper::getInstance()->dump(...$arguments);

        return $arguments;
    }
}

if (! function_exists('gg')) {
    /**
     * выводит аргументы для просмотра при отладке, откуда отладка была вызвана, завершает программу
     *
     * @param mixed ...$arguments
     *
     * @return array
     */
    function gg(...$arguments) : array
    {
        VarDumper::getInstance()->dump(...$arguments);

        die(1);
    }
}

if (! function_exists('gb')) {
    /**
     * буферизует аргументы для вывода в лог, откуда отладка была вызвана, завершает программу
     *
     * @param mixed ...$arguments
     *
     * @return string
     */
    function gb(...$arguments) : string
    {
        $result = VarDumper::getInstance()->dumpGet(...$arguments);

        return $result;
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
     * @return array
     */
    function ggn(int $n, ...$arguments) : array
    {
        $key = VarDumper::gkey(2);

        try {
            $result = VarDumper::getInstance()->ggn($key, $n, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }

        return $result;
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
     * @return array
     */
    function ggt($limit, ...$arguments) : array
    {
        $key = VarDumper::gkey(2);

        try {
            $result = VarDumper::getInstance()->ggt($key, $limit, ...$arguments);
        }
        catch ( ShutdownException $e ) {
            die(1);
        }

        return $result;
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
    function ggr(?string $group)
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

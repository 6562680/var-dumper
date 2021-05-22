<?php

namespace Gzhegow\VarDumper;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;


/**
 * VarDumper
 */
class VarDumper
{
    /**
     * @var bool
     */
    protected $noColor = false;
    /**
     * @var bool
     */
    protected $nonInteractive = false;


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->noColor = $_SERVER[ 'NO_COLOR' ] ?? false;
        $this->nonInteractive = false;
    }



    /**
     * @return VarCloner
     */
    public function newCloner() : VarCloner
    {
        end(static::$casters);

        $casters = ( null !== ( $key = key(static::$casters) ) )
            ? static::$casters[ $key ]
            : [];

        $cloner = new VarCloner();
        $cloner->addCasters($casters);

        return $cloner;
    }


    /**
     * @return AbstractDumper
     */
    public function newDumper() : AbstractDumper
    {
        $dumper = PHP_SAPI === 'cli'
            ? $this->newCliDumper()
            : $this->newHtmlDumper();

        return $dumper;
    }

    /**
     * @return CliDumper
     */
    public function newCliDumper() : CliDumper
    {
        $dumper = new CliDumper();

        return $dumper;
    }

    /**
     * @return HtmlDumper
     */
    public function newHtmlDumper() : HtmlDumper
    {
        $dumper = new HtmlDumper();

        return $dumper;
    }


    /**
     * @return bool|mixed
     */
    public function isNoColor() : bool
    {
        return $this->noColor;
    }

    /**
     * @return bool|mixed
     */
    public function isNonInteractive() : bool
    {
        return $this->nonInteractive;
    }


    /**
     * @param bool|mixed $noColor
     *
     * @return static
     */
    public function noColor(bool $noColor)
    {
        $this->noColor = $noColor;

        // internal Symfony/VarDumper setting
        $_SERVER[ 'NO_COLOR' ] = $noColor;

        return $this;
    }

    /**
     * @param bool|mixed $nonInteractive
     *
     * @return static
     */
    public function nonInteractive(bool $nonInteractive)
    {
        $this->nonInteractive = $nonInteractive;

        return $this;
    }


    /**
     * @param array       $casters
     * @param null|string $key
     *
     * @return static
     */
    public function push(array $casters, string $key = null)
    {
        $list = [];

        foreach ( $casters as $type => $caster ) {
            if (! is_callable($caster)) {
                throw new \InvalidArgumentException('Each caster should be callable');
            }

            $list[ $type ] = static::funcBind($caster);
        }

        if (null === $key) {
            static::$casters[] = $list;

        } else {
            end(static::$casters);

            if ($key !== key(static::$casters)) {
                unset(static::$casters[ $key ]);

                static::$casters[ $key ] = $list;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function pop() : array
    {
        return array_pop(static::$casters) ?: [];
    }


    /**
     * @param mixed ...$arguments
     *
     * @return array
     */
    public function dump(...$arguments) : array
    {
        $cloner = $this->newCloner();
        $dumper = $this->newDumper();

        foreach ( $arguments as $argument ) {
            try {
                $var = $cloner->cloneVar($argument);

                $dumper->dump($var);
            }
            catch ( \ErrorException $e ) {
                throw new \RuntimeException($e->getMessage(), null, $e);
            }
        }

        return $arguments;
    }

    /**
     * @param mixed ...$arguments
     *
     * @return string
     */
    public function dumpGet(...$arguments) : string
    {
        $noColor = $this->noColor;
        $this->noColor(true);

        $cloner = $this->newCloner();
        $dumper = $this->newCliDumper();

        $output = '';
        foreach ( $arguments as $argument ) {
            try {
                $var = $cloner->cloneVar($argument);

                $content = rtrim($dumper->dump($var, true));
                $delimiter = ( false !== mb_strpos($output, "\n") )
                    ? "\n"
                    : ' ';

                $output .= ( '' !== $output )
                    ? $delimiter . $content
                    : $content;
            }
            catch ( \ErrorException $e ) {
                throw new \RuntimeException($e->getMessage(), null, $e);
            }
        }

        $this->noColor($noColor);

        return $output;
    }


    /**
     * @param mixed ...$arguments
     *
     * @return array
     */
    public function gpause(...$arguments) : array
    {
        if (PHP_SAPI !== 'cli') {
            throw new \RuntimeException('Should be called in CLI mode');
        }

        if ($arguments) {
            VarDumper::getInstance()->dump(...$arguments);
        }

        echo '> Press ENTER to continue...' . PHP_EOL;
        $h = fopen('php://stdin', 'r');
        fgets($h);
        fclose($h);

        return $arguments;
    }

    /**
     * @param mixed ...$arguments
     *
     * @return array
     */
    public function gdump(...$arguments) : array
    {
        switch ( true ):
            case ( PHP_SAPI === 'cli'
                && ! $this->nonInteractive
            ):
                $this->gpause(...$arguments);
                break;

            default:
                if ($arguments) {
                    VarDumper::getInstance()->dump(...$arguments);
                }
                break;

        endswitch;

        return $arguments;
    }


    /**
     * @param string $key
     * @param int    $n
     * @param mixed  ...$arguments
     *
     * @return null|array
     */
    public function ggn(string $key, int $n, ...$arguments) : ?array
    {
        static $_n;

        $_n[ $key ] = $_n[ $key ] ?? $n;

        if (0 >= --$_n[ $key ]) {
            $this->gdump(...$arguments);

            throw new ShutdownException('Shutdown');
        }

        return $arguments;
    }

    /**
     * @param string    $key
     * @param int|int[] $limit
     * @param mixed     ...$arguments
     *
     * @return null|array
     */
    public function ggt(string $key, $limit, ...$arguments) : ?array
    {
        static $_limit;
        static $_offset;

        [ $limit, $offset ] = (array) $limit + [ 1, 0 ];

        $_limit[ $key ] = $_limit[ $key ] ?? $limit;
        $_offset[ $key ] = $_offset[ $key ] ?? $offset;

        if (0 < $_offset[ $key ]--) {
            return null;
        }

        if (0 < $_limit[ $key ]--) {
            $this->gdump(...$arguments);

            return $arguments;
        }

        throw new ShutdownException('Shutdown');
    }


    /**
     * @return VarDumper|static
     */
    public static function getInstance()
    {
        return static::$instance = static::$instance
            ?? new static();
    }


    /**
     * @param int $limit
     *
     * @return string
     */
    public static function key(int $limit = 1) : string
    {
        $limit = max(1, $limit);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);

        $step = end($trace);
        $file = $step[ 'file' ] ?? '';
        $line = $step[ 'line' ] ?? 0;

        $result = implode(':', [ $file, $line ]);

        return $result;
    }


    /**
     * bind
     * копирует тело функции и присваивает аргументы на их места в переданном порядке
     * bind('is_array', [], 1, 2) -> Closure of (function is_array($var = []))
     *
     * @param callable $func
     * @param mixed    ...$arguments
     *
     * @return \Closure
     */
    public static function funcBind(callable $func, ...$arguments) : \Closure
    {
        // string
        if (is_string($func)) {
            $bind = [];

            try {
                $rf = new \ReflectionFunction($func);
            }
            catch ( \ReflectionException $e ) {
                throw new \RuntimeException('Unable to reflect function', null, $e);
            }

            $cnt = $rf->getNumberOfRequiredParameters();

            while ( $cnt-- ) {
                $bind[] = null !== key($arguments)
                    ? current($arguments)
                    : null;

                next($arguments);
            }

            $func = \Closure::fromCallable($func);

        } else {
            $bind = $arguments;

        }

        $result = function (...$args) use ($func, $bind) {
            $bind = array_replace(
                $bind,
                array_slice($args, 0, count($bind))
            );

            return call_user_func_array($func, $bind);
        };

        return $result;
    }


    /**
     * @var callable[][]
     */
    protected static $casters = [];


    /**
     * @var static
     */
    protected static $instance;
}

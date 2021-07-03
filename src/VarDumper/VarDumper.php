<?php

namespace Gzhegow\VarDumper;

use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Gzhegow\VarDumper\Exceptions\Runtime\ShutdownException;
use Gzhegow\VarDumper\Exceptions\Logic\InvalidArgumentException;


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
     * @var array
     */
    protected $trace;

    /**
     * @var int[]
     */
    protected $indexes = [];
    /**
     * @var int[]
     */
    protected $limits = [];
    /**
     * @var int[]
     */
    protected $offsets = [];

    /**
     * @var bool[]
     */
    protected $groups;

    /**
     * @var callable[][]
     */
    protected $casters = [];


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->noColor = $_SERVER[ 'NO_COLOR' ] ?? false;
        $this->nonInteractive = false;

        $this->withTrace([]);
    }


    /**
     * @param array $trace
     *
     * @return static
     */
    public function withTrace(array $trace = [])
    {
        $trace += [
            'function' => '<function>',
            'line'     => '<line>',
            'file'     => '<file>',
            'class'    => '<class>',
            'object'   => null,
            'type'     => null,
            'args'     => [],
        ];

        $this->trace = $trace;

        return $this;
    }


    /**
     * @return VarCloner
     */
    public function newCloner() : VarCloner
    {
        end($this->casters);

        $casters = ( null !== ( $key = key($this->casters) ) )
            ? $this->casters[ $key ]
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
                $var = $cloner->cloneVar(implode(' :: ', [
                    $this->trace[ 'file' ],
                    $this->trace[ 'line' ],
                ]));
                $dumper->dump($var);

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
     * @return void
     */
    public function pause(...$arguments) : void
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
    }


    /**
     * @param mixed ...$arguments
     *
     * @return void
     */
    public function dumpPause(...$arguments) : void
    {
        switch ( true ):
            case ( PHP_SAPI === 'cli'
                && ! $this->nonInteractive
            ):
                $this->pause(...$arguments);
                break;

            default:
                if ($arguments) {
                    VarDumper::getInstance()->dump(...$arguments);
                }
                break;

        endswitch;
    }

    /**
     * @param mixed ...$arguments
     *
     * @return null|\Closure
     */
    public function dumpPauseGroup(...$arguments) : ?\Closure
    {
        if (null !== $this->groups) {
            return function (string $group = null) use ($arguments) {
                if (( null !== $group )
                    && isset($this->groups[ $group ])
                ) {
                    $this->dumpPause(...$arguments);
                }
            };
        }

        $this->dumpPause(...$arguments);

        return null;
    }


    /**
     * @param int|float|string $group
     *
     * @return static
     */
    public function ggroup($group)
    {
        if (! is_scalar($group)) {
            throw new InvalidArgumentException('Group should be scalar');
        }

        $this->groups[ $group ] = true;

        return $this;
    }

    /**
     * @return static
     */
    public function ggroupFlush()
    {
        $this->groups = null;

        return $this;
    }


    /**
     * @param array       $casters
     * @param null|string $key
     *
     * @return static
     */
    public function gcast(array $casters, string $key = null)
    {
        $list = [];

        foreach ( $casters as $type => $caster ) {
            if (! is_callable($caster)) {
                throw new \InvalidArgumentException('Each caster should be callable');
            }

            $list[ $type ] = $this->funcBind($caster);
        }

        if (null === $key) {
            $this->casters[] = $list;

        } else {
            end($this->casters);

            if ($key !== key($this->casters)) {
                unset($this->casters[ $key ]);

                $this->casters[ $key ] = $list;
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function gcastPop() : array
    {
        return array_pop($this->casters) ?: [];
    }


    /**
     * @param string $key
     * @param int    $idx
     * @param mixed  ...$arguments
     *
     * @return null|array
     */
    public function ggn(string $key, int $idx, ...$arguments) : array
    {
        $this->indexes[ $key ] = $this->indexes[ $key ] ?? $idx;

        if (0 >= --$this->indexes[ $key ]) {
            $this->dumpPauseGroup(...$arguments);

            throw new ShutdownException('Shutdown');
        }

        return $arguments;
    }

    /**
     * @param string    $key
     * @param int|int[] $limits
     * @param mixed     ...$arguments
     *
     * @return null|array
     */
    public function ggt(string $key, $limits, ...$arguments) : ?array
    {
        $limits = is_array($limits)
            ? $limits
            : [ $limits ];

        foreach ( $limits as $limit ) {
            if (! is_int($limit)) {
                throw new InvalidArgumentException('Each limit should be integer');
            }
        }

        [ $limit, $offset ] = $limits + [ 1, 0 ];

        $this->limits[ $key ] = $this->limits[ $key ] ?? $limit;
        $this->offsets[ $key ] = $this->offsets[ $key ] ?? $offset;

        if (0 < $this->offsets[ $key ]--) {
            return null;
        }

        if (0 < $this->limits[ $key ]--) {
            $this->dumpPauseGroup(...$arguments);

            return $arguments;
        }

        throw new ShutdownException('Shutdown');
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
    protected function funcBind(callable $func, ...$arguments) : \Closure
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
    public static function gkey(int $limit = 1) : string
    {
        $limit = max(1, $limit);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);

        $step = end($trace);
        $file = $step[ 'file' ] ?? '';
        $line = $step[ 'line' ] ?? 0;

        $result = implode(' :: ', [ $file, $line ]);

        return $result;
    }


    /**
     * @var static
     */
    protected static $instance;
}

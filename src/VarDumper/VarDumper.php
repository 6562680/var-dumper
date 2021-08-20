<?php

namespace Gzhegow\VarDumper;

use Symfony\Component\VarDumper\Cloner\Stub;
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
    protected $calls = [];
    /**
     * @var int[][]
     */
    protected $ranges = [];

    /**
     * @var callable[][]
     */
    protected $casters = [];

    /**
     * @var bool[]
     */
    protected $groups;


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
     * @return static
     */
    public function reset()
    {
        $this->calls = [];
        $this->ranges = [];

        $this->casters = [];

        $this->groups = null;

        return $this;
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
     * @return bool[]
     */
    public function getGroups() : array
    {
        return $this->groups;
    }


    /**
     * @return bool
     */
    public function hasGroups() : bool
    {
        return isset($this->groups);
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
    public function buff(...$arguments) : string
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
    public function pauseDump(...$arguments) : void
    {
        switch ( true ):
            case ( PHP_SAPI === 'cli'
                && ! $this->nonInteractive
            ):
                if ($arguments) {
                    VarDumper::getInstance()->dump(...$arguments);
                }

                $this->cliPause();
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
     * @return void
     */
    public function pauseDumpExit(...$arguments) : void
    {
        $this->pauseDump(...$arguments);

        throw new ShutdownException('Shutdown');
    }

    /**
     * @param string    $key
     * @param int|int[] $range
     * @param mixed     ...$arguments
     *
     * @return void
     */
    public function pauseDumpRange(string $key, $range, ...$arguments) : void
    {
        $range = is_array($range)
            ? $range
            : [ $range ];

        foreach ( $range as $r ) {
            if (! is_int($r)) {
                throw new InvalidArgumentException('Each range step should be integer');
            }
        }

        if (! isset($this->calls[ $key ])) {
            $this->calls[ $key ] = 0;
            $this->ranges[ $key ] = [ min($range), max($range) ];
        }

        $call = ++$this->calls[ $key ];

        [ $min, $max ] = $this->ranges[ $key ];

        if ($call < $min) {
            return;

        } elseif ($call <= $max) {
            $this->pauseDump(...$arguments);

            return;
        }

        throw new ShutdownException('Shutdown');
    }


    /**
     * @param null|string $group
     *
     * @return static
     */
    public function ggroup(string $group = null)
    {
        isset($group)
            ? ( $this->groups[ $group ] = true )
            : ( $this->groups = null );

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

            $list[ $type ] = function ($object, $array, Stub $stub, $isNested, $filter) use ($caster) {
                $func = $this->funcBind($caster);

                $result = $func($object, $array, $stub, $isNested, $filter)
                    ? $array
                    : null;

                return $result;
            };
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
     * @return void
     */
    public function cliPause() : void
    {
        if (PHP_SAPI !== 'cli') {
            throw new \RuntimeException('Should be called in CLI mode');
        }

        echo '> Press ENTER to continue...' . PHP_EOL;
        $h = fopen('php://stdin', 'r');
        fgets($h);
        fclose($h);
    }

    /**
     * @param callable $func
     *
     * @return \Closure
     */
    public function funcDecorateGroup(callable $func) : \Closure
    {
        return function (string $group = null, ...$arguments) use ($func) {
            if (! $this->hasGroups()) {
                return;
            }

            if (null === $group) {
                return;
            }

            if (! isset($this->groups[ $group ])) {
                return;
            }

            $func(...$arguments);
        };
    }

    /**
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
     * @param int $backtraceLimit
     *
     * @return string
     */
    public static function dumperKey(int $backtraceLimit = 1) : string
    {
        $backtraceLimit = max(1, $backtraceLimit);

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $backtraceLimit);

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

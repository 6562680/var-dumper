<?php

namespace Gzhegow\VarDumper\Tests\Services;

use PHPUnit\Framework\TestCase;
use Gzhegow\VarDumper\Tests\Services\BService;


/**
 * AService
 */
class AService
{
    /**
     * @var BService
     */
    protected $b;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->b = new BService();
    }

    /**
     * @return BService
     */
    public function getB() : BService
    {
        return $this->b;
    }
}

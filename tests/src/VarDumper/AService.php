<?php

namespace Gzhegow\VarDumper\Tests;

use PHPUnit\Framework\TestCase;


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

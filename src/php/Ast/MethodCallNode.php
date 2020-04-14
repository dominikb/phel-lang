<?php 

namespace Phel\Ast;

use Phel\Lang\Symbol;
use Phel\NodeEnvironment;

class MethodCallNode implements Node {

    /**
     * @var NodeEnvironment
     */
    protected $env;

    /**
     * @var Symbol
     */
    protected $fn;

    /**
     * @var Node[]
     */
    protected $args;

    public function __construct(NodeEnvironment $env, Symbol $fn, array $args)
    {
        $this->env = $env;
        $this->fn = $fn;
        $this->args = $args;
    }

    public function getFn() {
        return $this->fn;
    }

    public function getArgs() {
        return $this->args;
    }

    public function getEnv(): NodeEnvironment {
        return $this->env;
    }
}
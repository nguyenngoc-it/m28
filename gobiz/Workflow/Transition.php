<?php

namespace Gobiz\Workflow;

class Transition
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $from;

    /**
     * @var string
     */
    public $to;

    /**
     * Transition constructor
     *
     * @param string $name
     * @param string $from
     * @param string $to
     */
    public function __construct($name, $from, $to)
    {
        $this->name = $name;
        $this->from = $from;
        $this->to = $to;
    }
}
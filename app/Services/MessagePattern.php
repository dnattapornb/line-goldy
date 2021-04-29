<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MessagePattern
{
    /** @var array */
    private static $categories = ['POEM', 'BULL', 'LINE-COMMAND'];
    /** @var string */
    private $category = '';
    /** @var string */
    private $command = '';
    /** @var string */
    private $action = '';
    /** @var MessagePatternParameter[] */
    private $parameters = [];

    public function __construct() { }

    /**
     * @return string
     */
    public function getCategory():string
    {
        return $this->category;
    }

    /**
     * @param  string  $category
     */
    public function setCategory(string $category):void
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getCommand():string
    {
        return $this->command;
    }

    /**
     * @param  string  $command
     */
    public function setCommand(string $command):void
    {
        $this->command = $command;
    }

    /**
     * @return string
     */
    public function getAction():string
    {
        return $this->action;
    }

    /**
     * @param  string  $action
     */
    public function setAction(string $action):void
    {
        $this->action = $action;
    }

    /**
     * @return \App\Services\MessagePatternParameter[]
     */
    public function getParameters():array
    {
        return $this->parameters;
    }

    /**
     * @param  string                                 $key
     * @param  \App\Services\MessagePatternParameter  $parameter
     */
    public function addParameter(string $key, \App\Services\MessagePatternParameter $parameter):void
    {
        $this->parameters[$key] = $parameter;
    }

    public function toArray()
    {
        $toArray = [
            'category'   => $this->getCategory(),
            'command'    => $this->getCommand(),
            'action'     => $this->getAction(),
            'parameters' => [],
        ];
        if (sizeof($this->getParameters()) > 0) {
            foreach ($this->getParameters() as $parameter) {
                $toArray['parameters'][] = $parameter->toArray();
            }
        }

        return $toArray;
    }
}

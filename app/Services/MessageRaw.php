<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MessageRaw
{
    /** @var string */
    private $message = '';
    /** @var string */
    private $pattern = '';
    /** @var array */
    private $matches = [];

    public function __construct() { }

    /**
     * @return string
     */
    public function getMessage():string
    {
        return $this->message;
    }

    /**
     * @param  string  $message
     */
    public function setMessage(string $message):void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getPattern():string
    {
        return $this->pattern;
    }

    /**
     * @param  string  $pattern
     */
    public function setPattern(string $pattern):void
    {
        $this->pattern = $pattern;
    }

    /**
     * @return array
     */
    public function getMatches():array
    {
        return $this->matches;
    }

    /**
     * @param  array  $matches
     */
    public function setMatches(array $matches):void
    {
        $this->matches = $matches;
    }

    public function toArray()
    {
        $toArray = [
            'message' => $this->getMessage(),
            'pattern' => $this->getPattern(),
            'matches' => $this->getMatches(),
        ];

        return $toArray;
    }
}

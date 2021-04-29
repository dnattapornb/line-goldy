<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MessagePatternParameter
{
    /** @var string */
    private $key = '';
    /** @var string|array */
    private $value = '';

    public function __construct() { }

    /**
     * @return string
     */
    public function getKey():string
    {
        return $this->key;
    }

    /**
     * @param  string  $key
     */
    public function setKey(string $key):void
    {
        $this->key = $key;
    }

    /**
     * @return array|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param  string|array  $value
     */
    public function setValue($value):void
    {
        $this->value = $value;
    }

    public function toArray()
    {
        $toArray = [
            'key'   => $this->getKey(),
            'value' => $this->getValue(),
        ];

        return $toArray;
    }
}

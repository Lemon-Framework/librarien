<?php

namespace Lemon\Librarien;

use Exception;

class Config
{
    private array $data;

    public function __construct(string $file) 
    {
        $this->data = require $file;    
    }

    public function get(string $key): mixed
    {
        if (!isset($this->data[$key])) {
            throw new Exception('Key '.$key.' is missing in config.');
        }
        return $this->data[$key];
    }
}

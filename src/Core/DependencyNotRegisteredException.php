<?php

namespace Unity\Core;

use Exception;

class DependencyNotRegisteredException extends Exception {
    private $className;

    public function __construct($className, $code = 0, Exception $previous = null) {
        $message = "Dependency not registered: $className";
        parent::__construct($message, $code, $previous);
        $this->className = $className;
    }

    public function getClassName() {
        return $this->className;
    }
}
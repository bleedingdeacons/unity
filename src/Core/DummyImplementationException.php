<?php

namespace Unity\Core;

use Exception;

class DummyImplementationException extends Exception {
    private $className;

    public function __construct($className, $code = 0, Exception $previous = null) {
        $message = "Attempt to use the Dummy Implementation";
        parent::__construct($message, $code, $previous);
        $this->className = $className;
    }

    public function getClassName() {
        return $this->className;
    }
}
<?php

// Autoloader
spl_autoload_register(function ($class) {

    $namespaces = explode('\\', $class);

    if (array_shift($namespaces) !== 'OnePilot') {
        return false;
    }

    $path = _PS_MODULE_DIR_ . 'onepilot/classes/' . implode('/', $namespaces) . '.php';

    if (file_exists($path)) {
        require_once $path;

        return true;
    }
});


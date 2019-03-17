<?php namespace OnePilot\Middlewares;

use OnePilot\Response;

class Handler
{
    public static function register()
    {
        //Remove php error reporting
        error_reporting(0);
        ini_set('error_reporting', 0);

        // Catch PHP errors
        register_shutdown_function(['OnePilot\Middlewares\Handler', 'errorShutdown']);

        // Catch PHP exception
        set_exception_handler(['OnePilot\Middlewares\Handler', 'exceptionHandler']);
    }

    /**
     * Catch errors and return infos in a JSON object
     *
     */
    static public function errorShutdown()
    {
        $lastError = error_get_last();
        $catchedErrors = [E_ERROR, E_PARSE];

        if ($lastError == null || !in_array($lastError['type'], $catchedErrors)) {
            return true;
        }

        $error = new \stdClass();
        $error->status = 'error';
        $error->type = $lastError['type'];
        $error->message = $lastError['message'];
        $error->file = $lastError['file'];
        $error->line = $lastError['line'];

        Response::make($error, 500);
    }

    /**
     * Catch Joomla exceptions and return informations in a JSON object
     *
     * @param mixed <Exception|Error> $e
     */
    static public function exceptionHandler($e)
    {
        $error = new \stdClass();
        $error->status = 'error';
        $error->type = 'exception';

        if ($e instanceof \Exception || (class_exists('Error') && $e instanceof \Error)) {
            $error->message = $e->getMessage();
            $error->file = $e->getFile();
            $error->line = $e->getLine();
        } else {
            $error->message = 'unknown_error';
            $error->file = __FILE__;
            $error->line = __LINE__;
        }

        Response::make($error, $e->getCode() ?: 500);
    }
}

<?php namespace OnePilot\Middlewares;

use Exception;
use OnePilot\Exceptions\OnePilotException;
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
     * Catch exceptions
     *
     * @param Exception $exception
     */
    static public function exceptionHandler($exception)
    {
        $httpCode = ($exception->getCode() >= 400 && $exception->getCode() < 600) ? $exception->getCode() : 500;
        $content = [
            'status'  => $httpCode,
            'message' => $exception->getMessage(),
            'data'    => [],
            'type'    => 'exception',
            'exception'=> get_class($exception)
        ];
        if (!empty($previous = $exception->getPrevious())) {
            $content['data']['previous'] = $previous;
        }
        if ($exception instanceof OnePilotException && !empty($exceptionData = $exception->getData())) {
            $content['data'] = array_merge($content['data'], $exceptionData);
        }

        Response::make($content, $httpCode);
    }
}

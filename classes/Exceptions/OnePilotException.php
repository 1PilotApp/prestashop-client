<?php namespace OnePilot\Exceptions;

use Exception;

/**
 * Define a custom OnePilotException
 * needed for only catch our exceptions
 */
class OnePilotException extends Exception
{
    protected $data = [];

    /**
     * CmsPilotException constructor.
     *
     * @param string    $message  The Exception message to throw.
     * @param int       $code     The Exception code.
     * @param Exception $previous [optional] The previous throwable used for the exception chaining.
     * @param array     $data     [optional] indexed array of custom field to attach to the exception
     */
    public function __construct($message, $code = 0, Exception $previous = null, array $data = [])
    {
        parent::__construct($message, $code, $previous);

        if (!empty($data)) {
            $this->data = $data;
        }
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Print the exception
     *
     * @return string
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    /**
     * Returns the status code.
     *
     * @return int An HTTP response status code
     */
    public function getStatusCode()
    {
        return $this->code;
    }
}

<?php namespace OnePilot\Middlewares;

use OnePilot\Exceptions\OnePilotException;


class Authentication
{
    private $hash;
    private $stamp;
    private $private_key;

    public static function register()
    {
        return new static();
    }

    /**
     * Authentication class
     * @throws OnePilotException
     */
    public function __construct()
    {
        $this->hash = $_SERVER['HTTP_HASH'];
        $this->stamp = $_SERVER['HTTP_STAMP'];
        $this->private_key = \Configuration::get('ONE_PILOT_API_KEY');

        $this->checkAuthentication();
    }

    /**
     * Check verify_key, hash_mac and timestamp
     *
     * @return boolean
     * @throws OnePilotException
     */
    public function checkAuthentication()
    {
        if (!$this->hash) {
            throw new OnePilotException('no-verification-key', 403);
        }

        if (!$this->private_key) {
            throw new OnePilotException('no-private-key-configured', 403);
        }

        $hash = hash_hmac('sha256', $this->stamp, $this->private_key);

        if (!$this->checkKey($hash, $this->hash)) {
            throw new OnePilotException('bad-authentification', 403);
        }

        $this->validateTimestamp();

        return true;
    }

    /**
     * Validate timestamp. The meaning of this check is to enhance security by
     * making sure any token can only be used in a short period of time.
     *
     * @return void
     *
     * @throws OnePilotException
     */
    private function validateTimestamp()
    {
        if (\Configuration::get('ONE_PILOT_SKIP_TIMESTAMP')) {
            return;
        }

        if (($this->stamp > time() - 360) && ($this->stamp < time() + 360)) {
            return;
        }

        throw new OnePilotException('bad-timestamp', 403);
    }

    /**
     * @param string $knowString
     * @param string $providedKey
     *
     * @return bool
     */
    private function checkKey($knowString, $providedKey)
    {
        $status = 0;

        if (!is_string($knowString) || !is_string($providedKey)) {
            return false;
        }

        $length = strlen($knowString);

        if ($length !== strlen($providedKey)) {
            return false;
        }

        for ($i = 0; $i < $length; $i++) {
            $status |= ord($providedKey[$i]) ^ ord($knowString[$i]);
        }

        return $status === 0;
    }
}

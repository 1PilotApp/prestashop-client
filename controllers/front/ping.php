<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnePilot\Response;


class OnepilotPingModuleFrontController extends ModuleFrontController
{

    public function init()
    {

        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        parent::init();

        Response::make([

            'message' => 'pong'
        ]);
    }
}
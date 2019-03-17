<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnePilot\Exceptions\OnePilotException;
use OnePilot\Response;

class OnepilotMailtesterModuleFrontController extends ModuleFrontController
{


    /**
     * Return all client data separated into different array items
     *
     * @return array
     * @throws OnePilotException
     */
    public function init()
    {
        parent::init();

        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        $email = Tools::getValue('email');

        if (empty($email)) {
            throw new OnePilotException('Missing email parameter', 400);
        }

        $this->sendEmail($email);

        /*if (!$this->sendEmail($email)) {
            throw new OnePilotException('Error when sending email', 500);
        }*/

        Response::make([
            'success' => true,
            'message' => 'Sent'
        ]);

    }

    private function sendEmail($email)
    {
        $siteUrl = Tools::getHttpHost(true) . __PS_BASE_URI__;

        $message = <<<EOF
This email was automatically sent by the 1Pilot Client installed on $siteUrl.

Ground control to Major Tom
Ground control to Major Tom
Take your protein pills and put your helmet on

Ground control to Major Tom
(10, 9, 8, 7)
Commencing countdown, engines on
(6, 5, 4, 3)
Check ignition, and may God's love be with you
(2, 1, liftoff)

This is ground control to Major Tom,

You've really made the grade
And the papers want to know whose shirts you wear
Now it's time to leave the capsule if you dare

This is Major Tom to ground control
I'm stepping through the door
And I'm floating in the most of peculiar way
And the stars look very different today

For here am I sitting in a tin can
Far above the world
Planet Earth is blue, and there's nothing I can do

Though I'm past 100,000 miles
I'm feeling very still
And I think my spaceship knows which way to go
Tell my wife I love her very much, she knows

Ground control to Major Tom,
Your circuit's dead, there's something wrong
Can you hear me Major Tom?
Can you hear me Major Tom?
Can you hear me Major Tom?
Can you...

Here am I floating round my tin can
Far above the moon
Planet Earth is blue, and there's nothing I can do...

Ground control to Major Tom,
Your circuit's dead, there's something wrong
Can you hear me Major Tom?
Can you hear me Major Tom?
Can you hear me Major Tom?
Can you...

Space Oddity
David Bowie
EOF;

         Mail::Send(
            (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
            'contact', // email template file to be use
            'Test send by 1Pilot.io for ensure emails are properly sent', // email subject
            array(
                '{email}' => Configuration::get('PS_SHOP_EMAIL'),
                '{message}' => $message
            ),
            $email, // receiver email address
            '1Pilot', //receiver name
            Configuration::get('PS_SHOP_EMAIL'), //Sender email
            Configuration::get("PS_SHOP_NAME") // Sender name
        );


    }
}
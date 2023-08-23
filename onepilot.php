<?php
/**
 * Copyright (c) since 2018 1Pilot (https://1pilot.io)
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    1Pilot <support@1pilot.io>
 * @copyright 1Pilot.io
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once 'autoloader.php';

/**
 * @mixin ModuleCore
 */
class Onepilot extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'onepilot';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = '1Pilot';
        $this->need_instance = 1;

        // set to true if your module is compliant with bootstrap (PrestaShop 1.6)
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('1Pilot');
        $this->description = $this->l('1Pilot PrestaShop module');

        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        if (empty(Configuration::get('ONE_PILOT_API_KEY'))) {
            Configuration::updateValue('ONE_PILOT_API_KEY', $this->generateKey());
        }

        return parent::install();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        $output = '';
        if (((bool)Tools::isSubmit('submitOnepilotModule')) == true) {
            $this->postProcess();
            $output .= $this->displayConfirmation($this->l('Settings updated'));
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output .= $this->renderForm();

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitOnepilotModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages'    => $this->context->controller->getLanguages(),
            'id_language'  => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'col'    => 3,
                        'type'   => 'text',
                        'prefix' => '<i class="icon icon-key"></i>',
                        'name'   => 'ONE_PILOT_API_KEY',
                        'label'  => $this->l('1Pilot private key'),
                    ],
                    [
                        'col'     => 3,
                        'type'    => 'switch',
                        'is_bool' => true,
                        'desc'    => $this->l('Skip timestamp validation ?'),
                        'name'    => 'ONE_PILOT_SKIP_TIMESTAMP',
                        'label'   => $this->l('Skip timestamp'),
                        'values'  => [
                            [
                                'id'    => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id'    => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
                    ],

                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return [
            'ONE_PILOT_API_KEY'        => Configuration::get('ONE_PILOT_API_KEY'),
            'ONE_PILOT_SKIP_TIMESTAMP' => Configuration::get('ONE_PILOT_SKIP_TIMESTAMP', 0),
        ];
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $configs = $this->getConfigFormValues();

        foreach (array_keys($configs) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    private function generateKey()
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';

        for ($i = 0; $i < 20; $i++) {
            $randstring .= $chars[rand(0, strlen($chars) - 1)];
        }

        return md5($randstring);
    }
}

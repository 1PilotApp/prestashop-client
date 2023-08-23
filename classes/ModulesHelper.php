<?php

namespace OnePilot;

use Configuration;
use Context;
use Country;
use Module;
use Tools;

class ModulesHelper
{
    private $modules = [];

    /**
     * @return array|\stdClass[]
     */
    public function getList()
    {
        if (version_compare(_PS_VERSION_, '8', '<')) {
            return $this->legacyList();
        }

        $this->modules = Module::getModulesOnDisk();

        $this->fillFromXml($this->addonsRequest('native'));
        $this->fillFromXml($this->addonsRequest('must-have'));

        return $this->modules;
    }

    /**
     * @param $request
     * @return bool|string
     * @see ToolsCore::addonsRequest() - PrestaShop 1.7
     *
     */
    public static function addonsRequest($request)
    {
        $post_query_data = array(
            'version' => _PS_VERSION_,
            'iso_lang' => Tools::strtolower(Context::getContext()->language->iso_code),
            'iso_code' => Tools::strtolower(Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'))),
            'shop_url' => Tools::getShopDomain(),
            'mail' => Configuration::get('PS_SHOP_EMAIL'),
            'format' => 'xml',
        );

        if (isset($params['source'])) {
            $post_query_data['source'] = $params['source'];
        }

        $post_data = http_build_query($post_query_data);

        $end_point = 'api.addons.prestashop.com';

        switch ($request) {
            case 'native':
                $post_data .= '&method=listing&action=native';
                break;
            case 'must-have':
                $post_data .= '&method=listing&action=must-have';
                break;
            default:
                return false;
        }

        $context = stream_context_create(array(
            'http' => array(
                'method' => 'POST',
                'content' => $post_data,
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'timeout' => 5,
            ),
        ));

        if ($content = Tools::file_get_contents('https://' . $end_point, false, $context)) {
            return $content;
        }

        return false;
    }

    /**
     * @param string $content
     * @return void
     */
    public function fillFromXml($content)
    {
        $xml = @simplexml_load_string($content, null, LIBXML_NOCDATA);

        if (empty($xml) || empty($xml->module)) {
            return;
        }

        foreach ($xml->module as $modaddons) {
            foreach ($this->modules as $key => $module) {
                if (Tools::strtolower($module->name) == Tools::strtolower($modaddons->name) && !isset($module->available_on_addons)) {
                    if ($module->version != $modaddons->version && version_compare($module->version, $modaddons->version) === -1) {
                        $this->modules[$key]->version_addons = $modaddons->version;
                    }
                }
            }
        }
    }

    /**
     * @return \stdClass[]
     */
    private function legacyList()
    {
        $this->legacyRefreshModulesCache();

        return Module::getModulesOnDisk();
    }

    /**
     * @return void
     * @see AdminModulesControllerCore::ajaxProcessRefreshModuleList
     */
    private function legacyRefreshModulesCache()
    {
        if (defined('Module::CACHE_FILE_DEFAULT_COUNTRY_MODULES_LIST')) {
            file_put_contents(
                _PS_ROOT_DIR_ . Module::CACHE_FILE_DEFAULT_COUNTRY_MODULES_LIST,
                Tools::addonsRequest('native')
            );
        }

        if (defined('Module::CACHE_FILE_MUST_HAVE_MODULES_LIST')) {
            file_put_contents(
                _PS_ROOT_DIR_ . Module::CACHE_FILE_MUST_HAVE_MODULES_LIST,
                Tools::addonsRequest('must-have')
            );
        }
    }
}

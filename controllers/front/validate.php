<?php

use OnePilot\Errors;
use OnePilot\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OnepilotValidateModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        parent::init();

        Response::make([
            'core'    => ['version' => _PS_VERSION_, 'new_version' => null,],
            'servers' => $this->getServers(),
            'plugins' => $this->getExtensions(),
            'files'   => $this->getFilesProperties(),
            'errors'  => (new Errors())->overview(),
            'extra'   => $this->getExtras(),
        ]);
    }

    private function getExtensions()
    {
        $activeModules = [];

        $this->refreshModulesCache();
        $modules = Module::getModulesOnDisk();

        foreach ($modules as $module) {
            if (!$module->active) {
                continue;
            }

            $newVersion = isset($module->version_addons) ? (string)$module->version_addons[0] : null;

            $activeModules[] = [
                'version'     => $module->version,
                'new_version' => $newVersion,
                'name'        => $module->displayName,
                'code'        => $module->name,
                'type'        => 'module',
                'active'      => $module->active,
            ];
        }

        return $activeModules;
    }

    private function getServers()
    {
        $serverWeb = $_SERVER['SERVER_SOFTWARE'] ?: getenv('SERVER_SOFTWARE') ?: 'NOT_FOUND';

        return [
            'php'    => phpversion(),
            'server' => $serverWeb,
            'mysql'  => Db::getInstance()->executeS("SHOW VARIABLES LIKE 'version'")[0]['Value'],
        ];
    }

    private function getFilesProperties()
    {
        $filesProperties = [];

        //files to check
        $files = [
            'index.php',
            '.htaccess',
        ];

        foreach ($files as $file) {
            $absolutePath = _PS_ROOT_DIR_ . '/' . $file;

            if (file_exists($absolutePath)) {
                $fp = fopen($absolutePath, 'r');
                $fstat = fstat($fp);
                fclose($fp);
                $checksum = md5_file($absolutePath);
            } elseif ($file != _PS_ROOT_DIR_ . '/.htaccess') { //If not, we say that the file can't be found
                $checksum = $fstat['size'] = $fstat['mtime'] = 'NOT_FOUND';
            }

            $file = [
                'rootpath'         => $file,
                'size'             => $fstat['size'],
                'modificationtime' => $fstat['mtime'],
                'checksum'         => $checksum,
            ];

            $filesProperties[] = $file;
        }

        return $filesProperties;
    }

    private function getExtras()
    {
        $context = Context::getContext();

        return [
            'debug_mode'      => _PS_MODE_DEV_,
            'cms.activeTheme' => $context->shop->theme_name,
        ];
    }

    /**
     * @see AdminModulesControllerCore::ajaxProcessRefreshModuleList
     */
    private function refreshModulesCache()
    {
        file_put_contents(
            _PS_ROOT_DIR_ . Module::CACHE_FILE_DEFAULT_COUNTRY_MODULES_LIST,
            Tools::addonsRequest('native')
        );

        file_put_contents(
            _PS_ROOT_DIR_ . Module::CACHE_FILE_MUST_HAVE_MODULES_LIST,
            Tools::addonsRequest('must-have')
        );
    }
}

<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class OnepilotValidateModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        \OnePilot\Response::make([
            'core'    => _PS_VERSION_,
            'update'  => null,
            'plugins' => $this->getExtensions(),
            'servers' => $this->getServers(),
            'files'   => $this->getFilesProperties(),
            'errors'  => (new \OnePilot\Errors())->overview(),
            'extra'   => $this->getExtras(),
        ]);
    }

    private function getExtensions()
    {
        $activeModules = [];
        $modules = Module::getModulesOnDisk();

        foreach ($modules as $module) {
            if (!$module->active) {
                continue;
            }

            $new_version = null;
            if ($module->version < $module->database_version) {
                $new_version = $module->database_version;
            }

            $activeModules[] = [
                "version"     => $module->version,
                "new_version" => $new_version,
                "name"        => $module->displayName,
                "code"        => $module->name,
                "type"        => "plugin",
                "active"      => $module->active,
            ];

        }

        return $activeModules;
    }

    private function getServers()
    {
        $serverWeb = $_SERVER['SERVER_SOFTWARE'] ?: getenv('SERVER_SOFTWARE') ?: 'NOT_FOUND';

        return
            [
                'php'     => phpversion(),
                'server'  => $serverWeb,
                'mysql'   => Db::getInstance()->executeS("SHOW VARIABLES LIKE 'version'")[0]['Value'],
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
}

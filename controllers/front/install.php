<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

use OnePilot\Exceptions\OnePilotException;
use OnePilot\Response;

/**
 * @mixin ModuleFrontControllerCore
 */
class OnepilotInstallModuleFrontController extends ModuleFrontController
{
    /** @var string */
    private $archivePath;

    /** @var array */
    private $modules = [];

    /** @var array */
    private $installedModules = [];

    /** @var array */
    private $updatedModules = [];

    /**
     * @throws OnePilotException
     */
    public function init()
    {
        \OnePilot\Middlewares\Handler::register();
        \OnePilot\Middlewares\Authentication::register();

        parent::init();

        $this->download();

        $this->extract();

        $this->install();

        $this->writeResponse();
    }

    public function __destruct()
    {
        if (file_exists($this->archivePath)) {
            @unlink($this->archivePath);
        }
    }

    /**
     * @throws OnePilotException
     */
    private function download()
    {
        if (empty($url = Tools::getValue('url'))) {
            throw new OnePilotException('URL parameter is missing', 400);
        }

        if (!function_exists('curl_init')) {
            throw new OnePilotException('cURL PHP extension is required', 500);
        }

        $this->archivePath = tempnam(sys_get_temp_dir(), '1pilot-ext-');

        $stream = fopen($this->archivePath, 'w');

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);
        curl_setopt($curl, CURLOPT_FILE, $stream);

        if (defined('CURLOPT_FOLLOWLOCATION') && !ini_get('open_basedir')) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
        }

        curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        fclose($stream);

        if ($statusCode != 200) {
            throw new OnePilotException('Error when downloading module archive', 500, null, [
                'module_url'  => $url,
                'result_code' => $statusCode,
            ]);
        }
    }

    /**
     * @return array
     * @throws OnePilotException
     */
    private function extract()
    {
        $mime = mime_content_type($this->archivePath);

        switch ($mime) {
            case "application/zip":
                return $this->extractZipArchive($this->archivePath);
            default:
                throw new OnePilotException('Unsupported archive format "' . $mime . '"');
        }
    }

    private function install()
    {
        foreach ($this->modules as $moduleName) {
            $module = Module::getInstanceByName($moduleName);

            if (!Module::isInstalled($moduleName)) {
                $module->install();
                $this->installedModules[] = $module;
            } else {
                Module::initUpgradeModule($module);

                $module->runUpgradeModule();

                if ($moduleName == 'onepilot') {
                    $onepilotNewVersion = $this->getOnepilotNewVersion();
                    $module->version = $onepilotNewVersion ?: $module->version;
                }

                Module::upgradeModuleVersion($moduleName, $module->version);

                $this->updatedModules[] = $module;
            }

            if (!Tools::getValue('enable', true)) {
                continue;
            }

            if (Validate::isLoadedObject($module)) {
                $module->enable();
            }
        }
    }

    /**
     * @param $archive
     * @param $destination
     *
     * @return array
     * @throws OnePilotException
     */
    private function extractZipArchive($archive)
    {
        $tempFolder = _PS_MODULE_DIR_ . md5(time());

        if (!mkdir($tempFolder, 0777, true)) {
            throw new OnePilotException("Can't create temp directory: {$tempFolder}");
        }

        if (!Tools::ZipExtract($archive, $tempFolder)) {
            throw new OnePilotException("An error occured while extracting the module");
        }

        $zipFolders = scandir($tempFolder);

        if (!Tools::ZipExtract($archive, _PS_MODULE_DIR_)) {
            $this->recursiveDeleteOnDisk($tempFolder);

            throw new OnePilotException("An error occured while extracting the module");
        }

        $invalidModules = [];
        $validModules = [];

        //check if it's a real module
        foreach ($zipFolders as $folder) {
            if (in_array($folder, ['.', '..', '.svn', '.git', '__MACOSX'])) {
                continue;
            }

            if (Module::getInstanceByName($folder)) {
                $validModules[] = $folder;
            } else {
                $invalidModules[] = $folder;
                $this->recursiveDeleteOnDisk(_PS_MODULE_DIR_ . $folder);
            }
        }

        if (!empty($invalidModules)) {
            throw new OnePilotException('The following module(s) "'
                . implode(', ', $invalidModules)
                . '" present in the archive are not valid');
        }

        $this->recursiveDeleteOnDisk($tempFolder);

        return $this->modules = $validModules;
    }

    /**
     * @param $dir
     */
    private function recursiveDeleteOnDisk($dir)
    {
        if (strpos(realpath($dir), realpath(_PS_MODULE_DIR_)) === false) {
            return;
        }

        if (is_dir($dir)) {
            $objects = scandir($dir, SCANDIR_SORT_NONE);

            foreach ($objects as $object) {
                if (in_array($object, ['.', '..'])) {
                    continue;
                }

                if (filetype($dir . '/' . $object) == 'dir') {
                    $this->recursiveDeleteOnDisk($dir . '/' . $object);
                } else {
                    unlink($dir . '/' . $object);
                }
            }

            reset($objects);
            rmdir($dir);
        }
    }

    private function writeResponse()
    {
        $plugins = [];

        foreach ($this->installedModules as $module) {
            $plugins[] = [
                'name'    => $module->displayName,
                'code'    => $module->name,
                'version' => $module->version,
                'action'  => 'installed',
            ];
        }

        foreach ($this->updatedModules as $module) {
            $plugins[] = [
                'name'    => $module->displayName,
                'code'    => $module->name,
                'version' => $module->version,
                'action'  => 'updated',
            ];
        }

        Response::make([
            'plugins' => $plugins,
        ]);
    }

    /**
     * @return string|null
     */
    private function getOnepilotNewVersion()
    {
        $contents = file_get_contents(_PS_MODULE_DIR_ . 'onepilot/onepilot.php');

        preg_match('#\$this->version\s*=\s*[\'"](.*)[\'"];#', $contents, $matches);

        if (empty($matches) || empty($matches[1])) {
            return null;
        }

        return $matches[1];
    }
}

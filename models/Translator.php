<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator\models;

use gplcart\core\Cache,
    gplcart\core\Config,
    gplcart\core\Hook;
use gplcart\core\helpers\Zip as ZipHelper;
use gplcart\core\models\Language as LanguageModel,
    gplcart\core\models\File as FileModel;

/**
 * Manages basic behaviors and data related to Translator module
 */
class Translator
{

    /**
     * Hook class instance
     * @var \gplcart\core\Hook $hook
     */
    protected $hook;

    /**
     * Config class instance
     * @var \gplcart\core\Config $config
     */
    protected $config;

    /**
     * Cache class instance
     * @var \gplcart\core\Cache $cache
     */
    protected $cache;

    /**
     * File model class instance
     * @var \gplcart\core\models\File $file
     */
    protected $file;

    /**
     * Zip helper class instance
     * @var \gplcart\core\helpers\Zip $zip
     */
    protected $zip;

    /**
     * Language model class instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * @param Hook $hook
     * @param Config $config
     * @param Cache $cache
     * @param ZipHelper $zip
     * @param FileModel $file
     * @param LanguageModel $language
     */
    public function __construct(Hook $hook, Config $config, Cache $cache, ZipHelper $zip,
            FileModel $file, LanguageModel $language)
    {
        $this->hook = $hook;
        $this->config = $config;

        $this->zip = $zip;
        $this->file = $file;
        $this->cache = $cache;
        $this->language = $language;
    }

    /**
     * Returns an array of information about the translation file
     * @param string $file
     * @return array
     */
    public function getFileInfo($file)
    {
        $lines = array();

        if (is_file($file)) {
            $lines = $this->language->parseCsv($file);
        }

        $result = array(
            'progress' => 0,
            'translated' => 0,
            'total' => count($lines)
        );

        if (empty($result['total'])) {
            return $result;
        }

        $result['translated'] = $this->countTranslated($lines);
        $result['progress'] = round(($result['translated'] / $result['total']) * 100);
        return $result;
    }

    /**
     * Copy a translation file
     * @param string $source
     * @param string $destination
     * @return boolean
     */
    public function copy($source, $destination)
    {
        $result = null;
        $this->hook->attach('module.translator.copy.before', $source, $destination, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $result = false;
        if ($this->prepareDirectory($destination)) {
            $result = copy($source, $destination);
        }

        $this->hook->attach('module.translator.copy.after', $source, $destination, $result, $this);
        return (bool) $result;
    }

    /**
     * Deletes a translation file
     * @param string $file
     * @param string $langcode
     * @return boolean
     */
    public function delete($file, $langcode)
    {
        $result = null;
        $this->hook->attach('module.translator.delete.before', $file, $langcode, $result, $this);

        if (isset($result)) {
            return $result;
        }

        if (!$this->canDelete($file, $langcode)) {
            return false;
        }

        $result = unlink($file);
        $this->hook->attach('module.translator.delete.after', $file, $langcode, $result, $this);
        return $result;
    }

    /**
     * Whether the translation file can be deleted
     * @param string $file
     * @param string $langcode
     * @return bool
     */
    public function canDelete($file, $langcode)
    {
        return $this->isTranslationFile($file, $langcode);
    }

    /**
     * Whether the file is a translation file
     * @param string $file
     * @param string $langcode
     * @return bool
     */
    public function isTranslationFile($file, $langcode)
    {
        return is_file($file)//
                && pathinfo($file, PATHINFO_EXTENSION) === 'csv'//
                && (strpos($file, $this->language->getDirectory($langcode)) === 0//
                || $this->getModuleIdFromPath($file));
    }

    /**
     * Returns a module ID from the translation file path
     * @param string $file
     * @return string
     */
    public function getModuleIdFromPath($file)
    {
        $module_id = basename(dirname(dirname($file)));
        $module = $this->config->getModule($module_id);

        if (!empty($module) && strpos($file, $this->language->getModuleDirectory($module_id)) === 0) {
            return $module_id;
        }

        return '';
    }

    /**
     * Returns an array of scanned and prepared translations
     * @param string|null $langcode
     * @return array
     */
    public function getImportList($langcode = null)
    {
        $key = "module.translator.import.$langcode";
        $list = $this->cache->get($key);

        if (empty($list)) {
            $file = $this->getImportFile();
            $scanned = $this->scanImportFile($file);
            $list = $this->buildImportList($scanned, $file, $langcode);
            $this->config->set('module_translator_saved', GC_TIME);
            $this->cache->set($key, $list);
        }

        $this->hook->attach('module.translator.import.list', $langcode, $list, $this);
        return $list;
    }

    /**
     * Delete both cache and ZIP file
     * @return boolean
     */
    public function clearImport()
    {
        $this->cache->clear('module.translator.import', array('pattern' => '*'));
        $file = $this->getImportFilePath();
        return is_file($file) && unlink($file);
    }

    /**
     * Copy translation from the source ZIP file
     * @param string $module_id
     * @param string $file
     * @param string $langcode
     * @return boolean
     */
    public function importContent($module_id, $file, $langcode)
    {
        $result = null;
        $this->hook->attach('module.translator.copy.before', $module_id, $file, $langcode, $result);

        if (isset($result)) {
            return $result;
        }

        $content = $this->readZip($module_id, $file, $langcode);

        if (empty($content)) {
            return false;
        }

        if ($module_id === 'core') {
            $module_id = '';
        }

        $destination = $this->language->getFile($langcode, $module_id);

        $result = false;
        if ($this->prepareDirectory($destination)) {
            $result = file_put_contents($destination, $content) !== false;
        }

        $this->hook->attach('module.translator.copy.after', $module_id, $file, $langcode, $destination, $result);
        return $result;
    }

    /**
     * Ensure that directory exists and contains no the same file
     * @param string $file
     * @return boolean
     */
    protected function prepareDirectory($file)
    {
        $directory = dirname($file);
        if (!file_exists($directory) && !mkdir($directory, 0775, true)) {
            return false;
        }

        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    /**
     * Returns an array of scanned translations
     * @param string|bool $file
     * @return array
     */
    public function scanImportFile($file)
    {
        if (empty($file)) {
            return array();
        }

        try {
            $items = $this->zip->set($file)->getList();
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage());
            return array();
        }

        // Convert to nested array
        $nested = array();
        foreach ($items as $item) {
            $parents = explode('/', trim($item, '/'));
            gplcart_array_set($nested, $parents, $item);
        }

        return $nested;
    }

    /**
     * Build translation data
     * @param array $items
     * @param string $file
     * @param null|string $langcode
     * @return array
     */
    public function buildImportList(array $items, $file, $langcode)
    {
        $list = array();
        $version = gplcart_version(true);

        if (!empty($items["$version.x"])) {
            $modules = $this->config->getModules();
            foreach ($items["$version.x"] as $module_id => $translations) {
                if ($module_id === 'core' || isset($modules[$module_id])) {
                    $list[$module_id] = $this->prepareImportTranslations($translations, $file, $langcode);
                }
            }
        }

        ksort($list);

        // Put core translations on the top
        if (isset($list['core'])) {
            $core = $list['core'];
            unset($list['core']);
            $list = array_merge(array('core' => $core), $list);
        }

        return $list;
    }

    /**
     * Prepare an array of translations
     * @param array $data
     * @param string $file
     * @param string $langcode
     * @return array
     */
    protected function prepareImportTranslations(array $data, $file, $langcode)
    {
        $languages = $this->language->getList();

        $prepared = array();
        foreach ($data as $filename => $path) {

            $pathinfo = pathinfo($filename);
            if (empty($pathinfo['extension']) || $pathinfo['extension'] !== 'csv') {
                continue;
            }

            list($lang, $version) = array_pad(explode('.', $pathinfo['filename'], 2), 2, '');

            if (!empty($langcode) && $langcode !== $lang) {
                continue;
            }

            if (empty($languages[$lang])) {
                continue;
            }

            $content = $this->language->parseCsv("zip://$file#$path");
            $total = count($content);
            $translated = $this->countTranslated($content);

            $prepared[$lang][$filename] = array(
                'total' => $total,
                'file' => $filename,
                'version' => $version,
                'content' => $content,
                'translated' => $translated,
                'progress' => round(($translated / $total) * 100)
            );

            uasort($prepared[$lang], function ($a, $b) {
                return version_compare($a['version'], $b['version']);
            });
        }

        return $prepared;
    }

    /**
     * Read CSV from ZIP file
     * @param string $module_id
     * @param string $file
     * @param string $langcode
     * @return string
     */
    public function readZip($module_id, $file, $langcode)
    {
        $list = $this->getImportList();

        $content = '';
        if (!empty($list[$module_id][$langcode][$file]['content'])) {
            // Use php://temp stream?
            $data = stream_get_meta_data(tmpfile());
            foreach ($list[$module_id][$langcode][$file]['content'] as $line) {
                gplcart_file_csv($data['uri'], $line);
            }

            $content = file_get_contents($data['uri']);
            unlink($data['uri']);
        }

        return $content;
    }

    /**
     * Returns a total number of translated strings
     * @param array $lines
     * @return integer
     */
    protected function countTranslated(array $lines)
    {
        $count = 0;
        foreach ($lines as $line) {
            if (isset($line[1]) && $line[1] !== '') {
                $count ++;
            }
        }

        return $count;
    }

    /**
     * Downloads a remote ZIP file
     * @param string $destination
     * @return boolean
     */
    public function downloadImportFile($destination)
    {
        try {
            $result = $this->file->download($this->getImportDownloadUrl(), 'zip', $destination);
            if ($result !== true) {
                trigger_error($result);
                return false;
            }
        } catch (\Exception $ex) {
            trigger_error($ex->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Returns URL of source ZIP file
     * @return string
     */
    public function getImportDownloadUrl()
    {
        return 'https://crowdin.com/download/project/gplcart.zip';
    }

    /**
     * Returns the path of a downloaded ZIP file
     * @return bool|string
     */
    public function getImportFile()
    {
        $file = $this->getImportFilePath();

        if (is_file($file)) {
            return $file;
        }

        return $this->downloadImportFile($file) ? $file : false;
    }

    /**
     * Returns the absolute path of downloaded ZIP file
     * @return string|bool
     */
    public function getImportFilePath()
    {
        return gplcart_file_private_temp('translator-import.zip');
    }

}

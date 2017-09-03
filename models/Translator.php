<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator\models;

use gplcart\core\Model as CoreModel;
use gplcart\core\models\Language as LanguageModel;

/**
 * Manages basic behaviors and data related to Translator module
 */
class Translator extends CoreModel
{

    /**
     * Language model class instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * @param LanguageModel $language
     */
    public function __construct(LanguageModel $language)
    {
        parent::__construct();

        $this->language = $language;
    }

    /**
     * Returns an array of information about the translation file
     * @param string $file
     * @return array
     */
    public function getFileInfo($file)
    {
        $lines = $this->get($file);

        $result = array(
            'progress' => 0,
            'translated' => 0,
            'total' => count($lines)
        );

        if (empty($lines)) {
            return $result;
        }

        foreach ($lines as $line) {
            if (isset($line[1]) && $line[1] !== '') {
                $result['translated'] ++;
            }
        }

        $result['progress'] = round(($result['translated'] / $result['total']) * 100);
        return $result;
    }

    /**
     * Returns an array of strings from the translation file
     * @param string $file
     * @return array
     */
    public function get($file)
    {
        return $this->language->parseCsv($file);
    }

    /**
     * Updates a translation file
     * @param string $file
     * @param string $langcode
     * @param array $lines
     * @return boolean
     */
    public function update($file, $langcode, array $lines)
    {
        $result = null;
        $this->hook->attach('module.translator.update.before', $file, $langcode, $lines, $result, $this);

        if (isset($result)) {
            return $result;
        }

        file_put_contents($file, '');

        foreach ($lines as $line) {
            gplcart_file_csv($file, $line);
        }

        $result = true;
        $this->hook->attach('module.translator.update.after', $file, $langcode, $lines, $result, $this);
        return (bool) $result;
    }

    /**
     * Copy a translation file
     * @param string $source
     * @param string $destination
     * @param string $langcode
     * @return boolean
     */
    public function copy($source, $destination, $langcode)
    {
        $result = null;
        $this->hook->attach('module.translator.copy.before', $source, $destination, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $directory = dirname($destination);
        if (!file_exists($directory) && !mkdir($directory, 0775, true)) {
            return false;
        }

        $result = copy($source, $destination);
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
     * Whether a translation file can be deleted
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
                || $this->getModuleIdFromFile($file));
    }

    /**
     * Returns a module ID from a translation file path
     * @param string $file
     * @return string
     */
    public function getModuleIdFromFile($file)
    {
        $module_id = basename(dirname(dirname($file)));
        $module = $this->config->getModule($module_id);

        if (!empty($module) && strpos($file, $this->language->getDirectoryModule($module_id)) === 0) {
            return $module_id;
        }

        return '';
    }

}

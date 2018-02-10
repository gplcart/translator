<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator\models;

use DOMDocument;
use DOMXPath;
use Exception;
use gplcart\core\Hook;
use gplcart\core\models\Translation as TranslationModel;
use gplcart\core\Module;
use RuntimeException;

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
     * Module class instance
     * @var \gplcart\core\Module $module
     */
    protected $module;

    /**
     * Translation UI model class instance
     * @var \gplcart\core\models\Translation $translation
     */
    protected $translation;

    /**
     * @param Hook $hook
     * @param Module $module
     * @param TranslationModel $translation
     */
    public function __construct(Hook $hook, Module $module, TranslationModel $translation)
    {
        $this->hook = $hook;
        $this->module = $module;
        $this->translation = $translation;
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
            $lines = $this->translation->parseCsv($file);
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

        try {
            $this->prepareDirectory($destination);
            $result = copy($source, $destination);
        } catch (Exception $ex) {
            $result = false;
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
        return is_file($file)
            && pathinfo($file, PATHINFO_EXTENSION) === 'csv'
            && (strpos($file, $this->translation->getDirectory($langcode)) === 0 || $this->getModuleIdFromPath($file));
    }

    /**
     * Returns a module ID from the translation file path
     * @param string $file
     * @return string
     */
    public function getModuleIdFromPath($file)
    {
        $module_id = basename(dirname(dirname($file)));
        $module = $this->module->get($module_id);

        if (!empty($module) && strpos($file, $this->translation->getModuleDirectory($module_id)) === 0) {
            return $module_id;
        }

        return '';
    }

    /**
     * Ensure that directory exists and contains no the same file
     * @param string $file
     * @throws RuntimeException
     */
    protected function prepareDirectory($file)
    {
        $directory = dirname($file);

        if (!file_exists($directory) && !mkdir($directory, 0775, true)) {
            throw new RuntimeException('Failed to create directory');
        }

        if (file_exists($file) && !unlink($file)) {
            throw new RuntimeException('Failed to unlink the existing file');
        }
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
                $count++;
            }
        }

        return $count;
    }

    /**
     * Fix html strings in the translation file
     * @param string $file
     * @param array $fixed_strings
     * @return array
     */
    public function getFixedStrings($file, array &$fixed_strings = array())
    {
        $lines = $this->translation->parseCsv($file);

        foreach ($lines as $num => $line) {
            unset($line[0]);
            foreach ($line as $col => $string) {
                $fixed = $this->fixHtmlString($string);
                if ($fixed !== false) {
                    $lines[$num][$col] = $fixed;
                    $fixed_strings[] = $string;
                }
            }
        }

        return $lines;
    }

    /**
     * Tries to fix invalid HTML
     * @param string $string
     * @return boolean|string
     */
    public function fixHtmlString($string)
    {
        if (strpos($string, '>') === false && strpos($string, '<') === false) {
            return false;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $xml = mb_convert_encoding("<root>$string</root>", 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($xml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//*[not(node())]') as $node) {
            $node->parentNode->removeChild($node);
        }

        $fixed = substr($dom->saveHTML(), 6, -8);
        return html_entity_decode($fixed, ENT_QUOTES, 'UTF-8');
    }

}

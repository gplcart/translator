<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator\controllers;

use gplcart\core\controllers\backend\Controller as BackendController;
use gplcart\core\models\FileTransfer as FileTransferModel;
use gplcart\modules\translator\models\Translator as TranslatorModuleModel;

/**
 * Handles incoming requests and outputs data related to Translator module
 */
class Translator extends BackendController
{
    /**
     * Translator model class instance
     * @var \gplcart\modules\translator\models\Translator $translator
     */
    protected $translator;

    /**
     * File transfer model class instance
     * @var \gplcart\core\models\FileTransfer $file_transfer
     */
    protected $file_transfer;

    /**
     * An array of translation strings
     * @var array
     */
    protected $data_content;

    /**
     * The current language
     * @var array
     */
    protected $data_language;

    /**
     * @param FileTransferModel $file_transfer
     * @param TranslatorModuleModel $translator
     */
    public function __construct(FileTransferModel $file_transfer, TranslatorModuleModel $translator)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->file_transfer = $file_transfer;
    }

    /**
     * Page callback
     * Displays the compiled files overview page
     * @param $langcode
     */
    public function compiledFilesTranslator($langcode)
    {
        $this->filesTranslator($langcode, 'compiled');
    }

    /**
     * Page callback
     * Displays the file overview page
     * @param string $langcode
     * @param string $tab
     */
    public function filesTranslator($langcode = null, $tab = '')
    {
        $this->setLanguageTranslator($langcode);
        $this->downloadFileTranslator();
        $this->actionFilesTranslator();
        $this->setTitleFilesTranslator();
        $this->setBreadcrumbFilesTranslator();

        $this->setData('tab', $tab);
        $this->setData('language', $this->data_language);
        $this->setData('files', $this->getFilesTranslator($tab));
        $this->setData('languages', $this->getLanguagesTranslator());

        $this->outputFilesTranslator();
    }

    /**
     * Returns a sorted array of languages
     * @return array
     */
    protected function getLanguagesTranslator()
    {
        $list = $this->language->getList();
        gplcart_array_sort($list, 'name');
        return $list;
    }

    /**
     * Download a translation file
     */
    protected function downloadFileTranslator()
    {
        $hash = $this->getQuery('download');

        if (!empty($hash) && $this->access('module_translator_download')) {

            $info = $this->parseFileHash($hash);

            if (!empty($info)) {
                $this->download($info[0], $info[1], array('text' => !empty($info[2])));
            }
        }
    }

    /**
     * Returns an array of file data
     * @param string $hash
     * @return array
     */
    protected function parseFileHash($hash)
    {
        $parts = $this->parseHashTranslator($hash);

        if (empty($parts)) {
            return array();
        }

        list($module_id, $file) = $parts;

        if (gplcart_path_is_absolute($file)) {
            return array($file, "$module_id-" . basename($file));
        }

        return array();
    }

    /**
     * Parses a hash string containing the module ID and translation file
     * @param string $hash
     * @return array
     */
    protected function parseHashTranslator($hash)
    {
        $file = gplcart_path_absolute(gplcart_string_decode($hash));

        if (!$this->translator->isTranslationFile($file, $this->data_language['code'])) {
            return array();
        }

        $module_id = $this->translator->getModuleIdFromPath($file);

        if (empty($module_id)) {
            $module_id = 'core';
        }

        if ($module_id !== 'core' && !$this->module->get($module_id)) {
            return array();
        }

        return array($module_id, $file);
    }

    /**
     * Applies an action to the selected translation files
     */
    protected function actionFilesTranslator()
    {
        list($selected, $action) = $this->getPostedAction();

        $deleted = 0;

        foreach ($selected as $hash) {
            if ($action === 'delete' && $this->access('module_translator_delete')) {
                $deleted += (int) $this->deleteFileTranslator($hash);
            }
        }

        if ($deleted > 0) {
            $this->setMessage($this->text('Deleted %num item(s)', array('%num' => $deleted)), 'success');
        }
    }

    /**
     * Deletes a translation file
     * @param string $id
     * @return boolean
     */
    protected function deleteFileTranslator($id)
    {
        $info = $this->parseFileHash($id);

        if (isset($info[0])) {
            return $this->translator->delete($info[0], $this->data_language['code']);
        }

        return false;
    }

    /**
     * Returns an array of files for the language
     * @param string $tab
     * @return array
     */
    protected function getFilesTranslator($tab)
    {
        if ($tab === 'compiled') {
            return $this->getCompiledFilesTranslator();
        }

        return $this->getOriginalFilesTranslator();
    }

    /**
     * Sets titles on the file overview page
     */
    protected function setTitleFilesTranslator()
    {
        $this->setTitle($this->text('Translations for %name', array('%name' => $this->data_language['name'])));
    }

    /**
     * Sets breadcrumbs on the files overview page
     */
    protected function setBreadcrumbFilesTranslator()
    {
        $breadcrumb = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Page callback
     * Displays the upload translation page
     * @param string $langcode
     */
    public function uploadTranslator($langcode)
    {
        $this->controlAccessUploadTranslator();

        $this->setLanguageTranslator($langcode);
        $this->setTitleUploadTranslator();
        $this->setBreadcrumbUploadTranslator();

        $this->setData('language', $this->data_language);
        $this->setData('modules', $this->getModulesTranslator());
        $this->setData('languages', $this->getLanguagesTranslator());

        $this->submitUploadTranslator();
        $this->outputUploadTranslator();
    }

    /**
     * Returns an array of sorted modules
     * @return array
     */
    protected function getModulesTranslator()
    {
        $modules = $this->module->getList();
        gplcart_array_sort($modules, 'name');
        return $modules;
    }

    /**
     * Controls permissions to upload a translation file
     */
    protected function controlAccessUploadTranslator()
    {
        $this->controlAccess('file_upload');
        $this->controlAccess('module_translator_upload');
    }

    /**
     * Sets titles on the upload translation page
     */
    protected function setTitleUploadTranslator()
    {
        $this->setTitle($this->text('Upload translation for %name', array('%name' => $this->data_language['name'])));
    }

    /**
     * Sets breadcrumbs on the upload translation page
     */
    protected function setBreadcrumbUploadTranslator()
    {
        $breadcrumb = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Handles submission of translation file
     */
    protected function submitUploadTranslator()
    {
        if ($this->isPosted('save') && $this->validateUploadTranslator()) {
            $this->normalizeHtml();
            $this->copyFileTranslator();
        }
    }

    /**
     * Validates a uploaded translation file
     * @return boolean
     */
    protected function validateUploadTranslator()
    {
        $this->setSubmitted('translation');
        $this->validateUploadScopeTranslator();
        $this->validateUploadFileTranslator();

        return !$this->hasErrors();
    }

    /**
     * Validates scope of uploaded file
     * @return boolean
     */
    protected function validateUploadScopeTranslator()
    {
        $scope = $this->getSubmitted('scope');

        if (!empty($scope) && !$this->module->get($scope)) {
            $this->setError('scope', $this->text('@field has invalid value', array('@field' => $scope)));
            return false;
        }

        $this->setSubmitted('destination', $this->translation->getFile($this->data_language['code'], $scope));
        return true;
    }

    /**
     * Validates uploaded translation file
     * @return boolean|null
     */
    protected function validateUploadFileTranslator()
    {
        if ($this->isError()) {
            return null;
        }

        $file = $this->request->file('file');

        if (empty($file)) {
            $this->setError('file', $this->text('File is required'));
            return false;
        }

        $result = $this->file_transfer->upload($file, 'csv');

        if ($result !== true) {
            $this->setError('file', $result);
            return false;
        }

        $this->setSubmitted('file', $this->file_transfer->getTransferred());
        return true;
    }

    /**
     * Check and try to fix malformed HTML in the uploaded file
     */
    protected function normalizeHtml()
    {
        if ($this->isSubmitted('fix')) {

            $fixed = array();
            $file = $this->getSubmitted('file');
            $lines = $this->translator->getFixedStrings($file, $fixed);

            file_put_contents($file, ''); // Truncate the uploaded file

            foreach ($lines as $line) {
                gplcart_file_csv($file, $line);
            }

            if (!empty($fixed)) {
                $this->setMessage($this->text('Fixed @num rows', array('@num' => count($fixed))), 'warning', true);
            }
        }
    }

    /**
     * Copy a uploaded translation
     */
    protected function copyFileTranslator()
    {
        $this->controlAccessUploadTranslator();

        $source = $this->getSubmitted('file');
        $destination = $this->getSubmitted('destination');

        if (!$this->translator->copy($source, $destination)) {
            $this->redirect('', $this->text('Translation has not been saved'), 'warning');
        }

        $message = $this->text('Translation has been saved. Now you can <a href="@url">refresh language</a>', array(
            '@url' => $this->url('admin/settings/language')));

        $this->redirect('', $message, 'success');
    }

    /**
     * Render and output the upload translation page
     */
    protected function outputUploadTranslator()
    {
        $this->output('translator|upload');
    }

    /**
     * Render and output the file overview page
     */
    protected function outputFilesTranslator()
    {
        $this->output('translator|files');
    }

    /**
     * Returns an array of primary translations
     * @return array
     */
    protected function getOriginalFilesTranslator()
    {
        $files = array($this->translation->getFile($this->data_language['code']));

        foreach (array_keys($this->module->getList()) as $module_id) {
            $files[$module_id] = $this->translation->getFile($this->data_language['code'], $module_id);
        }

        foreach ($files as $id => $file) {
            if (is_file($file)) {
                $files[$id] = $this->buildFileInfoTranslator($file);
            } else {
                unset($files[$id]);
            }
        }

        return $files;
    }

    /**
     * Returns an array of compiled translation files
     * @return array
     */
    protected function getCompiledFilesTranslator()
    {
        $directory = $this->translation->getCompiledDirectory($this->data_language['code']);

        $files = array();

        if (is_dir($directory)) {
            foreach (gplcart_file_scan($directory, array('csv')) as $file) {
                $files[] = $this->buildFileInfoTranslator($file);
            }
        }

        return $files;
    }

    /**
     * Build translation file info
     * @param string $file
     * @return array
     */
    protected function buildFileInfoTranslator($file)
    {
        $path = gplcart_path_relative($file);
        $context = str_replace(array('-', '_'), array('/', '/*/'), pathinfo(basename($path), PATHINFO_FILENAME));

        $langcode = $this->data_language['code'];
        $js_file = $this->translation->getContextJsFile($langcode);
        $common_file = $this->translation->getCommonFile($langcode);

        if (substr($js_file, -strlen($path)) === $path) {
            $context = $this->text('No context');
        }

        if (substr($common_file, -strlen($path)) === $path) {
            $context = $this->text('No context');
        }

        return array(
            'path' => $path,
            'modified' => filemtime($file),
            'hash' => gplcart_string_encode($path),
            'filesize' => gplcart_file_size(filesize($file)),
            'context' => preg_replace('/\*\/$/', '*', $context),
            'progress' => $this->translator->getFileInfo($file)
        );
    }

    /**
     * Sets the current language
     * @param string $langcode
     */
    protected function setLanguageTranslator($langcode)
    {
        if (!isset($langcode)) {
            $langcode = $this->language->getDefault();
        }

        $this->data_language = $this->language->get($langcode);

        if (empty($this->data_language)) {
            $this->outputHttpStatus(404);
        }
    }

    /**
     * Page callback
     * Displays the translation view page
     * @param string $langcode
     * @param string $id
     */
    public function viewTranslator($langcode, $id)
    {
        $this->setLanguageTranslator($langcode);
        $this->setContentTranslator($id);
        $this->setTitleViewTranslator();
        $this->setBreadcrumbViewTranslator();

        $this->setData('strings', $this->data_content);
        $this->setData('language', $this->data_language);

        $this->outputViewTranslator();
    }

    /**
     * Read content from a translation file
     * @param string $hash
     */
    protected function setContentTranslator($hash)
    {
        $parsed = $this->parseHashTranslator($hash);

        if (empty($parsed)) {
            $this->outputHttpStatus(403);
        }

        $this->data_content = array();

        if (gplcart_path_is_absolute($parsed[1])) {
            $this->data_content = $this->translation->parseCsv($parsed[1]);
        }

        // Untranslated strings go first
        usort($this->data_content, function ($a) {
            return isset($a[1]) && $a[1] !== '' ? 1 : -1;
        });
    }

    /**
     * Sets titles on the translation view page
     */
    protected function setTitleViewTranslator()
    {
        $this->setTitle($this->text('Translation for %name', array('%name' => $this->data_language['name'])));
    }

    /**
     * Sets breadcrumbs on the translation view page
     */
    protected function setBreadcrumbViewTranslator()
    {
        $breadcrumb = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Render and output the translation view page
     */
    protected function outputViewTranslator()
    {
        $this->output('translator|view');
    }

}

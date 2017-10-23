<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator\controllers;

use gplcart\core\models\File as FileModel;
use gplcart\core\controllers\backend\Controller as BackendController;
use gplcart\modules\translator\models\Translator as TranslatorModuleModel;

/**
 * Handles incoming requests and outputs data related to Translator module
 */
class Translator extends BackendController
{

    /**
     * File model class instance
     * @var \gplcart\core\models\File $file
     */
    protected $file;

    /**
     * Translator model class instance
     * @var \gplcart\modules\translator\models\Translator $translator
     */
    protected $translator;

    /**
     * The current translation file
     * @var string
     */
    protected $data_file;

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
     * @param FileModel $file
     * @param TranslatorModuleModel $translator
     */
    public function __construct(FileModel $file,
            TranslatorModuleModel $translator)
    {
        parent::__construct();

        $this->file = $file;
        $this->translator = $translator;
    }

    /**
     * Displays the language list page
     */
    public function languageTranslator()
    {
        $this->setTitleLanguageTranslator();
        $this->setBreadcrumbLanguageTranslator();

        $this->setData('languages', $this->getLanguagesTranslator());

        $this->outputLanguageTranslator();
    }

    /**
     * Returns an array of sorted languages
     * @return array
     */
    protected function getLanguagesTranslator()
    {
        $languages = $this->language->getList();
        gplcart_array_sort($languages, 'name');
        return gplcart_array_split($languages, 6);
    }

    /**
     * Set titles on the language list page
     */
    protected function setTitleLanguageTranslator()
    {
        $this->setTitle($this->text('Translator'));
    }

    /**
     * Sets breadcrumbs on the language list page
     */
    protected function setBreadcrumbLanguageTranslator()
    {
        $this->setBreadcrumbHome();
    }

    /**
     * Render and output the language list page
     */
    protected function outputLanguageTranslator()
    {
        $this->output('translator|languages');
    }

    /**
     * Displays the file overview page
     * @param string $langcode
     */
    public function filesTranslator($langcode)
    {
        $this->setLanguageTranslator($langcode);

        $this->downloadFileTranslator();
        $this->actionFilesTranslator();

        $this->setTitleFilesTranslator();
        $this->setBreadcrumbFilesTranslator();

        $this->setData('language', $this->data_language);
        $this->setData('files', $this->getFilesTranslator());

        $this->outputFilesTranslator();
    }

    /**
     * Download a translation file
     */
    protected function downloadFileTranslator()
    {
        $hash = $this->getQuery('download');
        if (!empty($hash) && $this->access('module_translator_download')) {
            $info = $this->getDownloadFileTranslator($hash);
            if (!empty($info)) {
                $this->download($info[0], $info[1], array('text' => !empty($info[2])));
            }
        }
    }

    /**
     * Returns an array of file
     * @param string $hash
     * @return array
     */
    protected function getDownloadFileTranslator($hash)
    {
        $parts = $this->parseHashTranslator($hash);

        if (empty($parts)) {
            return array();
        }

        list($module_id, $file) = $parts;

        if (gplcart_path_is_absolute($file)) {
            return array($file, "$module_id-" . basename($file));
        }

        $csv_string = $this->translator->readZip($module_id, $file, $this->data_language['code']);
        return empty($csv_string) ? array() : array($csv_string, $hash, true);
    }

    /**
     * Parses a hash string containing module ID and translation file
     * @param string $hash
     * @return array
     */
    protected function parseHashTranslator($hash)
    {
        if (pathinfo($hash, PATHINFO_EXTENSION) === 'csv') {
            $parts = explode('-', $hash, 2);
            if (empty($parts[1])) {
                return array();
            }

            list($module_id, $file) = $parts;
        } else {

            $file = gplcart_path_absolute(gplcart_string_decode($hash));
            if (!$this->translator->isTranslationFile($file, $this->data_language['code'])) {
                return array();
            }

            $module_id = $this->translator->getModuleIdFromPath($file);

            if (empty($module_id)) {
                $module_id = 'core';
            }
        }

        if ($module_id !== 'core' && !$this->config->getModule($module_id)) {
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
            $message = $this->text('Deleted %num item(s)', array('%num' => $deleted));
            $this->setMessage($message, 'success');
        }
    }

    /**
     * Deletes a translation file
     * @param string $id
     * @return boolean
     */
    protected function deleteFileTranslator($id)
    {
        $info = $this->getDownloadFileTranslator($id);

        if (isset($info[0])) {
            return $this->translator->delete($info[0], $this->data_language['code']);
        }

        return false;
    }

    /**
     * Returns an array of files for the language
     * @return array
     */
    protected function getFilesTranslator()
    {
        if ($this->getQuery('tab') === 'compiled') {
            return $this->getCompiledFilesTranslator();
        }

        return $this->getPrimaryFilesTranslator();
    }

    /**
     * Sets titles on the file overview page
     */
    protected function setTitleFilesTranslator()
    {
        $vars = array('%name' => $this->data_language['name']);
        $this->setTitle($this->text('Translations for %name', $vars));
    }

    /**
     * Sets breadcrumbs on the files overview page
     */
    protected function setBreadcrumbFilesTranslator()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $breadcrumbs[] = array(
            'url' => $this->url('admin/tool/translator'),
            'text' => $this->text('Languages')
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
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
        $this->setData('modules', $this->config->getModules());

        $this->submitUploadTranslator();
        $this->outputUploadTranslator();
    }

    /**
     * Controls access to upload a translation file
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
        $vars = array('%name' => $this->data_language['name']);
        $this->setTitle($this->text('Upload translation for %name', $vars));
    }

    /**
     * Sets breadcrumbs on the upload translation page
     */
    protected function setBreadcrumbUploadTranslator()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $breadcrumbs[] = array(
            'url' => $this->url('admin/tool/translator'),
            'text' => $this->text('Languages')
        );

        $breadcrumbs[] = array(
            'url' => $this->url("admin/tool/translator/{$this->data_language['code']}"),
            'text' => $this->text('Translations for %name', array('%name' => $this->data_language['name']))
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Handles submission of translation file
     */
    protected function submitUploadTranslator()
    {
        if ($this->isPosted('save') && $this->validateUploadTranslator()) {
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

        if (!empty($scope) && !$this->config->getModule($scope)) {
            $this->setError('scope', $this->text('@field has invalid value', array('@field' => $scope)));
            return false;
        }

        $this->setSubmitted('destination', $this->language->getFile($this->data_language['code'], $scope));
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

        $result = $this->file->upload($file, 'csv');

        if ($result !== true) {
            $this->setError('file', $result);
            return false;
        }

        $this->setSubmitted('file', $this->file->getTransferred());
        return true;
    }

    /**
     * Copy a uploaded translation
     */
    protected function copyFileTranslator()
    {
        $this->controlAccessUploadTranslator();

        $source = $this->getSubmitted('file');
        $destination = $this->getSubmitted('destination');
        $result = $this->translator->copy($source, $destination);

        if ($result) {
            $this->redirect('', $this->text('Translation has been saved. Now you can <a href="@url">refresh language</a>', array('@url' => $this->url('admin/settings/language'))), 'success');
        }

        $this->redirect('', $this->text('Translation has not been saved'), 'warning');
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
    protected function getPrimaryFilesTranslator()
    {
        $files = array($this->language->getFile($this->data_language['code']));

        foreach (array_keys($this->config->getModules()) as $module_id) {
            $files[$module_id] = $this->language->getFile($this->data_language['code'], $module_id);
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
        $directory = $this->language->getCompiledDirectory($this->data_language['code']);

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
        $js_file = $this->language->getContextJsFile($langcode);
        $common_file = $this->language->getCommonFile($langcode);

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
        $this->data_language = $this->language->get($langcode);

        if (empty($this->data_language)) {
            $this->outputHttpStatus(404);
        }
    }

    /**
     * Displays list of translation files available for import
     * @param string $langcode
     */
    public function listImportTranslator($langcode)
    {
        $this->setLanguageTranslator($langcode);

        $this->actionImportTranslator();
        $this->downloadFileTranslator();

        $this->setTitleListImportTranslator();
        $this->setBreadcrumbListImportTranslator();

        $this->setData('language', $this->data_language);
        $this->setData('modules', $this->config->getModules());
        $this->setData('list', $this->translator->getImportList($langcode));
        $this->setData('time', $this->config->get('module_translator_saved', 0));
        $this->setData('download_url', $this->translator->getImportDownloadUrl());

        $this->submitImportTranslator();
        $this->outputListImportTranslator();
    }

    /**
     * Handles submitted import
     */
    protected function submitImportTranslator()
    {
        if ($this->isPosted('update')) {
            if ($this->translator->clearImport()) {
                $this->redirect('', $this->text('File has been updated'), 'success');
            }
            $this->redirect('', $this->text('File has not been updated'), 'warning');
        }
    }

    /**
     * Bulk actions for selected translations
     */
    protected function actionImportTranslator()
    {
        list($selected, $action) = $this->getPostedAction();

        $submitted = array();
        foreach ($selected as $id) {
            if ($action === 'import') {
                list($module_id, $file) = explode('-', $id, 2);
                if (isset($submitted[$module_id])) {
                    $this->setMessage($this->text('Please select only one file per module'), 'warning');
                    return null;
                }

                $submitted[$module_id] = $file;
            }
        }

        $imported = 0;
        foreach ($submitted as $module_id => $file) {
            $imported += (int) $this->translator->importContent($module_id, $file, $this->data_language['code']);
        }

        if ($imported > 0) {
            $this->setMessage($this->text('Imported @num translation(s). Now you can <a href="@url">refresh language</a>', array('@num' => $imported, '@url' => $this->url('admin/settings/language'))), 'success');
        }
    }

    /**
     * Sets titles on the translation list page
     */
    protected function setTitleListImportTranslator()
    {
        $vars = array('%name' => $this->data_language['name']);
        $this->setTitle($this->text('Import translations for %name', $vars));
    }

    /**
     * Sets breadcrumbs on the translation list page
     */
    protected function setBreadcrumbListImportTranslator()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $breadcrumbs[] = array(
            'url' => $this->url('admin/tool/translator'),
            'text' => $this->text('Languages')
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Render and output the translation list page
     */
    protected function outputListImportTranslator()
    {
        $this->output('translator|import');
    }

    /**
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
     * Read content from ZIP file
     * @param string $hash
     */
    protected function setContentTranslator($hash)
    {
        $parsed = $this->parseHashTranslator($hash);

        if (empty($parsed)) {
            $this->outputHttpStatus(403);
        }

        list($module_id, $file) = $parsed;

        if (gplcart_path_is_absolute($file)) {
            $this->data_content = $this->language->parseCsv($file);
        } else {
            $list = $this->translator->getImportList();
            if (!isset($list[$module_id][$this->data_language['code']][$file]['content'])) {
                $this->outputHttpStatus(403);
            }

            $this->data_content = $list[$module_id][$this->data_language['code']][$file]['content'];
        }

        // Untranslated strings go first
        usort($this->data_content, function($a) {
            return isset($a[1]) && $a[1] !== '' ? 1 : -1;
        });
    }

    /**
     * Sets titles on the translation view page
     */
    protected function setTitleViewTranslator()
    {
        $vars = array('%name' => $this->data_language['name']);
        $this->setTitle($this->text('Translation for %name', $vars));
    }

    /**
     * Sets breadcrumbs on the translation view page
     */
    protected function setBreadcrumbViewTranslator()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard')
        );

        $breadcrumbs[] = array(
            'url' => $this->url('admin/tool/translator'),
            'text' => $this->text('Languages')
        );

        $breadcrumbs[] = array(
            'url' => $this->url("admin/tool/translator/{$this->data_language['code']}/import"),
            'text' => $this->text('Import translations for %name', array('%name' => $this->data_language['name']))
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Render and output the translation view page
     */
    protected function outputViewTranslator()
    {
        $this->output('translator|view');
    }

}

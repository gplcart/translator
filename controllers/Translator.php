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
     * Translator model class instance
     * @var \gplcart\modules\translator\models\Translator $translator
     */
    protected $translator;

    /**
     * File model class instance
     * @var \gplcart\core\models\File $file
     */
    protected $file;

    /**
     * The current language
     * @var array
     */
    protected $data_language;

    /**
     * The current translation file
     * @var string
     */
    protected $data_file;

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
        $this->outputLanguageTranslator();
    }

    /**
     * Set titles on the language list page
     */
    protected function setTitleLanguageTranslator()
    {
        $this->setTitle($this->text('Translator'));
    }

    /**
     * Sets bread crumbs on the language list page
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

        $compiled = $this->isQuery('compiled');

        $this->setData('compiled', $compiled);
        $this->setData('language', $this->data_language);
        $this->setData('files', $this->getFilesTranslator($compiled));

        $this->outputFilesTranslator();
    }

    /**
     * Download a translation file
     */
    protected function downloadFileTranslator()
    {
        $hash = $this->getQuery('download');

        if (!empty($hash)) {
            $this->download($this->getFileFromHash($hash));
        }
    }

    /**
     * Applies an action to the selected translation files
     */
    protected function actionFilesTranslator()
    {
        $action = $this->getPosted('action', '', true, 'string');
        $selected = $this->getPosted('selected', array(), true, 'array');

        if (empty($action)) {
            return null;
        }

        $deleted = 0;
        foreach ($selected as $hash) {

            if ($action === 'delete' && $this->access('module_translator_delete')) {
                $deleted += (int) $this->deleteFileTranslator($hash);
            }
        }

        if ($deleted > 0) {
            $message = $this->text('Deleted %num items', array('%num' => $deleted));
            $this->setMessage($message, 'success', true);
        }
    }

    /**
     * Deletes a translation file
     * @param string $hash
     * @return boolean
     */
    protected function deleteFileTranslator($hash)
    {
        $file = $this->getFileFromHash($hash);
        return empty($file) ? false : $this->translator->delete($file, $this->data_language['code']);
    }

    /**
     * Returns an array of files for the language
     * @param bool $compiled
     * @return array
     */
    protected function getFilesTranslator($compiled)
    {
        return $compiled ? $this->getCompiledFilesTranslator() : $this->getPrimaryFilesTranslator();
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
        $this->setBreadcrumbEditTranslator();
    }

    /**
     * Handles a submitted translation file
     */
    protected function submitUploadTranslator()
    {
        if ($this->isPosted('save') && $this->validateUploadTranslator()) {
            $this->copyFileTranslator();
        }
    }

    /**
     * Validates a file upload
     * @return boolean
     */
    protected function validateUploadTranslator()
    {
        $this->setSubmitted('translation');

        $this->validateUploadContextTranslator();
        $this->validateUploadFileTranslator();

        return !$this->hasErrors();
    }

    /**
     * Validates a uploaded translation file
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
     * Validates a context of uploaded file
     * @return boolean
     */
    protected function validateUploadContextTranslator()
    {
        $context = $this->getSubmitted('context');

        if (!empty($context) && !$this->config->getModule($context)) {
            $this->setError('context', $this->text('@field has invalid value', array('@field' => $context)));
            return false;
        }

        $this->setSubmitted('destination', $this->language->getFile($this->data_language['code'], $context));
        return true;
    }

    /**
     * Controls access to upload a translation file
     */
    protected function controlAccessUploadTranslator()
    {
        $this->controlAccess('file_upload');
        $this->controlAccess('module_translator_add');
    }

    /**
     * Copy a uploaded translation
     */
    protected function copyFileTranslator()
    {
        $this->controlAccessUploadTranslator();

        $source = $this->getSubmitted('file');
        $langcode = $this->data_language['code'];
        $destination = $this->getSubmitted('destination');

        $result = $this->translator->copy($source, $destination, $langcode);

        if ($result) {
            $this->redirect('', $this->text('Translation has been saved'), 'success');
        }

        $this->redirect('', $this->text('Translation has not been saved'), 'danger');
    }

    /**
     * Render and output the upload translation page
     */
    protected function outputUploadTranslator()
    {
        $this->output('translator|upload');
    }

    /**
     * Displays the edit translation page
     * @param string $langcode
     * @param string $hash
     */
    public function editTranslator($langcode, $hash)
    {
        $this->setFileTranslator($hash);
        $this->setLanguageTranslator($langcode);

        $this->setTitleEditTranslator();
        $this->setBreadcrumbEditTranslator();

        $this->setData('language', $this->data_language);
        $this->setData('strings', $this->getStringsTranslator());

        $this->submitEditTranslator();

        $this->setJsEditTranslator();
        $this->outputEditTranslator();
    }

    /**
     * Sets breadcrumbs on the edit translation page
     */
    protected function setBreadcrumbEditTranslator()
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
     * Sets titles on the edit translation page
     */
    protected function setTitleEditTranslator()
    {
        $vars = array('%name' => gplcart_relative_path($this->data_file));
        $this->setTitle($this->text('Edit %name', $vars));
    }

    /**
     * Handles an array of submitted data while editing a translation file
     */
    protected function submitEditTranslator()
    {
        if (($this->isPosted('save') || $this->isPosted('save_refresh')) && $this->validateEditTranslator()) {
            $this->updateFileTranslator();
        }
    }

    /**
     * Validates a submitted data before updating a translation file
     * @return bool
     */
    protected function validateEditTranslator()
    {
        $this->setSubmitted('strings', null, false);
        return true;
    }

    /**
     * Updates a translation file
     */
    protected function updateFileTranslator()
    {
        $this->controlAccess('module_translator_edit');

        $result = $this->translator->update($this->data_file, $this->data_language['code'], $this->getSubmitted());

        if ($this->isPosted('save_refresh')) {
            $this->language->refresh($this->data_language['code']);
            $this->setMessage($this->text('Cache has been deleted'), 'success', true);
        }

        $severity = 'success';
        $message = $this->text('Translation has been updated');

        if (!$result) {
            $severity = 'warning';
            $message = $this->text('Translation has not been updated');
        }

        $this->redirect('', $message, $severity);
    }

    /**
     * Sets Java-scripts on the edit translation page
     */
    protected function setJsEditTranslator()
    {
        $this->setJs('system/modules/translator/js/common.js');
    }

    /**
     * Render and display the edit translation page
     */
    protected function outputEditTranslator()
    {
        $this->output('translator|edit');
    }

    /**
     * Render and output the file overview page
     */
    protected function outputFilesTranslator()
    {
        $this->output('translator|files');
    }

    /**
     * Sets the current translation file
     * @param string $hash
     */
    protected function setFileTranslator($hash)
    {
        $this->data_file = $this->getFileFromHash($hash);

        if (empty($this->data_file)) {
            $this->outputHttpStatus(403);
        }
    }

    /**
     * Converts a base 64 encoded file path into the full file path
     * @param string $hash
     * @return string
     */
    protected function getFileFromHash($hash)
    {
        $file = gplcart_absolute_path(gplcart_string_decode($hash));

        if ($this->translator->isTranslationFile($file, $this->data_language['code'])) {
            return $file;
        }

        return '';
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
                $files[$id] = $this->getFileInfo($file);
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
        $files = array();
        foreach ($this->language->getCompiledFiles($this->data_language['code']) as $file) {
            $files[] = $this->getFileInfo($file);
        }

        return $files;
    }

    /**
     * Returns an array of translation info about the file
     * @param string $file
     * @return array
     */
    protected function getFileInfo($file)
    {
        $path = gplcart_relative_path($file);
        $context = str_replace('-', '\\', pathinfo(basename($path), PATHINFO_FILENAME));

        return array(
            'path' => $path,
            'modified' => filemtime($file),
            'hash' => gplcart_string_encode($path),
            'filesize' => gplcart_file_size(filesize($file)),
            'progress' => $this->translator->getFileInfo($file),
            'context' => $context === $this->data_language['code'] ? '' : $context
        );
    }

    /**
     * Parse a translation file
     * @return array
     */
    protected function getStringsTranslator()
    {
        $strings = $this->translator->get($this->data_file);

        // Untranslated strings go first
        usort($strings, function($a) {
            return isset($a[1]) && $a[1] !== '' ? 1 : -1;
        });

        return $strings;
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

}

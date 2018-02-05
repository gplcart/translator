<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator;

/**
 * Main class for Translator module
 */
class Main
{

    /**
     * Implements hook "module.install.before"
     * @param null|string $result
     */
    public function hookModuleInstallBefore(&$result)
    {
        if (!class_exists('ZipArchive')) {
            $result = gplcart_text('Class ZipArchive does not exist');
        }
    }

    /**
     * Implements hook "route.list"
     * @param array $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/tool/translator'] = array(
            'menu' => array(
                'admin' => 'Translator' // @text
            ),
            'access' => 'module_translator',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'languageTranslator')
            )
        );

        $routes['admin/tool/translator/(\w+)'] = array(
            'access' => 'module_translator',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'filesTranslator')
            )
        );

        $routes['admin/tool/translator/(\w+)/upload'] = array(
            'access' => 'module_translator_upload',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'uploadTranslator')
            )
        );

        $routes['admin/tool/translator/(\w+)/view/([^/]+)'] = array(
            'access' => 'module_translator',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'viewTranslator')
            )
        );

        $routes['admin/tool/translator/(\w+)/import'] = array(
            'access' => 'module_translator_import',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'listImportTranslator')
            )
        );
    }

    /**
     * Implements hook "user.role.permissions"
     * @param array $permissions
     */
    public function hookUserRolePermissions(array &$permissions)
    {
        $permissions['module_translator'] = 'Translator: access'; // @text
        $permissions['module_translator_upload'] = 'Translator: upload translations'; // @text
        $permissions['module_translator_delete'] = 'Translator: delete translations'; // @text
        $permissions['module_translator_import'] = 'Translator: import translations'; // @text
        $permissions['module_translator_download'] = 'Translator: download translations'; // @text
    }

}

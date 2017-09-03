<?php

/**
 * @package Translator
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GPL-3.0+
 */

namespace gplcart\modules\translator;

use gplcart\core\Module;

/**
 * Main class for Translator module
 */
class Translator extends Module
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Implements hook "route.list"
     * @param array $routes
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/tool/translator'] = array(
            'menu' => array('admin' => 'Translator'),
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

        $routes['admin/tool/translator/(\w+)/edit/(\w+)'] = array(
            'access' => 'module_translator_edit',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'editTranslator')
            )
        );

        $routes['admin/tool/translator/(\w+)/add'] = array(
            'access' => 'module_translator_add',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\translator\\controllers\\Translator', 'uploadTranslator')
            )
        );
    }

    /**
     * Implements hook "user.role.permissions"
     * @param array $permissions
     */
    public function hookUserRolePermissions(array &$permissions)
    {
        $permissions['module_translator'] = 'Translator module: access';
        $permissions['module_translator_add'] = 'Translator module: add translations';
        $permissions['module_translator_edit'] = 'Translator module: edit translations';
        $permissions['module_translator_delete'] = 'Translator module: delete translations';
    }

}

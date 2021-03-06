<?php
/**
 * Admin Shell
 *
 * Generates admin-like plugins based on composition of cakephp templates
 *
 * @copyright     Copyright 2010, Jose Diaz-Gonzalez. (http://josediazgonzalez.com)
 * @link          http://josediazgonzalez.com
 * @package       cake_admin
 * @subpackage    cake_admin.vendors.shells
 * @since         ApiGenerator 0.1
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 **/
if (!class_exists('CakeAdmin')) App::import('Lib', 'CakeAdmin.cake_admin');
if (!class_exists('Shell')) App::import('Core', 'Shell');
class AdminShell extends Shell {

/**
 * Tasks to be loaded by this Task
 *
 * @var array
 */
    var $tasks = array(
        'AdminTemplate',
        'AdminModel',
        'AdminController',
        'AdminView'
    );

/**
 * Holds tables found on connection.
 *
 * @var array
 * @access protected
 */
    var $_tables = array();

    var $booleans = array();
    var $strings = array();
    var $arrays = array();
    var $array_required = array();

/**
 * Folder handler
 *
 * @var File
 **/
    var $handler;

/**
 * Override main
 *
 */
    function main() {
        // Create the admin object
        $files = $this->find();
        if (!$files) {
            $this->error(__('Admin files not found', true));
        }
        $build = 0;
        $plugins = array();
        $adminClasses = array();
        foreach ($files as $file) {
            // Create an instance of the particular admin class
            $className = Inflector::camelize(str_replace('.php', '', $file));
            App::import('Lib', $className, array('file' => "admin/{$file}"));
            $admin = new $className;

            // Validate the admin class
            if (!$this->validate($admin)) {
                $this->err(sprintf(
                    __('Validation failed for %s', true),
                    $className
                ));
                continue;
            }

            // Check that paths can be written
            if (($error = $this->checkBuild($admin)) !== true) {
                $this->err(sprintf('%s for %s', $error, $className));
                continue;
            }

            $adminClasses[$file] = $admin;
            $plugins[$admin->plugin][] = array(
                'title'     => $this->_controllerName($admin->modelName),
                'controller'=> $this->_pluralName($this->_controllerName($admin->modelName)),
                'action'    => $admin->redirectTo,
                'adminClass'=> $admin,
            );
        }

        $this->generateApp($plugins);

        foreach ($adminClasses as $file => $admin) {
            if (!$this->generate($admin)) {
                $this->err(sprintf(
                    __('Error in generating admin for %s', true),
                    $className
                ));
                continue;
            }

            $build++;
        }

        $this->generateMisc($plugins);

        $fails = count($files) - $build;
        if ($build == 0) {
            $this->out(__('Failed to build admin', true));
        }

        $this->out(sprintf(
            __('Admin successfully built for %s models', true),
            count($file)
        ));

        if ($fails !== 0) {
            $this->out(sprintf(
                __('Fix all %s errors before regenerating admin', true),
                $fails
            ));
        }
    }

/**
 * Finds all CakeAdmin files in app/libs/admin
 *
 * @return array
 * @todo test me
 */
    function find() {
        $this->handler();
        $this->handler->cd(APPLIBS);
        $content = $this->handler->read();

        if (empty($content[0])) {
            return false;
        }

        if (!in_array('admin', $content[0])) {
            return false;
        }

        $this->handler->cd(APPLIBS . 'admin');
        $content = $this->handler->find('([a-z_]+)(.php)');

        return (empty($content)) ? false : $content;
    }

/**
 * Validates an Admin class
 *
 * Checks to ensure the types of each variable are set
 * and are valid in the context of the generation
 *
 * If a variable is private or protected access, returns
 * false
 *
 * @param CakeAdmin $admin
 * @return boolean
 * @todo test me
 **/
    function validate($admin) {
        foreach ($this->booleans as $var) {
            if (!is_bool($admin->$$var)) {
                return false;
            }
        }

        foreach ($this->strings as $var) {
            if (!is_string($admin->$$var) || empty($admin->$$var)) {
                return false;
            }
        }

        foreach ($this->arrays as $var) {
            if (!is_array($admin->$$var)) {
                return false;
            }
        }

        foreach ($this->array_required as $var) {
            if (empty($admin->$$var)) {
                return false;
            }
        }

        return true;
    }

/**
 * Checks that directories are writeable
 *
 * Checks the 'plugin' prefix class variable
 * to ensure that the plugin path, and all
 * subpaths, are writeable by the system.
 *
 * Also ensures that templates are available for
 * all actions, plugins, etc
 *
 * @param CakeAdmin $admin
 * @return mixed boolean true if successful, string error message otherwise
 * @todo test me
 */
    function checkBuild($admin) {
        $this->handler();
        $this->handler->cd(APP);
        $contents = $this->handler->read();

        if (!in_array('plugins', $contents[0])) {
            return __("Missing Plugin directory", true);
        }

        $this->handler->cd(APP . 'plugins');
        $contents = $this->handler->read();
        $path = APP . 'plugins' . DS . $admin->plugin;
        // Recover if the required plugin directory is missing
        if (!in_array($admin->plugin, $contents[0])) {
            $this->handler->create($path);
        }

        $contents = $this->handler->read();
        if (!in_array($admin->plugin, $contents[0])) {
            return sprintf(__("Unable to create path: %s", true), $path);
        }

        // Check all the required MVC directories
        $required = array('controllers', 'models', 'views');
        $this->handler->cd($path);
        $content = $this->handler->read();

        foreach ($required as $directory) {
            if (!in_array($directory, $content[0])) {
                $this->handler->create($path . DS . $directory);
            }
            $content = $this->handler->read();

            if (!in_array($directory, $content[0])) {
                return sprintf(__('Missing directory: %s', true), $directory);
            }
        }

        // Check that the directories and files are writeable by shell
        foreach ($required as $directory) {
            if (!$this->handler->chmod($path . DS .$directory)) {
                return sprintf(__('Directory not writeable: %s', true), 
                    $directory
                );
            }
        }

        return true;
    }

/**
 * Compiles the admin section for a particular model
 *
 * @param CakeAdmin $admin
 * @return boolean
 * @todo test me
 **/
    function generate($admin) {
        if (!$this->AdminModel->generate($admin))  {
            $this->out();
            $this->out(sprintf('Failed to generate %s Model',
                $admin->modelName
            ));
            $this->out();
            return false;
        }
        if (!$this->AdminController->generate($admin)) {
            $this->out();
            $this->out(sprintf('Failed to generate %s Controller',
                $this->_controllerName($admin->modelName)
            ));
            $this->out();
            return false;
        }
        if (!$this->AdminView->generate($admin)) {
            $this->out();
            $this->out(sprintf('Failed to generate %s Views',
                $this->_controllerName($admin->modelName)
            ));
            $this->out();
            return false;
        }
        return true;
    }

/**
 * Generates App classes for each plugin
 *
 * @return boolean
 * @author Jose Diaz-Gonzalez
 **/
    function generateApp($plugins) {
        $generated = array();

        foreach ($plugins as $plugin => $cakeAdmins) {
            if (in_array($plugin, $generated)) continue;
            $generated[] = $plugin;

            $admin = current($cakeAdmins);
            $admin = $admin['adminClass'];

            if (!$this->AdminController->generateAppController($admin)) {
                $this->out();
                $this->out(sprintf('Failed to generate %s AppController',
                    Inflector::humanize($admin->plugin)
                ));
                $this->out();
                return false;
            }
            if (!$this->AdminModel->generateAppModel($admin)) {
                $this->out();
                $this->out(sprintf('Failed to generate %s AppModel',
                    Inflector::humanize($admin->plugin)
                ));
                $this->out();
                return false;
            }
        }
        return true;
    }

/**
 * Adds extra data for each generated plugin, such as
 * layouts, css, and elements
 *
 * @return void
 * @author Jose Diaz-Gonzalez
 **/
    function generateMisc($plugins) {
        foreach ($plugins as $plugin => $cakeAdmins) {
            // Generate flash elements
            $files = array('error', 'info', 'notice', 'success');

            $templatePath = array();
            $templatePath[] = APP . 'plugins' . DS . 'cake_admin' . DS . 'libs' . DS;
            $templatePath[] = 'templates' . DS . 'misc' . DS;

            foreach ($files as $file) {
                $contents = $this->AdminTemplate->generate(implode($templatePath) . 'flash' . DS, $file);

                $path = APP . 'plugins' . DS . $plugin . DS . 'views' . DS . 'elements' . DS . 'flash' . DS;
                $this->createFile("{$path}{$file}.ctp", $contents);
            }

            // Generate CSS
            $contents = $this->AdminTemplate->generate(implode($templatePath), 'cake.admin.generic');
            $path = APP . 'plugins' . DS . $plugin . DS . 'webroot' . DS . 'css' . DS;
            $this->createFile("{$path}cake.admin.generic.css", $contents);

            // Generate Layout
            $this->AdminTemplate->set(compact(
                'plugin',
                'plugins'
            ));
            $contents = $this->AdminTemplate->generate(implode($templatePath), 'layout.default');
            $path = APP . 'plugins' . DS . $plugin . DS . 'views' . DS . 'layouts' . DS;
            $this->createFile("{$path}default.ctp", $contents);
        }
    }

/**
 * Sets up a folder handler
 *
 * Calls App::import('Core', 'Folder') if the handler has not
 * been created in order to create a new folder.
 *
 * @return boolean true if handler is setup, false otherwise
 * @todo test me
 */
    function handler() {
        if (!$this->handler) {
            App::import('Core', 'Folder');
            $this->handler = new Folder(APP);
        }
        return is_object($this->handler);
    }

}
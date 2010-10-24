<?php

class CakeAdmin {

/**
 * Name of the model. Must be overridden in order to create a
 * valid admin section, unless you want everything to reference
 * a Cake Model
 *
 * @var string
 */
    var $modelName      = 'cake';

/**
 * Name of database connection to use
 *
 * @var string
 */
    var $useDbConfig    = 'default';

/**
 * Name of the primaryKey of the model
 *
 * @var string
 */
    var $primaryKey     = 'id';

/**
 * Name of the displayField of the model
 *
 * @var string
 */
    var $displayField   = 'title';

/**
 * Instead of prefixes, we use plugins to set a valid prefix
 * Pluginizing the app means we can isolate the "admin" portion
 * From everything else. One can also App::import() the plugin
 * Models/Views and import the admin goodness.
 *
 * Isolating the CakeAdmin'ed app from everything else is good
 * for ease of updating code without have to worry about a
 * re-admin deleting customizations
 *
 * @var string
 */
    var $plugin         = 'admin';

/**
 * Apply all these Behaviors to the Model
 *
 * @var array
 */
    var $behaviors      = array();

/**
 * Apply all these Components to the Controller
 *
 * @var string
 **/
    var $components     = array();

/**
 * Apply all these Helpers to the Controller
 *
 * @var string
 **/
    var $helpers    = array();

/**
 * Model validation rules
 *
 * alias => rule, where rule can be an array. If the rule is a string or the
 * message is missing, the message is set to some default.
 *
 * Validation rules are wrapped in __d() calls within the CakeAdmin model template,
 * meaning your admin section can be localized at a later date and time.
 *
 * @var array
 */
    var $validations    = array();

/**
 * Relations to use on this model. Defaults to belongsTo.
 * Assumes conventions, but you can configure if necessary
 *
 * @var string
 */
    var $relations = array();

/**
 * Customize the views further from the base
 *
 * @var array
 */
    var $views      = array(
        'index' => array(
            'fields'        => '*',
            'list_filter'   => null,
            'link'          => array('id'),                 // Link to object here
            'order'         => 'id ASC',                    // Default ordering
            'search'        => array('id'),                 // Allow searching of these fields
            'sort'          => true,                        // Allow sorting. Also takes array of fields to enable sorting on
        ),
        'create' => array(
            array('fields'  => array('title', 'content')),
        ),
        'update' => array(
            array('fields' => array('title', 'content')),
        )
    );

/**
 * The following actions are implemented, where alias => action
 *
 * @var array
 **/
    var $actions = array(
        'add'       => 'add',
        'view'      => 'view',
        'edit'      => 'edit',
        'delete'    => 'delete',
        'history'   => 'history',
        'changelog' => 'changelog',
    );

/**
 * Auth Configuration
 * We can decide which actions we want to require auth for
 * Authentication can be implemented by some plugin adapter, app-wide
 *
 * @var string
 * @todo implement me
 */
    var $auth = array();

/**
 * AuthImplementation
 *
 * Either Acl, Auth, Authsome.Authsome, or Sanction.Permit, or the boolean false
 *
 * Acl requires Auth, Sanction.Permit requires Authsome
 *
 * @var array
 * @todo implement me
 */
    var $authImplementation = false;

/**
 * Use sessions?
 *
 * @var boolean
 * @default true
 */
    var $sessions = true;

/**
 * Automagic Webservice?
 *
 * @var boolean
 * @default false
 * @todo implement me
 **/
    var $webservice = false;

/**
 * Action to redirect to on errors
 *
 * @var string
 * @default index
 */
    var $redirectTo = 'index';

    function __construct() {
        // Set a table if not already set
        if (!isset($this->useTable)) {
            $this->useTable = Inflector::tableize($this->modelName);
        }
        // Iterate over validation rules to set proper defaults
        if (!empty($this->validations)) {
            $validationRules = array();
            foreach ($this->validations as $fieldName => $rules) {
                if (is_string($rules)) {
                    $options = array('rule' => $rules);
                    $rule = $options['rule'];
                    $options['message'] = $this->_validationMessage($fieldName, $rule, $options);
                    $validationRules[$fieldName][$rule] = $options;
                    continue;
                }

                foreach ($rules as $alias => $options) {
                    if (!is_array($options)) {
                        $options = array('rule' => $options);
                    }
                    $rule = $options['rule'];
                    if (is_array($rule)) {
                        $rule = current($rule);
                    }
                    if (empty($options['message'])) {
                        $options['message'] = $this->_validationMessage($fieldName, $rule, $options);
                    }
                    $validationRules[$fieldName][$alias] = $options;
                }
            }
            $this->validations = $validationRules;
        }
    }

    function _validationMessage($fieldName, $rule, $options = array()) {
        $fieldName = Inflector::humanize((preg_replace('/_id$/', '', $fieldName)));

        // Retrieve rule message
        $ruleMessage = '{{field}} must be valid input';
        if (in_array($rule, $this->_validationMessages)) {
            $ruleMessage = $this->_validationMessages[$rule];
        }

        // Requires only fieldName
        $fieldNameOnly = array(
            'alphaNumeric', 'blank', 'boolean',
            'email', 'extension', 'file',
            'ip', 'inList', 'isUnique',
            'money', 'numeric', 'notEmpty',
            'phone', 'postal', 'ssn', 'url'
        );
        $ruleMessage = str_replace("{{field}}", $fieldName, $ruleMessage);
        if (in_array($rule, $fieldNameOnly)) return $ruleMessage;

        // Require min/max
        $minMaxRequired = array('between', 'multiple', 'range');
        if (in_array($rule, $minMaxRequired)) {
            $min = (isset($options['min'])) ? $options['min'] : 'min';
            $max = (isset($options['max'])) ? $options['max'] : 'max';
            $ruleMessage = str_replace("{{min}}", $min, $ruleMessage);
            return str_replace("{{max}}", $max, $ruleMessage);
        }

        // Require Length
        $lengthRequired = array('decimal', 'maxLength', 'minLength');
        if (in_array($rule, $lengthRequired)) {
            $length = (isset($options['length'])) ? $options['length'] : 'length';
            return str_replace("{{length}}", $length, $ruleMessage);
        }
        // Comparison, [comparison, value]
        if ($rule == 'comparison') {
            $comparison = (isset($options['comparison'])) ? $options['comparison'] : 'comparison';
            $value = (isset($options['value'])) ? $options['value'] : 'value';
            $ruleMessage = str_replace("{{comparison}}", $comparison, $ruleMessage);
            return str_replace("{{value}}", $value, $ruleMessage);
        }

        // Format
        if ($rule == 'format') {
            $format = (isset($options['format'])) ? $options['format'] : 'format';
            return str_replace("{{format}}", $format, $ruleMessage);
        }
        return $ruleMessage;
    }

/**
 * Default Validation Messages
 *
 * Messages may contain the string `{{field}}` in order to support
 * inclusion of fieldnames via str_replace
 *
 * Rules that have parameters may include those parameters as {{parameter}}
 *
 * When overriding these in sub-classes, remember to either override
 * in the __construct() method to array_merge with the defaults, or
 * specify all the messages that may be used
 *
 * @var string
 */
    var $_validationMessages = array(
        'alphaNumeric'  => '{{field}} must only contain letters and numbers',
        'between'       => '{{field}} must be between {{min}} and {{max}} characters long',
        'blank'         => '{{field}} must be blank or contain only whitespace characters',
        'boolean'       => 'Incorrect value for {{field}}',
        'cc'            => 'The credit card number you supplied was invalid',
        'comparison'    => '{{field}} must be {{comparison}} to {{value}}',
        'date'          => 'Enter a valid date in {{format}} format',
        'decimal'       => '{{field}} must be a valid decimal number with at least {{length}} decimal points',
        'email'         => '{{field}} must be a valid email address',
        'equalTo'       => '{{field}} must be equal to {{number}}',
        'extension'     => '{{field}} must have a valid extension',
        'file'          => '{{field}} must be a valid file name',
        'ip'            => '{{field}} must be a valid IP address',
        'inList'        => 'Your selection for {{field}} must be in the given list',
        'isUnique'      => 'This {{field}} has already been taken',
        'maxLength'     => '{{field}} must have less than {{length}} characters',
        'minLength'     => '{{field}} must have at least {{length}} characters',
        'money'         => '{{field}} must be a valid monetary amount',
        'multiple'      => 'You must select at least {{min}} and no more than {{max}} options for {{field}}',
        'numeric'       => '{{field}} must be numeric',
        'notEmpty'      => '{{field}} cannot be empty',
        'phone'         => '{{field}} must be a valid phone number',
        'postal'        => '{{field}} must be a valid postal code',
        'range'         => '{{field}} must be between {{min}} and {{max}}',
        'ssn'           => '{{field}} must be a valid social security number',
        'url'           => '{{field}} must be a valid url',
    );
}
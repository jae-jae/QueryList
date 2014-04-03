<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category  Zend
 * @package   Zend_Validate
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 * @version   $Id: $
 */

/**
 * @see Zend_Validate_Abstract
 */
require_once 'Zend/Validate/Abstract.php';

/**
 * Validator for the file extension of a file
 *
 * @category  Zend
 * @package   Zend_Validate
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Validate_File_Extension extends Zend_Validate_Abstract
{
    /**
     * @const string Error constants
     */
    const FALSE_EXTENSION = 'fileExtensionFalse';
    const NOT_FOUND       = 'fileExtensionNotFound';

    /**
     * @var array Error message templates
     */
    protected $_messageTemplates = array(
        self::FALSE_EXTENSION => "The file '%value%' has a false extension",
        self::NOT_FOUND       => "The file '%value%' was not found"
    );

    /**
     * Internal list of extensions
     * @var string
     */
    protected $_extension = '';

    /**
     * Validate case sensitive
     *
     * @var boolean
     */
    protected $_case = false;

    /**
     * @var array Error message template variables
     */
    protected $_messageVariables = array(
        'extension' => '_extension'
    );

    /**
     * Sets validator options
     *
     * @param  string|array $extension
     * @param  boolean      $case      If true validation is done case sensitive
     * @return void
     */
    public function __construct($extension, $case = false)
    {
        $this->_case = (boolean) $case;
        $this->setExtension($extension);
    }

    /**
     * Returns the set file extension
     *
     * @param  boolean $asArray Returns the values as array, when false an concated string is returned
     * @return string
     */
    public function getExtension($asArray = false)
    {
        $asArray   = (bool) $asArray;
        $extension = (string) $this->_extension;
        if ($asArray) {
            $extension = explode(',', $extension);
        }

        return $extension;
    }

    /**
     * Sets the file extensions
     *
     * @param  string|array $extension The extensions to validate
     * @return Zend_Validate_File_Extension Provides a fluent interface
     */
    public function setExtension($extension)
    {
        $this->_extension = null;
        $this->addExtension($extension);
        return $this;
    }

    /**
     * Adds the file extensions
     *
     * @param  string|array $extension The extensions to add for validation
     * @return Zend_Validate_File_Extension Provides a fluent interface
     */
    public function addExtension($extension)
    {
        $extensions = $this->getExtension(true);
        if (is_string($extension)) {
            $extension = explode(',', $extension);
        }

        foreach ($extension as $content) {
            if (empty($content) || !is_string($content)) {
                continue;
            }

            $extensions[] = trim($content);
        }
        $extensions = array_unique($extensions);

        // Sanity check to ensure no empty values
        foreach ($extensions as $key => $ext) {
            if (empty($ext)) {
                unset($extensions[$key]);
            }
        }

        $this->_extension = implode(',', $extensions);

        return $this;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if the fileextension of $value is included in the
     * set extension list
     *
     * @param  string  $value Real file to check for extension
     * @param  array   $file  File data from Zend_File_Transfer
     * @return boolean
     */
    public function isValid($value, $file = null)
    {
        // Is file readable ?
        if (!@is_readable($value)) {
            $this->_throw($file, self::NOT_FOUND);
            return false;
        }

        if ($file !== null) {
            $info['extension'] = substr($file['name'], strpos($file['name'], '.') + 1);
        } else {
            $info = @pathinfo($value);
        }

        $extensions = $this->getExtension(true);

        if ($this->_case and (in_array($info['extension'], $extensions))) {
            return true;
        } else if (!$this->_case) {
            foreach ($extensions as $extension) {
                if (strtolower($extension) == strtolower($info['extension'])) {
                    return true;
                }
            }
        }

        $this->_throw($file, self::FALSE_EXTENSION);
        return false;
    }

    /**
     * Throws an error of the given type
     *
     * @param  string $file
     * @param  string $errorType
     * @return false
     */
    protected function _throw($file, $errorType)
    {
        if ($file !== null) {
            $this->_value = $file['name'];
        }

        $this->_error($errorType);
        return false;
    }
}

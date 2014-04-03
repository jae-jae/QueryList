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
 * Validator for counting all given files
 *
 * @category  Zend
 * @package   Zend_Validate
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Validate_File_Count extends Zend_Validate_Abstract
{
    /**#@+
     * @const string Error constants
     */
    const TOO_MUCH = 'fileCountTooMuch';
    const TOO_LESS = 'fileCountTooLess';
    /**#@-*/

    /**
     * @var array Error message templates
     */
    protected $_messageTemplates = array(
        self::TOO_MUCH => "Too much files, only '%value%' are allowed",
        self::TOO_LESS => "Too less files, minimum '%value%' must be given"
    );

    /**
     * @var array Error message template variables
     */
    protected $_messageVariables = array(
        'min' => '_min',
        'max' => '_max'
    );

    /**
     * Minimum file count
     *
     * If null, there is no minimum file count
     *
     * @var integer
     */
    protected $_min;

    /**
     * Maximum file count
     *
     * If null, there is no maximum file count
     *
     * @var integer|null
     */
    protected $_max;

    /**
     * Internal file array
     * @var array
     */
    protected $_files;

    /**
     * Sets validator options
     *
     * Min limits the file count, when used with max=null it is the maximum file count
     * It also accepts an array with the keys 'min' and 'max'
     *
     * @param  integer|array $min Minimum file count
     * @param  integer       $max Maximum file count
     * @return void
     */
    public function __construct($min, $max = null)
    {
        $this->_files = array();
        if (is_array($min) === true) {
            if (isset($min['max']) === true) {
                $max = $min['max'];
            }

            if (isset($min['min']) === true) {
                $min = $min['min'];
            }

            if (isset($min[0]) === true) {
                if (count($min) === 2) {
                    $max = $min[1];
                    $min = $min[0];
                } else {
                    $max = $min[0];
                    $min = null;
                }
            }
        }

        if (empty($max) === true) {
            $max = $min;
            $min = null;
        }

        $this->setMin($min);
        $this->setMax($max);
    }

    /**
     * Returns the minimum file count
     *
     * @return integer
     */
    public function getMin()
    {
        $min = $this->_min;

        return $min;
    }

    /**
     * Sets the minimum file count
     *
     * @param  integer $min            The minimum file count
     * @return Zend_Validate_File_Size Provides a fluent interface
     * @throws Zend_Validate_Exception When min is greater than max
     */
    public function setMin($min)
    {
        if ($min === null) {
            $this->_min = null;
        } else if (($this->_max !== null) and ($min > $this->_max)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception('The minimum must be less than or equal to the maximum file count, but '
                . " {$min} > {$this->_max}");
        } else {
            $this->_min = max(0, (integer) $min);
        }

        return $this;
    }

    /**
     * Returns the maximum file count
     *
     * @return integer|null
     */
    public function getMax()
    {
        return $this->_max;
    }

    /**
     * Sets the maximum file count
     *
     * @param  integer|null $max       The maximum file count
     * @throws Zend_Validate_Exception When max is smaller than min
     * @return Zend_Validate_StringLength Provides a fluent interface
     */
    public function setMax($max)
    {
        if ($max === null) {
            $this->_max = null;
        } else if (($this->_min !== null) and ($max < $this->_min)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The maximum must be greater than or equal to the minimum file count, but "
                . "{$max} < {$this->_min}");
        } else {
            $this->_max = (integer) $max;
        }

        return $this;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if the file count of all checked files is at least min and
     * not bigger than max (when max is not null). Attention: When checking with set min you
     * must give all files with the first call, otherwise you will get an false.
     *
     * @param  string|array $value Filenames to check for count
     * @param  array        $file  File data from Zend_File_Transfer
     * @return boolean
     */
    public function isValid($value, $file = null)
    {
        if (is_string($value)) {
            $value = array($value);
        }

        foreach ($value as $file) {
            if (!isset($this->_files[$file])) {
                $this->_files[$file] = $file;
            }
        }

        if (($this->_max !== null) && (count($this->_files) > $this->_max)) {
            $this->_value = $this->_max;
            $this->_error(self::TOO_MUCH);
            return false;
        }

        if (($this->_min !== null) && (count($this->_files) < $this->_min)) {
            $this->_value = $this->_min;
            $this->_error(self::TOO_LESS);
            return false;
        }

        return true;
    }
}

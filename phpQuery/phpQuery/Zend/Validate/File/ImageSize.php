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
 * Validator for the image size of a image file
 *
 * @category  Zend
 * @package   Zend_Validate
 * @copyright Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Validate_File_ImageSize extends Zend_Validate_Abstract
{
    /**
     * @const string Error constants
     */
    const WIDTH_TOO_BIG    = 'fileImageSizeWidthTooBig';
    const WIDTH_TOO_SMALL  = 'fileImageSizeWidthTooSmall';
    const HEIGHT_TOO_BIG   = 'fileImageSizeHeightTooBig';
    const HEIGHT_TOO_SMALL = 'fileImageSizeHeightTooSmall';
    const NOT_DETECTED     = 'fileImageSizeNotDetected';
    const NOT_READABLE     = 'fileImageSizeNotReadable';

    /**
     * @var array Error message template
     */
    protected $_messageTemplates = array(
        self::WIDTH_TOO_BIG    => "Width of the image '%value%' is bigger than allowed",
        self::WIDTH_TOO_SMALL  => "Width of the image '%value%' is smaller than allowed",
        self::HEIGHT_TOO_BIG   => "Height of the image '%value%' is bigger than allowed",
        self::HEIGHT_TOO_SMALL => "Height of the image '%value%' is smaller than allowed",
        self::NOT_DETECTED     => "Size of the image '%value%' could not be detected",
        self::NOT_READABLE     => "The image '%value%' can not be read"
    );

    /**
     * @var array Error message template variables
     */
    protected $_messageVariables = array(
        'minwidth'  => '_minwidth',
        'maxwidth'  => '_maxwidth',
        'minheight' => '_minheight',
        'maxheight' => '_maxheight'
    );

    /**
     * Minimum image width
     *
     * @var integer
     */
    protected $_minwidth;

    /**
     * Maximum image width
     *
     * @var integer
     */
    protected $_maxwidth;

    /**
     * Minimum image height
     *
     * @var integer
     */
    protected $_minheight;

    /**
     * Maximum image height
     *
     * @var integer
     */
    protected $_maxheight;

    /**
     * Sets validator options
     *
     * Min limits the filesize, when used with max=null if is the maximum filesize
     * It also accepts an array with the keys 'min' and 'max'
     *
     * @param  integer|array $max Maximum filesize
     * @param  integer       $max Maximum filesize
     * @return void
     */
    public function __construct($minwidth = 0, $minheight = 0, $maxwidth = null, $maxheight = null)
    {
        if (is_array($minwidth) === true) {
            if (isset($minwidth['maxheight']) === true) {
                $maxheight = $minwidth['maxheight'];
            }

            if (isset($minwidth['minheight']) === true) {
                $minheight = $minwidth['minheight'];
            }

            if (isset($minwidth['maxwidth']) === true) {
                $maxwidth = $minwidth['maxwidth'];
            }

            if (isset($minwidth['minwidth']) === true) {
                $minwidth = $minwidth['minwidth'];
            }

            if (isset($minwidth[0]) === true) {
                $maxheight = $minwidth[3];
                $maxwidth  = $minwidth[2];
                $minheight = $minwidth[1];
                $minwidth  = $minwidth[0];
            }
        }

        $this->setImageMin($minwidth, $minheight);
        $this->setImageMax($maxwidth, $maxheight);
    }

    /**
     * Returns the set minimum image sizes
     *
     * @return array
     */
    public function getImageMin()
    {
        return array($this->_minwidth, $this->_minheight);
    }

    /**
     * Returns the set maximum image sizes
     *
     * @return array
     */
    public function getImageMax()
    {
        return array($this->_maxwidth, $this->_maxheight);
    }

    /**
     * Returns the set image width sizes
     *
     * @return array
     */
    public function getImageWidth()
    {
        return array($this->_minwidth, $this->_maxwidth);
    }

    /**
     * Returns the set image height sizes
     *
     * @return array
     */
    public function getImageHeight()
    {
        return array($this->_minheight, $this->_maxheight);
    }

    /**
     * Sets the minimum image size
     *
     * @param  integer $minwidth            The minimum image width
     * @param  integer $minheight           The minimum image height
     * @throws Zend_Validate_Exception      When minwidth is greater than maxwidth
     * @throws Zend_Validate_Exception      When minheight is greater than maxheight
     * @return Zend_Validate_File_ImageSize Provides a fluent interface
     */
    public function setImageMin($minwidth, $minheight)
    {
        if (($this->_maxwidth !== null) and ($minwidth > $this->_maxwidth)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The minimum image width must be less than or equal to the "
                . " maximum image width, but {$minwidth} > {$this->_maxwidth}");
        }

        if (($this->_maxheight !== null) and ($minheight > $this->_maxheight)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The minimum image height must be less than or equal to the "
                . " maximum image height, but {$minheight} > {$this->_maxheight}");
        }

        $this->_minwidth  = max(0, (integer) $minwidth);
        $this->_minheight = max(0, (integer) $minheight);
        return $this;
    }

    /**
     * Sets the maximum image size
     *
     * @param  integer $maxwidth       The maximum image width
     * @param  integer $maxheight      The maximum image height
     * @throws Zend_Validate_Exception When maxwidth is smaller than minwidth
     * @throws Zend_Validate_Exception When maxheight is smaller than minheight
     * @return Zend_Validate_StringLength Provides a fluent interface
     */
    public function setImageMax($maxwidth, $maxheight)
    {
        if ($maxwidth === null) {
            $tempwidth = null;
        } else if (($this->_minwidth !== null) and ($maxwidth < $this->_minwidth)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The maximum image width must be greater than or equal to the "
                . "minimum image width, but {$maxwidth} < {$this->_minwidth}");
        } else {
            $tempwidth = (integer) $maxwidth;
        }

        if ($maxheight === null) {
            $tempheight = null;
        } else if (($this->_minheight !== null) and ($maxheight < $this->_minheight)) {
            require_once 'Zend/Validate/Exception.php';
            throw new Zend_Validate_Exception("The maximum image height must be greater than or equal to the "
                . "minimum image height, but {$maxheight} < {$this->_minwidth}");
        } else {
            $tempheight = (integer) $maxheight;
        }

        $this->_maxwidth  = $tempwidth;
        $this->_maxheight = $tempheight;
        return $this;
    }

    /**
     * Sets the mimimum and maximum image width
     *
     * @param  integer $minwidth            The minimum image width
     * @param  integer $maxwidth            The maximum image width
     * @return Zend_Validate_File_ImageSize Provides a fluent interface
     */
    public function setImageWidth($minwidth, $maxwidth)
    {
        $this->setImageMin($minwidth, $this->_minheight);
        $this->setImageMax($maxwidth, $this->_maxheight);
        return $this;
    }

    /**
     * Sets the mimimum and maximum image height
     *
     * @param  integer $minheight           The minimum image height
     * @param  integer $maxheight           The maximum image height
     * @return Zend_Validate_File_ImageSize Provides a fluent interface
     */
    public function setImageHeight($minheight, $maxheight)
    {
        $this->setImageMin($this->_minwidth, $minheight);
        $this->setImageMax($this->_maxwidth, $maxheight);
        return $this;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if the imagesize of $value is at least min and
     * not bigger than max
     *
     * @param  string $value Real file to check for image size
     * @param  array  $file  File data from Zend_File_Transfer
     * @return boolean
     */
    public function isValid($value, $file = null)
    {
        // Is file readable ?
        if (@is_readable($value) === false) {
            $this->_throw($file, self::NOT_READABLE);
            return false;
        }

        $size = @getimagesize($value);
        $this->_setValue($file);

        if (empty($size) or ($size[0] === 0) or ($size[1] === 0)) {
            $this->_throw($file, self::NOT_DETECTED);
            return false;
        }

        if ($size[0] < $this->_minwidth) {
            $this->_throw($file, self::WIDTH_TOO_SMALL);
        }

        if ($size[1] < $this->_minheight) {
            $this->_throw($file, self::HEIGHT_TOO_SMALL);
        }

        if (($this->_maxwidth !== null) and ($this->_maxwidth < $size[0])) {
            $this->_throw($file, self::WIDTH_TOO_BIG);
        }

        if (($this->_maxheight !== null) and ($this->_maxheight < $size[1])) {
            $this->_throw($file, self::HEIGHT_TOO_BIG);
        }

        if (count($this->_messages) > 0) {
            return false;
        } else {
            return true;
        }
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

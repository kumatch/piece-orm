<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-orm/
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/ORM/Error.php';
require_once 'PEAR.php';
require_once 'Cache/Lite/File.php';
require_once 'Piece/ORM/Context.php';
require_once 'Piece/ORM/Mapper.php';

if (version_compare(phpversion(), '5.0.0', '<')) {
    require_once 'spyc.php';
} else {
    require_once 'spyc.php5';
}

// {{{ GLOBALS

$GLOBALS['PIECE_ORM_Mapper_Instances'] = array();

// {{{ Piece_ORM_Mapper_Factory

/**
 * A factory class for creating mapper objects.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_Factory
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     * @static
     */

    // }}}
    // {{{ factory()

    /**
     * Creates a mapper object based on the given information.
     *
     * @param string $mapperName
     * @param string $configDirectory
     * @param string $cacheDirectory
     * @return mixed
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     * @throws PIECE_ORM_ERROR_INVALID_MAPPER
     */
    function &factory($mapperName, $configDirectory, $cacheDirectory)
    {
        $context = &Piece_ORM_Context::singleton();
        $mapperID = sha1($context->getDSN() . ".$mapperName");
        if (!array_key_exists($mapperID, $GLOBALS['PIECE_ORM_Mapper_Instances'])) {
            Piece_ORM_Mapper_Factory::_load($mapperID, $mapperName, $configDirectory, $cacheDirectory);
            if (Piece_ORM_Error::hasErrors('exception')) {
                $return = null;
                return $return;
            }

            $mapperClass = Piece_ORM_Mapper_Factory::_getMapperClass($mapperID);
            $mapper = &new $mapperClass();
            if (!is_subclass_of($mapper, 'Piece_ORM_Mapper')) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_MAPPER,
                                      "The mapper class for [ $mapperName ] is invalid."
                                      );
                $return = null;
                return $return;
            }

            $GLOBALS['PIECE_ORM_Mapper_Instances'][$mapperID] = &$mapper;
        }

        return $GLOBALS['PIECE_ORM_Mapper_Instances'][$mapperID];
    }

    // }}}
    // {{{ clearInstances()

    /**
     * Clears the mapper instances.
     */
    function clearInstances()
    {
        $GLOBALS['PIECE_ORM_Mapper_Instances'] = array();
    }

    /**#@-*/

    /**#@+
     * @access private
     * @static
     */

    // }}}
    // {{{ _getMapperSource()

    /**
     * Gets a mapper source by either generating from a configuration file or
     * getting from a cache.
     *
     * @param string $mapperID
     * @param string $configFile
     * @param string $cacheDirectory
     * @return string
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     */
    function _getMapperSource($mapperID, $configFile, $cacheDirectory)
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => "$cacheDirectory/",
                                            'masterFile' => $configFile,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $mapperSource = $cache->get($mapperID);
        if (PEAR::isError($mapperSource)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_CANNOT_READ,
                                  "Cannot read the mapper source file in the directory [ $cacheDirectory ]."
                                  );
            $return = null;
            return $return;
        }

        if (!$mapperSource) {
            $mapperSource = Piece_ORM_Mapper_Factory::_generateMapperSource($mapperID, $configFile);
            $result = $cache->save($mapperSource);
            if (PEAR::isError($result)) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_CANNOT_WRITE,
                                      "Cannot write the mapper source to the cache file in the directory [ $cacheDirectory ]."
                                      );
                $return = null;
                return $return;
            }
        }

        return $mapperSource;
    }

    // }}}
    // {{{ _generateMapperSource()

    /**
     * Generates a mapper source from the given configuration file.
     *
     * @param string $mapperID
     * @param string $configFile
     * @return string
     */
    function &_generateMapperSource($mapperID, $configFile)
    {
        $mapperSource = 'class ' . Piece_ORM_Mapper_Factory::_getMapperClass($mapperID) . ' extends Piece_ORM_Mapper {}';
        $yaml = Spyc::YAMLLoad($configFile);

        return $mapperSource;
    }

    // }}}
    // {{{ _loaded()

    /**
     * Returns whether the mapper class for a given mapper ID has already
     * been loaded or not.
     *
     * @param string $mapperID
     * @return boolean
     */
    function _loaded($mapperID)
    {
        $mapperClass = Piece_ORM_Mapper_Factory::_getMapperClass($mapperID);
        if (version_compare(phpversion(), '5.0.0', '<')) {
            return class_exists($mapperClass);
        } else {
            return class_exists($mapperClass, false);
        }
    }

    // }}}
    // {{{ _load()

    /**
     * Loads a mapper class based on the given information.
     *
     * @param string $mapperID
     * @param string $mapperName
     * @param string $configDirectory
     * @param string $cacheDirectory
     * @throws PIECE_ORM_ERROR_INVALID_OPERATION
     * @throws PIECE_ORM_ERROR_NOT_FOUND
     * @throws PIECE_ORM_ERROR_NOT_READABLE
     * @throws PIECE_ORM_ERROR_CANNOT_READ
     * @throws PIECE_ORM_ERROR_CANNOT_WRITE
     */
    function _load($mapperID, $mapperName, $configDirectory, $cacheDirectory)
    {
        if (Piece_ORM_Mapper_Factory::_loaded($mapperID)) {
            return;
        }

        if (is_null($configDirectory)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_OPERATION,
                                  'The configuration directory must be specified.'
                                  );
            return;
        }

        if (!file_exists($configDirectory)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The configuration directory [ $configDirectory ] not found."
                                  );
            return;
        }

        if (is_null($cacheDirectory)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_OPERATION,
                                  'The cache directory must be specified.'
                                  );
            return;
        }

        if (!file_exists($cacheDirectory)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The cache directory [ $cacheDirectory ] not found."
                                  );
            return;
        }

        if (!is_readable($cacheDirectory) || !is_writable($cacheDirectory)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_READABLE,
                                  "The cache directory [ $cacheDirectory ] was not readable or writable."
                                  );
            return;
        }

        $configFile = "$configDirectory/$mapperName.yaml";
        if (!file_exists($configFile)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The configuration file [ $configFile ] not found."
                                  );
            return;
        }

        if (!is_readable($configFile)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_READABLE,
                                  "The configuration file [ $configFile ] was not readable."
                                  );
            return;
        }

        $mapperSource = Piece_ORM_Mapper_Factory::_getMapperSource($mapperID, $configFile, $cacheDirectory);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        eval($mapperSource);

        if (!Piece_ORM_Mapper_Factory::_loaded($mapperID)) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_NOT_FOUND,
                                  "The mapper [ $mapperName ] not found."
                                  );
        }
    }

    // }}}
    // {{{ _getMapperClass()

    /**
     * Gets the class name for a given mapper ID.
     *
     * @param string $mapperID
     * @return string
     */
    function _getMapperClass($mapperID)
    {
        return "Piece_ORM_Mapper_$mapperID";
    }

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>

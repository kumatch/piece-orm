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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once realpath(dirname(__FILE__) . '/../../../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/ORM/Mapper/Factory.php';
require_once 'Piece/ORM/Error.php';
require_once 'Cache/Lite.php';
require_once 'Piece/ORM/Context.php';
require_once 'Piece/ORM/Config.php';

// {{{ Piece_ORM_Mapper_FactoryTestCase

/**
 * TestCase for Piece_ORM_Mapper_Factory
 *
 * @package    Piece_ORM
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_ORM_Mapper_FactoryTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_cacheDirectory;
    var $_oldCacheDirectory;
    var $_oldMetadataCacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $config = &new Piece_ORM_Config();
        $config->setDSN('piece', 'pgsql://piece:piece@pieceorm/piece');
        $config->setOptions('piece', array('debug' => 2, 'result_buffering' => false));
        $context = &Piece_ORM_Context::singleton();
        $context->setConfiguration($config);
        $context->setMapperConfigDirectory($this->_cacheDirectory);
        $context->setDatabase('piece');
        $this->_oldCacheDirectory = Piece_ORM_Mapper_Factory::setConfigDirectory($this->_cacheDirectory);
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_cacheDirectory);
        $this->_oldMetadataCacheDirectory = Piece_ORM_Metadata_Factory::setCacheDirectory($this->_cacheDirectory);
    }

    function tearDown()
    {
        Piece_ORM_Metadata_Factory::setCacheDirectory($this->_oldMetadataCacheDirectory);
        Piece_ORM_Metadata_Factory::clearInstances();
        Piece_ORM_Mapper_Factory::setCacheDirectory($this->_oldCacheDirectory);
        Piece_ORM_Mapper_Factory::setConfigDirectory($this->_oldCacheDirectory);
        Piece_ORM_Mapper_Factory::clearInstances();
        Piece_ORM_Context::clear();
        $cache = &new Cache_Lite(array('cacheDir' => "{$this->_cacheDirectory}/",
                                       'automaticSerialization' => true,
                                       'errorHandlingAPIBreak' => true)
                                 );
        $cache->clean();
        Piece_ORM_Error::clearErrors();
        Piece_ORM_Error::popCallback();
    }

    function testConfigurationDirectoryNotSpecified()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $oldConfigDirectory = Piece_ORM_Mapper_Factory::setConfigDirectory(null);

        Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);

        Piece_ORM_Mapper_Factory::setConfigDirectory($oldConfigDirectory);
        Piece_ORM_Error::popCallback();
    }

    function testConfigurationDirectoryNotFound()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $oldConfigDirectory = Piece_ORM_Mapper_Factory::setConfigDirectory(dirname(__FILE__) . '/foo');

        Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_NOT_FOUND, $error['code']);

        Piece_ORM_Mapper_Factory::setConfigDirectory($oldConfigDirectory);
        Piece_ORM_Error::popCallback();
    }

    function testCacheDirectoryNotSpecified()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $oldCacheDirectory = Piece_ORM_Mapper_Factory::setCacheDirectory(null);

        Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_INVALID_OPERATION, $error['code']);

        Piece_ORM_Mapper_Factory::setCacheDirectory($oldCacheDirectory);
        Piece_ORM_Error::popCallback();
    }

    function testCacheDirectoryNotFound()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $oldCacheDirectory = Piece_ORM_Mapper_Factory::setCacheDirectory(dirname(__FILE__) . '/foo');

        Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_NOT_FOUND, $error['code']);

        Piece_ORM_Mapper_Factory::setCacheDirectory($oldCacheDirectory);
        Piece_ORM_Error::popCallback();
    }

    function testConfigurationFileNotFound()
    {
        Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        Piece_ORM_Mapper_Factory::factory('Foo');

        $this->assertTrue(Piece_ORM_Error::hasErrors('exception'));

        $error = Piece_ORM_Error::pop();

        $this->assertEquals(PIECE_ORM_ERROR_NOT_FOUND, $error['code']);

        Piece_ORM_Error::popCallback();
    }

    function testFactory()
    {
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertTrue(is_subclass_of($mapper, 'Piece_ORM_Mapper_Common'));
    }

    function testInstanceCache()
    {
        $mapper1 = &Piece_ORM_Mapper_Factory::factory('Person');
        $mapper2 = &Piece_ORM_Mapper_Factory::factory('Person');

        $mapper1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $mapper1));
        $this->assertEquals('bar', $mapper1->foo);
        $this->assertTrue(array_key_exists('foo', $mapper2));
        $this->assertEquals('bar', $mapper2->foo);
    }

    function testSwitchDatabase()
    {
        $context = &Piece_ORM_Context::singleton();
        $config = &$context->getConfiguration();
        $config->setDSN('piece1', 'pgsql://piece:piece@pieceorm/piece');
        $config->setOptions('piece1', array('debug' => 0, 'result_buffering' => false));
        $mapper1 = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertEquals(2, $mapper1->_dbh->options['debug']);

        $mapper1->foo = 'bar';
        $context->setDatabase('piece1');
        $mapper2 = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertEquals(0, $mapper2->_dbh->options['debug']);
        $this->assertTrue(array_key_exists('foo', $mapper2));
        $this->assertEquals('bar', $mapper2->foo);
    }

    /**
     * @since Method available since Release 0.8.0
     */
    function testCacheIDsShouldUniqueInOneCacheDirectory()
    {
        $oldDirectory = getcwd();
        chdir("{$this->_cacheDirectory}/CacheIDsShouldUniqueInOneCacheDirectory1");
        Piece_ORM_Mapper_Factory::setConfigDirectory('.');
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertEquals(1, $this->_getCacheFileCount($this->_cacheDirectory) - 1);

        chdir("{$this->_cacheDirectory}/CacheIDsShouldUniqueInOneCacheDirectory2");
        Piece_ORM_Mapper_Factory::setConfigDirectory('.');
        $mapper = &Piece_ORM_Mapper_Factory::factory('Person');

        $this->assertEquals(2, $this->_getCacheFileCount($this->_cacheDirectory) - 1);

        chdir($oldDirectory);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**
     * @since Method available since Release 0.8.0
     */
    function _getCacheFileCount($directory)
    {
        $cacheFileCount = 0;
        if ($dh = opendir($directory)) {
            while (true) {
                $file = readdir($dh);
                if ($file === false) {
                    break;
                }

                if (filetype("$directory/$file") == 'file') {
                    if (preg_match('/^cache_.+/', $file)) {
                        ++$cacheFileCount;
                    }
                }
            }

            closedir($dh);
        }

        return $cacheFileCount;
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

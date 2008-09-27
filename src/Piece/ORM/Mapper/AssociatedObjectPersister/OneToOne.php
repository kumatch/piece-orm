<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.2.0
 */

namespace Piece::ORM::Mapper::AssociatedObjectPersister;

use Piece::ORM::Mapper::AssociatedObjectPersister::Common;
use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Inflector;

// {{{ Piece::ORM::Mapper::AssociatedObjectPersister::OneToOne

/**
 * An associated object persister for One-to-One relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class OneToOne extends Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ insert()

    /**
     * Inserts associated objects to a table.
     *
     * @param array  $relationship
     * @param string $mappedAs
     */
    public function insert(array $relationship, $mappedAs)
    {
        if (!property_exists($this->subject, $mappedAs)) {
            return;
        }

        if (!is_object($this->subject->$mappedAs)) {
            return;
        }

        $mapper = MapperFactory::factory($relationship['table']);

        $this->subject->{ $mappedAs }->{ Inflector::camelize($relationship['column'], true) } = $this->subject->{ Inflector::camelize($relationship['referencedColumn'], true) };
        $mapper->insert($this->subject->{ $mappedAs });
    }

    // }}}
    // {{{ update()

    /**
     * Updates associated objects in a table.
     *
     * @param array  $relationship
     * @param string $mappedAs
     */
    public function update(array $relationship, $mappedAs)
    {
        if (!property_exists($this->subject, $mappedAs)) {
            return;
        }

        if (!is_null($this->subject->$mappedAs) && !is_object($this->subject->$mappedAs)) {
            return;
        }

        $mapper = MapperFactory::factory($relationship['table']);

        $referencedColumnValue = $this->subject->{ Inflector::camelize($relationship['referencedColumn'], true) };
        $oldObject = $mapper->findWithQuery("SELECT * FROM {$relationship['table']} WHERE {$relationship['column']} = " . $mapper->quote($referencedColumnValue, $relationship['column']));

        if (is_null($oldObject)) {
            if (!is_null($this->subject->$mappedAs)) {
                $this->subject->$mappedAs->{ Inflector::camelize($relationship['column'], true) } = $referencedColumnValue;
                $mapper->insert($this->subject->$mappedAs);
            }
        } else {
            if (!is_null($this->subject->$mappedAs)) {
                $mapper->update($this->subject->$mappedAs);
            } else {
                $mapper->delete($oldObject);
            }
        }
    }

    // }}}
    // {{{ delete()

    /**
     * Removes associated objects from a table.
     *
     * @param array  $relationship
     * @param string $mappedAs
     */
    public function delete(array $relationship, $mappedAs)
    {
        if (!property_exists($this->subject, $mappedAs)) {
            return;
        }

        if (!is_null($this->subject->$mappedAs) && !is_object($this->subject->$mappedAs)) {
            return;
        }

        $mapper = MapperFactory::factory($relationship['table']);
        $mapper->delete($this->subject->$mappedAs);
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

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

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

namespace Piece::ORM::Mapper::ObjectPersister::AssociationPersisterStrategy;

use Piece::ORM::Mapper::MapperFactory;
use Piece::ORM::Inflector;
use Piece::ORM::Mapper::ObjectPersister::AssociationPersisterStrategy::AssociationPersisterStrategyInterface;
use Piece::ORM::Mapper::Association;

// {{{ Piece::ORM::Mapper::ObjectPersister::AssociationPersisterStrategy::ManyToMany

/**
 * An associated object persister for Many-to-Many associations.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ManyToMany implements AssociationPersisterStrategyInterface
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
     * @param Piece::ORM::Mapper::Association $association
     * @param mixed                           $subject
     */
    public function insert(Association $association, $subject)
    {
        $property = $association->getProperty();
        if (!property_exists($subject, $property)) {
            return;
        }

        if (!is_array($subject->$property)) {
            return;
        }

        $linkTable = $association->getLinkTable();
        $mapper = MapperFactory::factory($linkTable->getTable());

        $referencedColumnValue = $subject->{ Inflector::camelize($linkTable->getReferencedColumn(), true) };
        $object = $mapper->createObject();
        foreach ($subject->$property as $associatedObject) {
            $object->{ Inflector::camelize($linkTable->getColumn(), true) } =
                $referencedColumnValue;
            $object->{ Inflector::camelize($linkTable->getInverseColumn(), true) } =
                $associatedObject->{ Inflector::camelize($association->getColumn(), true) };
            $mapper->insert($object);
        }
    }

    // }}}
    // {{{ update()

    /**
     * Updates associated objects in a table.
     *
     * @param Piece::ORM::Mapper::Association $association
     * @param mixed                           $subject
     */
    public function update(Association $association, $subject)
    {
        $property = $association->getProperty();
        if (!property_exists($subject, $property)) {
            return;
        }

        if (!is_array($subject->$property)) {
            return;
        }

        $linkTable = $association->getLinkTable();
        $mapper = MapperFactory::factory($linkTable->getTable());

        $referencedColumnValue = $subject->{ Inflector::camelize($linkTable->getReferencedColumn(), true) };
        $mapper->executeQuery('DELETE FROM ' .
                              $linkTable->getTable() .
                              ' WHERE ' .
                              $linkTable->getColumn() .
                              ' = ' .
                              $mapper->quote($referencedColumnValue, $linkTable->getColumn()),
                              true
                              );

        $object = $mapper->createObject();
        foreach ($subject->$property as $associatedObject) {
            $object->{ Inflector::camelize($linkTable->getColumn(), true) } = $referencedColumnValue;
            $object->{ Inflector::camelize($linkTable->getInverseColumn(), true) } = $associatedObject->{ Inflector::camelize($association->getColumn(), true) };
            $mapper->insert($object);
        }
    }

    // }}}
    // {{{ delete()

    /**
     * Removes associated objects from a table.
     *
     * @param Piece::ORM::Mapper::Association $association
     * @param mixed                           $subject
     */
    public function delete(Association $association, $subject)
    {
        $linkTable = $association->getLinkTable();
        $property = Inflector::camelize($linkTable->getReferencedColumn(), true);
        if (!property_exists($subject, $property)) {
            return;
        }

        $mapper = MapperFactory::factory($linkTable->getTable());
        $mapper->executeQuery('DELETE FROM ' .
                              $linkTable->getTable() .
                              ' WHERE ' .
                              $linkTable->getColumn() .
                              ' = ' .
                              $mapper->quote($subject->$property, $linkTable->getColumn()),
                              true
                              );
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

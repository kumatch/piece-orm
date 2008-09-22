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

namespace Piece::ORM::Mapper::AssociatedObjectLoader;

use Piece::ORM::Mapper::AssociatedObjectLoader::Common;
use Piece::ORM::Mapper::Common as MapperCommon;
use Piece::ORM::Inflector;

// {{{ Piece::ORM::Mapper::AssociatedObjectLoader::ManyToMany

/**
 * An associated object loader for Many-to-Many relationships.
 *
 * @package    Piece_ORM
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.2.0
 */
class ManyToMany extends Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access protected
     */

    protected $defaultValueOfMappedAs = array();

    /**#@-*/

    /**#@+
     * @access private
     */

    private $_associations = array();
    private $_loadedRows = array();

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ addAssociation()

    /**
     * Adds an association about what an inverse side record is associated with
     * an owning side record.
     *
     * @param array                      $row
     * @param Piece::ORM::Mapper::Common $mapper
     * @param integer                    $relationshipIndex
     * @return boolean
     */
    public function addAssociation(array $row,
                                   MapperCommon $mapper,
                                   $relationshipIndex
                                   )
    {
        $metadata = $mapper->getMetadata();
        $primaryKey = $metadata->getPrimaryKey();
        $this->_associations[$relationshipIndex][ $row[$primaryKey] ][] = $row[ $this->getRelationshipKeyFieldNameInSecondaryQuery($this->relationships[$relationshipIndex]) ];

        if (@array_key_exists($row[$primaryKey], $this->_loadedRows[$relationshipIndex])) {
            return false;
        } else {
            @$this->_loadedRows[$relationshipIndex][ $row[$primaryKey] ] = true;
            unset($row[ $this->getRelationshipKeyFieldNameInSecondaryQuery($this->relationships[$relationshipIndex]) ]);
            return true;
        }
    }

    /**#@-*/

    /**#@+
     * @access protected
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ buildQuery()

    /**
     * Builds a query to get associated objects.
     *
     * @param integer $relationshipIndex
     * @return string
     */
    protected function buildQuery($relationshipIndex)
    {
        return "SELECT {$this->relationships[$relationshipIndex]['through']['table']}.{$this->relationships[$relationshipIndex]['through']['column']} AS " . $this->getRelationshipKeyFieldNameInSecondaryQuery($this->relationships[$relationshipIndex]) . ", {$this->relationships[$relationshipIndex]['table']}.* FROM {$this->relationships[$relationshipIndex]['table']}, {$this->relationships[$relationshipIndex]['through']['table']} WHERE {$this->relationships[$relationshipIndex]['through']['table']}.{$this->relationships[$relationshipIndex]['through']['column']} IN (" . implode(',', $this->relationshipKeys[$relationshipIndex]) . ") AND {$this->relationships[$relationshipIndex]['table']}.{$this->relationships[$relationshipIndex]['column']} = {$this->relationships[$relationshipIndex]['through']['table']}.{$this->relationships[$relationshipIndex]['through']['inverseColumn']}";
    }

    // }}}
    // {{{ getRelationshipKeyFieldNameInPrimaryQuery()

    /**
     * Gets the name of the relationship key field in the primary query.
     *
     * @param array $relationship
     * @return string
     */
    protected function getRelationshipKeyFieldNameInPrimaryQuery(array $relationship)
    {
        return $relationship['through']['referencedColumn'];
    }

    // }}}
    // {{{ getRelationshipKeyFieldNameInSecondaryQuery()

    /**
     * Gets the name of the relationship key field in the secondary query.
     *
     * @param array $relationship
     * @return string
     */
    protected function getRelationshipKeyFieldNameInSecondaryQuery(array $relationship)
    {
        return "__relationship_key_field";
    }

    // }}}
    // {{{ associateObject()

    /**
     * Associates an object which are loaded by the secondary query into objects which
     * are loaded by the primary query.
     *
     * @param stdClass                   $associatedObject
     * @param Piece::ORM::Mapper::Common $mapper
     * @param string                     $relationshipKeyPropertyName
     * @param integer                    $relationshipIndex
     */
    protected function associateObject($associatedObject,
                                       MapperCommon $mapper,
                                       $relationshipKeyPropertyName,
                                       $relationshipIndex
                                       )
    {
        $metadata = $mapper->getMetadata();
        $primaryKey = Inflector::camelize($metadata->getPrimaryKey(), true);

        for ($j = 0, $count = count($this->_associations[$relationshipIndex][ $associatedObject->$primaryKey ]); $j < $count; ++$j) {
            $this->objects[ $this->objectIndexes[$relationshipIndex][ $this->_associations[$relationshipIndex][ $associatedObject->$primaryKey ][$j] ] ]->{ $this->relationships[$relationshipIndex]['mappedAs'] }[] = $associatedObject;
        }
    }

    // }}}
    // {{{ getPreloadCallback()

    /**
     * Gets the preload callback for a loader.
     *
     * @return callback
     */
    protected function getPreloadCallback()
    {
        return array($this, 'addAssociation');
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

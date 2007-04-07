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
 * @since      File available since Release 0.2.0
 */

require_once 'Piece/ORM/Mapper/RelationshipType/Common.php';
require_once 'Piece/ORM/Error.php';
require_once 'Piece/ORM/Metadata/Factory.php';

// {{{ Piece_ORM_Mapper_RelationshipType_ManyToMany

/**
 * A driver class for Many-to-Many relationships.
 *
 * @package    Piece_ORM
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-orm/
 * @since      Class available since Release 0.2.0
 */
class Piece_ORM_Mapper_RelationshipType_ManyToMany extends Piece_ORM_Mapper_RelationshipType_Common
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
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _doNormalizeDefinition()

    /**
     * Normalizes a relationship definition with relationship type specific
     * behavior.
     *
     * @param array $relationship
     * @param Piece_ORM_Metadata &$metadata
     * @param Piece_ORM_Metadata &$relationshipMetadata
     * @return array
     * @throws PIECE_ORM_ERROR_INVALID_CONFIGURATION
     * @throws PIECE_ORM_ERROR_INVOCATION_FAILED
     */
    function _doNormalizeDefinitions($relationship, &$metadata, &$relationshipMetadata)
    {
        if (!array_key_exists('column', $relationship)) {
            if ($primaryKey = $relationshipMetadata->getPrimaryKey()) {
                $relationship['column'] = $primaryKey;
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required if the element [ column ] omit.'
                                      );
                return;
            }
        } 

        if (!$relationshipMetadata->hasField($relationship['column'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$relationship['column']} ] not found in the table [ " . $relationshipMetadata->getTableName() . ' ].'
                                  );
            return;
        }

        $relationship['referencedColumn'] = null;

        if (!array_key_exists('through', $relationship)) {
            $relationship['through'] = array();
        }

        if (!array_key_exists('table', $relationship['through'])) {
            $throughTableName1 = $metadata->getTableName() . "_{$relationship['table']}";
            $throughTableName2 = "{$relationship['table']}_" . $metadata->getTableName();
            foreach (array($throughTableName1, $throughTableName2) as $throughTableName) {
                Piece_ORM_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                $throughMetadata = &Piece_ORM_Metadata_Factory::factory($throughTableName);
                Piece_ORM_Error::popCallback();
                if (!Piece_ORM_Error::hasErrors('exception')) {
                    $relationship['through']['table'] = $throughTableName;
                    break;
                }

                Piece_ORM_Error::pop();
            }

            if (!$throughMetadata) {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      "One of [ $throughTableName1 ] or [ $throughTableName2 ] must exists in the database, if the element [ table ] in the element [ through ] omit."
                                      );
                return; 
            }
        }

        $throughMetadata = &Piece_ORM_Metadata_Factory::factory($relationship['through']['table']);
        if (Piece_ORM_Error::hasErrors('exception')) {
            return;
        }

        if (!array_key_exists('column', $relationship['through'])) {
            if ($primaryKey = $metadata->getPrimaryKey()) {
                $relationship['through']['column'] = $metadata->getTableName() . "_$primaryKey";
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ column ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$throughMetadata->hasField($relationship['through']['column'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$relationship['through']['column']} ] not found in the table [ " . $throughMetadata->getTableName() . ' ].'
                                  );
            return;
        }

        if (!array_key_exists('referencedColumn', $relationship['through'])) {
            if ($primaryKey = $metadata->getPrimaryKey()) {
                $relationship['through']['referencedColumn'] = $primaryKey;
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ referencedColumn ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$metadata->hasField($relationship['through']['referencedColumn'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$relationship['through']['referencedColumn']} ] not found in the table [ " . $metadata->getTableName() . ' ].'
                                  );
            return;
        }

        if (!array_key_exists('inverseColumn', $relationship['through'])) {
            if ($primaryKey = $relationshipMetadata->getPrimaryKey()) {
                $relationship['through']['inverseColumn'] = $relationshipMetadata->getTableName() . "_$primaryKey";
            } else {
                Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                      'A single primary key field is required, if the element [ column ] in the element [ through ] omit.'
                                      );
                return;
            }
        } 

        if (!$throughMetadata->hasField($relationship['through']['inverseColumn'])) {
            Piece_ORM_Error::push(PIECE_ORM_ERROR_INVALID_CONFIGURATION,
                                  "The field [ {$relationship['through']['inverseColumn']} ] not found in the table [ " . $throughMetadata->getTableName() . ' ].'
                                  );
            return;
        }

        return $relationship;
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

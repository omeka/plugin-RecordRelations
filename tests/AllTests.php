<?php
/**
 * @version $Id$
 * @copyright Center for History and New Media, 2007-2010
 * @license http://www.gnu.org/licenses/gpl-3.0.txt
 * @package Omeka
 */

/**
 * Test suite for Coins.
 *
 * @package Omeka
 * @copyright Center for History and New Media, 2007-2010
 */
class RecordRelations_AllTests extends PHPUnit_Framework_TestSuite
{
    public static function suite()
    {
        $suite = new RecordRelations_AllTests('RecordRelations Tests');
        $testCollector = new PHPUnit_Runner_IncludePathTestCollector(
          array(dirname(__FILE__) . '/cases')
        );
        $suite->addTestFiles($testCollector->collectTests());
        return $suite;
    }
}

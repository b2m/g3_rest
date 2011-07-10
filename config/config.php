<?php
/**
 * Gallery3 Rest Connect
 *
 * PHP version 5
 *
 * @category  Contao
 * @package   G3_Rest
 * @author    Benjamin Meier <gpl@code-meier.de>
 * @copyright 2011 Benjamin Meier
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 * @link      https://github.com/b2m/g3_rest
 */

if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Add InsertTag
 * @global array $GLOBALS['TL_HOOKS']['replaceInsertTags']['G3_Rest']['g3images']
 * @name $TL_HOOKS['replaceInsertTags']['G3_Rest']['g3images']
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('G3_Rest', 'g3images');

/**
 * Delete file in tmp folder
 * @global @array $GLOBALS['TL_CRON']['weekly']
 * @name $TL_CRON['weekly']
 */
$GLOBALS['TL_CRON']['weekly'][] = array('G3_Rest', 'purgeTempFolder');
?>

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
 * Heading for g3_rest settings
 * @global array $GLOBALS['TL_LANG']['tl_settings']['g3_rest_legend'] 
 * @name $TL_LANG['tl_settings']['g3_rest_legend']
 */
$GLOBALS['TL_LANG']['tl_settings']['g3_rest_legend'] = 'Gallery3 REST Settings';

/**
 * Description for setting field g3_rest_token
 * @global array $GLOBALS['TL_LANG']['tl_settings']['g3_rest_token_label'] 
 * @name $TL_LANG['tl_settings']['g3_rest_token_label']
 */
$GLOBALS['TL_LANG']['tl_settings']['g3_rest_url_label'] = array('REST URL', 'Complete REST Url with trailing slash: http://www.example.com/index.php/rest/ or http://www.example.com/rest/ if url rewriting is in use');

/**
 * Description for setting field g3_rest_token
 * @global array $GLOBALS['TL_LANG']['tl_settings']['g3_rest_token_label'] 
 * @name $TL_LANG['tl_settings']['g3_rest_token_label']
 */
$GLOBALS['TL_LANG']['tl_settings']['g3_rest_token_label'] = array('REST Token', 'The token access the REST service. It is recommended to create a special REST-User in Gallery3 and use the corresponding token!');
?>
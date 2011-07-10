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
 * Add palettes to tl_settings
 * @global array $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']
 * @name $palettes['default']
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default']
    = str_replace(
        ';{search_legend:hide}',
        ';{g3_rest_legend:hide},g3_rest_url,g3_rest_token,g3_rest_cache_time;{search_legend:hide}',
        $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']
    );

/**
 * Add url field to tl_settings
 * @global array $GLOBALS['TL_DCA']['tl_settings']['fields']['g3_rest_url']
 * @name $fields['g3_rest_url']
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['g3_rest_url']
    = array(
            'label'     => &$GLOBALS['TL_LANG']['tl_settings']['g3_rest_url_label'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array(
                                 'tl_class'        => 'w50',
                                 'rgxp'            => 'url',
                                 'decode Entities' => true,
                                 'nospace'         => true,
                                 'mandatory'       => true
                                 )
    );

/**
 * Add token field to tl_settings
 * @global array $GLOBALS['TL_DCA']['tl_settings']['fields']['g3_rest_token']
 * @name $fields['g3_rest_token']
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['g3_rest_token']
    = array(
           'label'     => &$GLOBALS['TL_LANG']['tl_settings']['g3_rest_token_label'],
           'exclude'   => true,
           'inputType' => 'text',
           'eval'      => array(
                                'tl_class'  => 'w50',
                                'rgxp'      => 'alnum',
                                'nospace'   => true,
                                'mandatory' => true
                                )
    );

/**
 * Add cache time field to tl_settings
 * @global array $GLOBALS['TL_DCA']['tl_settings']['fields']['g3_rest_cache_time']
 * @name $fields['g3_rest_cache_time']
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['g3_rest_cache_time']
    = array(
           'label'     => &$GLOBALS['TL_LANG']['tl_settings']['g3_rest_cache_time_label'],
           'exclude'   => true,
           'inputType' => 'text',
           'eval'      => array(
                                'tl_class'  => 'w50',
                                'rgxp'      => 'digit',
                                'nospace'   => true,
                                'mandatory' => true
                                )
    );

?>
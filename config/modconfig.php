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

// possible GET-params and allowed values
$GLOBALS['TL_G3'] = array();

// holds the tag
$GLOBALS['TL_G3']['tag']['val'] = null;
$GLOBALS['TL_G3']['tag']['test'] = 'readonly';

// the image width
$GLOBALS['TL_G3']['width']['val'] = null; // int
$GLOBALS['TL_G3']['width']['test'] = 'int';

// the image height
$GLOBALS['TL_G3']['height']['val'] = null; // int
$GLOBALS['TL_G3']['height']['test'] = 'int';

// the alternative text of the image
$GLOBALS['TL_G3']['alt']['val'] = 'g3Title'; // g3Title, g3Desc, custom text
$GLOBALS['TL_G3']['alt']['test'] = 'text';

// the image class
$GLOBALS['TL_G3']['class']['val'] = ''; // text
$GLOBALS['TL_G3']['class']['test'] = 'text';

// the rel attribute e.g. to trigger the lightbox
$GLOBALS['TL_G3']['rel']['val'] = ''; // text
$GLOBALS['TL_G3']['rel']['test'] = 'text';

// the class of the sorounding div container
$GLOBALS['TL_G3']['divclass']['val'] = ''; // text
$GLOBALS['TL_G3']['divclass']['test'] = 'text';

// the description text of the image, also used by the lightbox
$GLOBALS['TL_G3']['title']['val'] = 'g3Desc'; // g3Title, g3Desc, custom text
$GLOBALS['TL_G3']['title']['test'] = 'text';

/*
 * the image caption
 * none, g3Title, g3Desc, custom text
 */
$GLOBALS['TL_G3']['caption']['val'] = 'g3Title';
$GLOBALS['TL_G3']['caption']['test'] = 'text';

// the link the image is linked to
$GLOBALS['TL_G3']['link']['val'] = 'resize'; // none, resize, orig, site
$GLOBALS['TL_G3']['link']['test'] = array('none', 'resize', 'orig', 'site');

// the included image size
$GLOBALS['TL_G3']['include']['val'] = 'thumb'; // thumb, resize, orig
$GLOBALS['TL_G3']['include']['test'] = array('thumb', 'resize', 'orig');

// show link to the gallery page
$GLOBALS['TL_G3']['showlink']['val'] = 1; // 0, 1
$GLOBALS['TL_G3']['showlink']['test'] = array(0, 1);

// show linked tags
$GLOBALS['TL_G3']['showtags']['val'] = 1; // 0, 1
$GLOBALS['TL_G3']['showtags']['test'] = array(0, 1);

// the time the html should be cached
$GLOBALS['TL_G3']['cache_time']['val'] = 0;
$GLOBALS['TL_G3']['cache_time']['test'] = 'int'; // time in hours

// id or ids of gallery items
$GLOBALS['TL_G3']['id']['val'] = 1; // int or array of ints
$GLOBALS['TL_G3']['id']['test'] = 'int|array';

// the number of items to show, triggered by g3_rest
$GLOBALS['TL_G3']['count']['val'] = null; // int
$GLOBALS['TL_G3']['count']['test'] = 'int';

// the number of items to consider, triggered by gallery
$GLOBALS['TL_G3']['num']['val'] = null; // int
$GLOBALS['TL_G3']['num']['test'] = 'int';

// the number to start, triggered by gallery
$GLOBALS['TL_G3']['start']['val'] = null; // int
$GLOBALS['TL_G3']['start']['test'] = 'int';

// the scope to consider, triggered by gallery
$GLOBALS['TL_G3']['scope']['val'] = null; // direct, all
$GLOBALS['TL_G3']['scope']['test'] = array('direct', 'all');

// show random item, triggered by gallery
$GLOBALS['TL_G3']['random']['val'] = null; // 'true'
$GLOBALS['TL_G3']['random']['test'] = array('true');

// only consider items with the given name, triggered by gallery
$GLOBALS['TL_G3']['name']['val'] = null; // text
$GLOBALS['TL_G3']['name']['test'] = 'text';

// the type of items to consider, triggered by gallery
$GLOBALS['TL_G3']['type']['val'] = null; // album, photo, (video)
$GLOBALS['TL_G3']['type']['test'] = array('album', 'photo');
?>
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
 * Class G3_Rest
 *
 * @category  Contao
 * @package   G3_Rest
 * @author    Benjamin Meier <gpl@code-meier.de>
 * @copyright 2011 Benjamin Meier
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL
 * @link      https://github.com/b2m/g3_rest
 */
class G3_Rest extends Frontend
{
    /**
     * Calculate the new dimensions of the photo
     *
     * Calculates the dimensions of the photo
     * depending on the settings within gallery3
     * and the InsertTag.
     *
     * @param int   $h    original height of image
     * @param int   $w    original width of image
     * @param array $conf the parameter array
     *
     * @return array Array containing new (height, width)
     */
    private function _calDimensions($h, $w, $conf)
    {
        $r = 1;
        if ($conf['height']
            && $h > $conf['width']
        ) {
            $r = $conf['height']/$h;
        }
        if ($conf['width']
            && $r*$w > $conf['width']
        ) {
            $r = $conf['width']/$w;
        }
        return array(round($r*$h), round($r*$w));
    }

    /**
     * Check if InsertTag could be processed
     *
     * Check InsertTag and if it could be
     * processed extract parameters and
     * direct request to the corresponding method
     *
     * @param string $tag InsertTag with params
     *
     * @return string|boolean
     */
    public function g3images($tag)
    {
        $tagSplit = explode('::', $tag);

        // check if this is a tag for us
        if ($tagSplit[0] == 'g3_rest' || $tagSplit[0] == 'cache_g3_rest') {
            // need a second argument
            if (isset($tagSplit[1])) {
                // check if caching is allowed
                if ($tagSplit[0] == 'g3_rest'
                    && $GLOBALS['TL_CONFIG']['g3_rest_cache_time'] > 0
                ) {
                    // get path to language specific filename
                    $file = TL_ROOT.'/system/tmp/g3_rest/';
                    $file .= md5($GLOBALS['TL_LANGUAGE'].specialchars($tag)).'.txt';
                    // check if file exists
                    if (file_exists($file)) {
                        $html = file_get_contents($file);
                        // check if file is expired
                        preg_match('~<!--([0-9]*)-->~', $html, $matches);
                        if (intval($matches[1]) > time()) {
                            // remove timestamp from output
                            $html = str_replace($matches[0], '', $html);
                            return $html;
                        }
                    }
                }

                // get module config
                include_once TL_ROOT.'/system/modules/g3_rest/config/modconfig.php';

                // generate config for this insertTag
                $conf = array();
                foreach ($GLOBALS['TL_G3'] as $key => $val) {
                    $conf[$key] = $val['val'];
                }

                // set tag
                $conf['tag'] = specialchars('{{'.$tag.'}}');

                // parse arguments
                if (strpos($tagSplit[1], '?') !== false) {
                    $this->import('String');

                    $after = explode('?', urldecode($tagSplit[1]), 2);

                    $action = $after[0];

                    $params = $this->String->decodeEntities($after[1]);
                    $params = str_replace('[&]', '&', $params);
                    $paramarr = explode('&', $params);

                    foreach ($paramarr as $param) {
                        list($key, $value) = explode('=', $param);
                        if (array_key_exists($key, $conf)) {
                            $param = $this->checkParam(
                                $value,
                                $GLOBALS['TL_G3'][$key]['test']
                            );
                            if ($param || strlen($param)) {
                                $conf[$key] = $param;
                            }
                        } else {
                            $message = 'Unknown parameter "'.$key.'"';
                            $message .= ' in '.$conf['tag'];
                            $this->log(
                                $message,
                                __CLASS__.' '.__METHOD__,
                                TL_ERROR
                            );
                            return '';
                        }
                    }
                } else {
                    $this->log(
                        'No parameter after ? in '.$conf['tag'],
                        __CLASS__.' '.__METHOD__,
                        TL_ERROR
                    );
                    return '';
                }

                // go through different actions
                switch($action) {
                case 'item':
                    return $this->getItem($conf);
                    break;
                case 'items':
                    return $html = $this->getItems($conf);
                    break;
                case 'tag':
                    return $html = $this->getTagItems($conf, 'photos');
                    break;
                default:
                    $message = 'Unknown parameter "'.$action.'"';
                    $message .= ' after :: in '.$conf['tag'];
                    $this->log(
                        $message,
                        __CLASS__.' '.__METHOD__,
                        TL_ERROR
                    );
                    return '';
                    break;
                }
            } else {
                $this->log(
                    'No parameter after :: in '.$conf['tag'],
                    __CLASS__.' '.__METHOD__,
                    TL_ERROR
                );
                return '';
            }
        }
        return false;
    }

    /**
     * REST for a single item
     *
     * Generates and executes the REST Request for
     * a single item. Redirects the request to getItems()
     * if more than one id is given.
     *
     * @param array $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function getItem($conf)
    {
        if (is_array($conf['id'])) {
            return $this->getItems($conf);
        } else {
            // allowed params for REST request
            $use = array('start', 'num', 'scope', 'name', 'random', 'type');
            $url = $GLOBALS['TL_CONFIG']['g3_rest_url'].'item/'.$conf['id'];
            $url .= '?'.substr($this->reqParams($this->filterArray($use, $conf)), 1);

            // get REST response as json
            $item = $this->request($url, $conf);
            if (!is_object($item)) {
                return '';
            }
            // check if params are set so members are needed
            if ($item->entity->type=='album'
                && ($conf['name']!=null
                || $conf['random']!=null
                || $conf['count']!=null)
            ) {
                $c = count($item->members);
                if ($c > 0) {
                    // only one member
                    if ($c == 1 || $conf['count'] == 1) {
                        $new_item = $this->request($item->members[0], $conf);
                        if (!is_object($new_item)) {
                            return '';
                        }
                        return $this->processItem($new_item, $conf);
                    } else {
                        // several members so check how many
                        if ($conf['count']!=null
                            && $conf['count'] < $c
                        ) {
                            $c = $conf['count'];
                        }
                        $urls = array_slice($item->members, 0, $c);
                        $url = $GLOBALS['TL_CONFIG']['g3_rest_url'].'items?';
                        $url .= 'urls=["'.implode('","', $urls).'"]';
                        $items = $this->request($url, $conf);
                        if (!is_object($items) && !is_array($items)) {
                            return '';
                        }
                        $c = count($items);
                        if ($c == 1) {
                            return $this->processItem($items[0], $conf);
                        } else if ($c > 1) {
                            // set caption to album title or description
                            switch ($conf['caption']) {
                            case 'g3Title':
                                $conf['caption'] = $item->entity->title;
                                break;
                            case 'g3Desc':
                                $conf['caption'] = $item->entity->description;
                                break;
                            default:
                                break;
                            }
                            return $this->processItems($items, $conf);
                        }
                    }
                }
            } else {
                if ($conf['type'] == null
                    || $conf['type'] == $item->entity->type
                ) {
                    return $this->processItem($item, $conf);
                }
            }
        }
        $this->log(
            'No Items found for '.$conf['tag'].' ('.$url.')',
            __CLASS__.' '.__METHOD__,
            TL_ERROR
        );
        return '';
    }

    /**
     * REST for several items
     *
     * Generates and executes the REST Request for
     * several items. Redirects the request to getItem()
     * if only one id is given.
     *
     * @param array $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function getItems($conf)
    {
        if (!is_array($conf['id'])) {
            return $this->getItem($conf);
        } else if (count($conf['id']) == 1) {
            $conf['id'] = $conf['id'][0];
            return $this->getItem($conf);
        } else if (count($conf['id']) > 1) {
            // allowed params for REST request
            $use = array('start', 'num');
            $url = $GLOBALS['TL_CONFIG']['g3_rest_url'].'items?';
            $url .= 'urls=["'.$GLOBALS['TL_CONFIG']['g3_rest_url'].'item/';
            $i = '","'.$GLOBALS['TL_CONFIG']['g3_rest_url'].'item/';
            $url .= implode($i, $conf['id']).'"]';
            $url .= $this->reqParams($this->filterArray($use, $conf));

            $items = $this->request($url, $conf);
            if (!is_object($items) && !is_array($items)) {
                return '';
            } else {
                $check = ($conf['type'] != null) ? $conf['type'] : false;
                $allowed = ($conf['count']!=null) ? $conf['count'] : 100;
                $c = 0;
                foreach ($items as $item) {
                    if (!$check || $item->entity->type == $check) {
                        $c++;
                        $process[] = $item;
                        if ($c>=$allowed) {
                            break;
                        }
                    }
                }

                $cp = count($process);
                if ($cp == 1) {
                    return $this->processItem($process[0], $conf);
                } else if ($cp > 1) {
                    return $this->processItems($process, $conf);
                }
            }
        }
        $this->log(
            'No Items found for '.$conf['tag'].' ('.$url.')',
            __CLASS__.' '.__METHOD__,
            TL_ERROR
        );
        return '';
    }

    /**
     * REST for items to corresponding tag
     *
     * Generates and executes the REST Request for
     * all the items connected to the given tag id.
     * Redirect the request with the new ids to getItems()
     *
     * @param array $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function getTagItems($conf)
    {
        if (is_array($conf['id'])) {
            $this->log(
                'Only one id allowed in '.$conf['tag'],
                __CLASS__.' '.__METHOD__,
                TL_ERROR
            );
            return '';
        }

        // allowed params for REST request
        $use = array('start', 'num');

        $url = $GLOBALS['TL_CONFIG']['g3_rest_url'].'tag/'.$conf['id'];
        $url .= $this->reqParams($this->filterArray($use, $conf));

        // get REST response as json
        $tag = $this->request($url, $conf);
        if (!is_object($tag)) {
            return '';
        }
        $items = array();
        foreach ($tag->relationships->items->members as $item) {
            $items[] = substr(strchr($item, ','), 1);
        }
        $conf['id'] = $items;
        return $this->getItems($conf);
    }

    /**
     * Generates a caption
     *
     * Generates a caption depending on the
     * values in the parameter array.
     *
     * @param object $item The item data as JSON object
     * @param array  $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function processCaption($item, $conf)
    {
        // setting image caption title
        switch ($conf['caption']) {
        case 'none':
            return '';
            break;
        case 'g3Title':
            $caption = $item->entity->title;
            break;
        case 'g3Desc':
            $caption = $item->entity->description;
            break;
        default:
            $caption = $conf['caption'];
            break;
        }

        $html = $this->getContainerOpenHTML('caption', 'caption');
        $html .= $caption."\n";
        $html .= $this->getContainerCloseHTML();
        return $html;
    }

    /**
     * Generates the image container
     *
     * Generates a html container depending on the
     * values in the parameter array.
     *
     * @param object $item The item data as JSON object
     * @param array  $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function processItem($item, $conf)
    {
        $html = '';
        // check wether countainer is needed
        if ($this->checkContainer($conf)) {
            $html .= $this->getContainerOpenHTML('image', $conf['divclass']);
        }

        // get photo html code
        $html .= $this->processPhoto($item, $conf);
        $html .= $this->processCaption($item, $conf);
        $members = $item->relationships->tags->members;
        // check wether links to the tags should be shown
        if ($conf['showtags'] == 1
            && count($members) > 0
        ) {
            $html .= $this->processTags($members, $item->entity->id, $conf);
        }
        // check wether a link to the gallery should be shown
        if ($conf['showlink'] == 1) {
            $html .= $this->processLink($item, $conf);
        }
        // close container
        if ($this->checkContainer($conf)) {
            $html .= $this->getContainerCloseHTML();
        }
        $this->writeCache($html, $conf);
        return $html;
    }

    /**
     * Generates a container for several images
     *
     * Generates a html container depending on the
     * values in the parameter array.
     *
     * @param object $items The item data as JSON object
     * @param array  $conf  The parameter array
     *
     * @return string Resulting html code.
     */
    protected function processItems($items, $conf)
    {
        // correct some parameters
        if ($conf['caption'] == 'g3Title'
            || $conf['caption'] == 'g3Desc'
        ) {
            $conf['caption'] = 'none';
        }

        if ($conf['alt'] != 'g3Title'
            && $conf['alt'] != 'g3Des'
        ) {
            $conf['alt'] = 'g3Title';
        }

        if ($conf['title'] != 'g3Title'
            && $conf['title'] != 'g3Desc'
        ) {
            $conf['title'] = 'g3Desc';
        }

        $ids = array();
        $tags = array();
        $html = $this->getContainerOpenHTML('image', $conf['divclass']);
        foreach ($items as $item) {
            $members = $item->relationships->tags->members;
            if (count($members)>0) {
                $tags = array_merge($tags, $members);
                $ids[] = $item->entity->id;
            }
            $html .= $this->processPhoto($item, $conf);
        }
        $html .= $this->processCaption(null, $conf);
        // check wether links to the tags should be shown
        if ($conf['showtags'] == 1 && count($tags) > 0) {
            $html .= $this->processTags($tags, $ids, $conf);
        }
        $html .= $this->getContainerCloseHTML();
        $this->writeCache($html, $conf);
        return $html;
    }

    /**
     * Processes data for a link to the Gallery
     *
     * Processes data for a html link to the Gallery
     * depending on values in the parameter array.
     *
     * @param object $item The item data as JSON object
     * @param array  $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function processLink($item, $conf)
    {
        return $this->getLinkHTML($item->entity->web_url, $item->entity->title);
    }

    /**
     * Processes data for a (linked) image
     *
     * Processes data for a image (linked to  Gallery)
     * depending on values in the parameter array.
     *
     * @param object $item The item data as JSON object
     * @param array  $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function processPhoto($item, $conf)
    {
        if ($item->entity->type == 'album') {
            $check_include = 'thumb';
            $check_link = 'site';
        } else if ($item->entity->type == 'movie') {
            $check_include = 'thumb';
            if ($conf['link'] == 'resize') {
                $check_link = 'orig';
            } else {
                $check_link = $conf['link'];
            }
        } else {
            $check_include = $conf['include'];
            $check_link = $conf['link'];
        }

        // setting photo values like file, height and width
        switch ($check_include) {
        case 'resize':
            $f = $item->entity->resize_url_public;
            list($h, $w) = $this->_calDimensions(
                $item->entity->resize_height,
                $item->entity->resize_width,
                $conf
            );
            break;
        case 'orig':
            $f = $item->entity->file_url_public;
            list($h, $w) = $this->_calDimensions(
                $item->entity->orig_height,
                $item->entity->orig_width,
                $conf
            );
            break;
        case 'thumb':
            $f = $item->entity->thumb_url_public;
            list($h, $w) = $this->_calDimensions(
                $item->entity->thumb_height,
                $item->entity->thumb_width,
                $conf
            );
            break;
        }

        // setting alt
        switch ($conf['alt']) {
        case 'g3Title':
            $alt = $item->entity->title;
            break;
        case 'g3Desc':
            $alt = $item->entity->description;
            break;
        default:
            $alt = $conf['alt'];
            break;
        }

        // set alternative text to title if Description or user param is emty
        if (strlen($alt)==0) {
            $alt = $item->entity->title;
        }

        $cl = $conf['class'];
        $rel = $conf['rel'];

        // check wether photo should be linked
        if ($conf['link'] == 'none') {
            $html = $this->getImageHTML($f, $h, $w, $alt, $cl);
        } else {
            // setting link target
            switch ($check_link) {
            case 'orig':
                $link = $item->entity->file_url_public;
                break;
            case 'site':
                $link = $item->entity->web_url;
                break;
            case 'resize':
                $link = $item->entity->resize_url_public;
                break;
            }

            // setting link title
            switch ($conf['title']) {
            case 'none':
                $title = '';
                break;
            case 'g3Title':
                $title = $item->entity->title;
                break;
            case 'g3Desc':
                $title = $item->entity->description;
                break;
            default:
                $title = $conf['title'];
                break;
            }
            $html = $this->getImageHTML($f, $h, $w, $alt, $cl, $link, $title, $rel);
        }
        return $html;
    }

    /**
     * Processes data for linked tags
     *
     * Processes data for linked Tags depending
     * on values in the parameter array.
     *
     * @param array $tags The tags as REST links
     * @param array $ids  The item ids to replace
     * @param array $conf The parameter array
     *
     * @return string Resulting html code.
     */
    protected function processTags($tags, $ids, $conf)
    {
        $html = '';
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        if (count($tags)>0) {
            $html .= $this->getContainerOpenHTML('tags');
            $html .= $GLOBALS['TL_LANG']['MSC']['g3_rest_tag'];
            // get rid of relationships and double tags
            for ($i=0;$i<count($tags);$i++) {
                $tags[$i] = str_replace('_item', '', $tags[$i]);
                foreach ($ids as $id) {
                    $tags[$i] = str_replace(','.$id, '', $tags[$i]);
                }
            }
            $tags = array_unique($tags);
            // performs REST request and gets HTML code
            $config_url = $GLOBALS['TL_CONFIG']['g3_rest_url'];
            foreach ($tags as $tag) {
                $request = $this->request($tag, $conf);
                if (!is_object($request)) {
                    return '';
                }
                $name = $request->entity->name;
                $url = str_replace('rest/', 'tag/'.$name, $config_url);
                $html .= $this->getTagHTML($name, $url);
            }
            $html .= $this->getContainerCloseHTML();
        }
        return $html;
    }

    /**
     * Generates HTML for opening a container
     *
     * @param string $type  Type of container (css class)
     * @param string $class User css class
     *
     * @return string Resulting html code.
     */
    protected function getContainerOpenHTML($type, $class='')
    {
        $html ='<div class="'.$type.'_container';
        $html .= (strlen($class) ? ' '.$class : '').'">'."\n";
        return $html;
    }

    /**
     * Generates HTML for closing a container
     *
     * @return string Resulting html code.
     */
    protected function getContainerCloseHTML()
    {
        return '</div>'."\n";
    }

    /**
     * Generates HTML for a linked tag
     *
     * @param string $tag Name of tag
     * @param string $url Internet Adress of tag
     *
     * @return string Resulting html code.
     */
    protected function getTagHTML($tag, $url)
    {
        $html = '<a href="'.$url.'" title="';
        $html .= sprintf($GLOBALS['TL_LANG']['MSC']['g3_rest_tagged'], $tag).'"';
        $html .= LINK_NEW_WINDOW.'>';
        $html .= $tag.'</a>'."\n";
        return $html;
    }

    /**
     * Generates HTML for a link to Gallery
     *
     * @param string $link  Internet Adress
     * @param string $title Title for Link
     *
     * @return string Resulting html code.
     */
    protected function getLinkHTML($link, $title='')
    {
        $html = '<a href="'.$link.'"';
        $html .= (strlen($title)) ? ' title="'.$title.'"' : '';
        $html .= ' class="gallery_link"';
        $html .= LINK_NEW_WINDOW_BLUR.'>';
        $html .= $GLOBALS['TL_LANG']['MSC']['g3_rest_gallery'];
        $html .= '</a>'."\n";
        return $html;
    }

    /**
     * Generates HTML for a linked Image
     *
     * @param string $f   URL to image file
     * @param int    $h   Height of image
     * @param int    $w   Width of image
     * @param string $alt Alternative text for image
     * @param string $cl  CSS class for image
     * @param string $l   Link image to Gallery or bigger image
     * @param string $t   Title for link (also shown in lightbox)
     * @param string $rel Relative Attribute e.g. for triggering lightbox
     *
     * @return string Resulting html code.
     */
    protected function getImageHTML($f, $h, $w, $alt, $cl='', $l='', $t='', $rel='')
    {
        $html = '';
        if (strlen($l)) {
            $html .= '<a href="'.$l.'"';
            $html .= (strlen($t) ? ' title="'.$t.'"' : '');
            if (strlen($rel)) {
                $html .=  ' rel="'.$rel.'"';
            } else {
                $html .= LINK_NEW_WINDOW;
            }
                $html .= '>'."\n";
        }

        $html .= '<img src="'.$f.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"';
        $html .= (strlen($cl) ? ' class="'.$cl.'"' : '').' />'."\n";

        if (strlen($l)) {
            $html .= '</a>'."\n";
        }
        return $html;
    }

    /**
     * Checks whether a container around the image(s) is necesarry
     *
     * @param array $conf The parameter array
     *
     * @return boolean
     */
    protected function checkContainer($conf)
    {
        if ($conf['caption'] != 'none'
            || $conf['showlink'] == 1
            || $conf['showtags'] == 1
            || strlen($conf['divclass']) > 0
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Filters items in an array
     *
     * Filters all items in array $arr with a
     * key occuring as value in $keys
     *
     * @param array $keys Non assoziative array
     * @param array $arr  Assoziative array with key=>value pairs to filter
     *
     * @return array Filtered array
     */
    protected function filterArray($keys, $arr)
    {
        return array_intersect_key($arr, array_fill_keys($keys, null));
    }

    /**
     * Converts an array of params in a URL
     *
     * @param array $arr The Array convert
     *
     * @return string
     */
    protected function reqParams($arr)
    {
        $r = '';
        foreach ($arr as $k => $v) {
            if (strlen($v)) {
                $r .= '&'.$k.'='.$v;
            }
        }
        return $r;
    }

    /**
     * Checks and corrects user params
     *
     * @param string|array $param The parameter to check
     * @param string|array $check Allowed values
     *
     * @return string|array The corrected Params
     */
    protected function checkParam($param, $check)
    {
        $ret = null;
        switch ($check) {
        case 'readonly':
            $ret = false;
            break;
        case 'int':
            $ret = intval($param);
            break;
        case 'text':
            $ret = specialchars($param);
            break;
        case 'int|array':
            if (strpos($param, ',')!==false) {
                $ret = explode(',', $param);
                $ret = array_map('intval', $ret);
            } else {
                $ret = intval($param);
            }
            break;
        default:
            if (is_array($check) && in_array($param, $check)) {
                $ret = specialchars($param);
            } else if ($param == $check) {
                $ret = specialchars($param);
            }
            break;
        }
        return $ret;
    }

    /**
     * Writes html code to some cache files
     *
     * @param string $html The generated html to be written to cache file
     * @param array  $conf The parameter array
     *
     * @return boolean depending on writing success
     */
    protected function writeCache($html, $conf)
    {
        if (strlen($html) > 0) {
            $tag = str_replace(array('{{', '}}'), '', $conf['tag']);
            $tagSplit = explode('::', $tag);
            // check if caching is allowed
            if ($tagSplit[0] == 'g3_rest'
                && $GLOBALS['TL_CONFIG']['g3_rest_cache_time'] > 0
            ) {
                if ($conf['cache_time'] > 0) {
                    $cache_time = $conf['cache_time']*3600;
                } else {
                    $cache_time = $GLOBALS['TL_CONFIG']['g3_rest_cache_time']*3600;
                }
                // generate language specific filename
                $file = TL_ROOT.'/system/tmp/g3_rest/';
                $file .= md5($GLOBALS['TL_LANGUAGE'].$tag).'.txt';
                $html = '<!--'.(time()+$cache_time).'-->'."\n".$html;

                // check if file exists
                if (file_put_contents($file, $html) === false) {
                    $this->log(
                        'Could not write in ~/system/tmp/g3_rest/, cache disabled!',
                        __CLASS__.' '.__METHOD__,
                        TL_ERROR
                    );
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Sends REST request to Gallery
     *
     * @param string $url  The REST url
     * @param array  $conf The parameter array
     *
     * @return object JSON object
     */
    protected function request($url, $conf)
    {
        $token = $GLOBALS['TL_CONFIG']['g3_rest_token'];
        $req = new Request();
        $req->setHeader('X-Gallery-Request-Method', 'GET');
        $req->setHeader('X-Gallery-Request-Key', $token);
        $req->send($url);
        if ($req->code == 200 || $req->code == 201) {
            return json_decode($req->response);
        } else {
            $this->log(
                'Error '.$req->code.' for '.$url.' with '.$conf['tag'],
                __CLASS__.' '.__METHOD__,
                TL_ERROR
            );
            return '';
        }
    }

    /**
     * Deletes expired cache files via Contao Cron
     *
     * @return void
     */
    public function purgeTempFolder()
    {
        $path = 'system/tmp/g3_rest';

        // create folder if it does not exist
        if (!file_exists(TL_ROOT.'/'.$path)) {
            $this->import('Folder');
            new Folder($path);
            $this->log(
                'Tried to create ~/'.$path,
                __CLASS__.' '.__METHOD__,
                TL_CRON
            );
        }

        $files = scan(TL_ROOT.'/'.$path, true);

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!is_dir(TL_ROOT.'/'.$path.'/'.$file)) {
                    $filePointer = @fopen(TL_ROOT.'/'.$path.'/'.$file, 'r');
                    if ($filePointer && !feof($filePointer)) {
                        $line = fgets($filePointer, 4096);
                    } else {
                        $this->log(
                            'Could not read file ~/'.$path.'/'.$file.'!',
                            __CLASS__.' '.__METHOD__,
                            TL_ERROR
                        );

                    }
                    fclose($filePointer);
                    // check if file is expired
                    preg_match('~<!--([0-9]*)-->~', $line, $matches);
                    if (intval($matches[1]) < time()) {
                        @unlink(TL_ROOT.'/'.$path.'/'.$file);
                    }
                }
            }
        }
        $this->log(
            'Purged temporary g3_rest directory',
            __CLASS__.' '.__METHOD__,
            TL_CRON
        );
    }
}

?>
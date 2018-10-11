<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Document class
*/
class Document {
	private $title;
	private $description;
	private $keywords;
	private $links = array();
	private $styles = array();
	private $scripts = array();
	private $image;
	private $url;
	private $type;
	private $price;

	/**
     *
     *
     * @param	string	$title
     */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
     *
	 *
	 * @return	string
     */
	public function getTitle() {
		return $this->title;
	}

	/**
     *
     *
     * @param	string	$description
     */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
     *
     *
     * @param	string	$description
	 *
	 * @return	string
     */
	public function getDescription() {
		return $this->description;
	}

	/**
     *
     *
     * @param	string	$keywords
     */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
     *
	 *
	 * @return	string
     */
	public function getKeywords() {
		return $this->keywords;
	}

	/**
     *
     *
     * @param	string	$href
	 * @param	string	$rel
     */
	public function addLink($href, $rel) {
		$this->links[$href] = array(
			'href' => $href,
			'rel'  => $rel
		);
	}

	/**
     *
	 *
	 * @return	array
     */
	public function getLinks() {
		return $this->links;
	}

    /**
     *
     * @param    string $href
     * @param    string $rel
     * @param    string $media
     * @throws Exception
     */
    public function addStyle($href, $rel = 'stylesheet', $media = 'screen')
    {
        if (!$this->hasBaseUri($href)) {
            $href = url()->fullPathLink($href);
        }
        $this->styles[$href] = array(
            'href' => $href,
            'rel' => $rel,
            'media' => $media
        );
    }

	/**
     *
	 *
	 * @return	array
     */
	public function getStyles() {
		return $this->styles;
	}

    /**
     *
     * @param    string $href
     * @param    string $postion
     * @throws Exception
     */
    public function addScript($href, $postion = 'header')
    {
        if (!$this->hasBaseUri($href)) {
            $href = url()->fullPathLink($href);
        }
        $this->scripts[$postion][$href] = $href;
    }

	/**
     *
     *
     * @param	string	$postion
	 *
	 * @return	array
     */
	public function getScripts($postion = 'header') {
		if (isset($this->scripts[$postion])) {
			return $this->scripts[$postion];
		} else {
			return array();
		}
	}

	/**
		 *
		 *
		 * @param	string	$image
		 */
	public function setImage($image) {
		$this->image = $image;
	}

	/**
		 *
	 *
	 * @return	string
		 */
	public function getImage() {
		return $this->image;
	}


	/**
		 *
		 *
		 * @param	string	$url
		 */
	public function setUrl($url) {
		$this->url = $url;
	}

	/**
		 *
	 *
	 * @return	string
		 */
	public function getUrl() {
		return $this->url;
	}

	/**
		 *
		 *
		 * @param	string	$type
		 */
	public function setType($type) {


		$this->type = $type;
	}

	/**
		 *
	 *
	 * @return	string
		 */
	public function getType() {
		return $this->type;
	}

	/**
		 *
		 *
		 * @param	string	$price
		 */
	public function setPrice($price) {
		$this->price = $price;
	}

	/**
		 *
	 *
	 * @return	string
		 */
	public function getPrice() {
		return $this->price;
	}

    /**
     * @param $href
     * @return bool
     */
    private function hasBaseUri($href)
    {
        return strrpos($href, 'https') === 0
            || strrpos($href, 'http') === 0
            || strrpos($href, '//') === 0;
    }
}

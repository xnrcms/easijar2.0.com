<?php
/**
 * @package   OpenCart
 * @author    Daniel Kerr
 * @copyright Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license   https://opensource.org/licenses/GPL-3.0
 * @author    Daniel Kerr
 * @see       https://www.opencart.com
 */

/**
 * URL class.
 */
class Url {
	/** @var string */
	private $url;
	/** @var Controller[] */
	private $rewrite = array();

	/**
	 * Constructor.
	 *
	 * @param string $url
	 * @param string $ssl Unused
	 */
	public function __construct($url, $ssl = '') {
		$this->url = $url;
	}

	/**
	 *
	 *
	 * @param Controller $rewrite
	 *
	 * @return void
	 */
	public function addRewrite($rewrite) {
		$this->rewrite[] = $rewrite;
	}

	/**
	 *
	 *
	 * @param string          $route
	 * @param string|string[] $args
	 *
	 * @return string
	 */
	public function link($route, $args = '', $auto_admin_token = true) {
	    if ($route == 'common/home') {
	        return $this->url;
        }
		$url = $this->url . 'index.php?route=' . (string)$route;

        // Add user_token to admin link if it's not passed in
        if ($auto_admin_token && is_admin() && $user_token = array_get(session()->data, 'user_token')) {
            if (is_array($args) && !in_array('user_token', $args)) {
                $args['user_token'] = $user_token;
            } else if (!str_contains($args, 'user_token')) {
                $args .= '&user_token=' . $user_token;
            }
        }

		if ($args) {
			if (is_array($args)) {
				$url .= '&amp;' . http_build_query($args);
			} else {
				$url .= str_replace('&', '&amp;', '&' . ltrim($args, '&'));
			}
		}

		foreach ($this->rewrite as $rewrite) {
			$url = $rewrite->rewrite($url);
		}

		return $url;
	}

    public function imageLink($imagePath)
    {
        return $this->getBaseUrl() . 'image/' . $imagePath;
    }

    public function cssLink($cssPath)
    {
        return $this->getBaseUrl() . 'catalog/view/' . $cssPath;
    }

    public function jsLink($jsPath)
    {
        return $this->getBaseUrl() . 'catalog/view/' . $jsPath;
    }

    public function fullPathLink($path)
    {
        return $this->getBaseUrl() . $path;
    }

    public function getBaseUrl()
    {
        $cdnDomain = $this->getCdnDomain();
        if (!$cdnDomain) {
            return $this->url;
        }

        if ($this->isSecure()) {
            return 'https://' . $cdnDomain . '/';
        }
        return 'http://' . $cdnDomain . '/';
    }


    /**
     * Return CDN Domain, maybe we can get it from admin settings.
     *
     * @return string
     */
    private function getCdnDomain()
    {
        if (defined('CDN_DOMAIN') && CDN_DOMAIN) {
            return CDN_DOMAIN;
        }
        return '';
    }

    private function isSecure()
    {
        return stripos($this->url, 'https') !== false;
    }

    public function getQueries()
    {
        return $this->getQueriesExclude();
    }

    public function getQueriesExclude($queries = [])
    {
        $queries[] = 'route'; // No need to get route
        $results = [];
        foreach (request()->get as $key => $value) {
            if (in_array($key, $queries)) {
                continue;
            }
            if (!empty($value)) {
                $results[$key] = $value;
            }
        }
        return $results;
    }

    public function getQueriesOnly($queries = [])
    {
        $results = [];
        if (!$queries) {
            return $results;
        }

        foreach ($queries as $key) {
            if ($value = array_get(request()->get, $key)) {
                if (!empty($value)) {
                    $results[$key] = $value;
                }
            }
        }
        return $results;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: javierperezu
 */

namespace ryhor123\seotools;

use yii;
use yii\helpers\Json;
use ryhor123\seotools\models\Meta;

class Component extends \yii\base\Component
{
    /**
     * Default meta data values. These override any other metadata set
     * @var array
     */
    public $defaults = [
        'og:type' => 'website',
    ];

    /**
     * Component ID representing the database
     * @var string
     */
    public $db = 'db';

    /**
     * Component ID representing the cache
     * @var string
     */
    public $cache = 'cache';

    /**
     * The Component ID for this module (used to mark cache segments)
     * @var string
     */
    public $componentId = 'seotools';

    /**
     * After how long (seconds) will the routes caching expire.
     * @var int
     */
    public $cacheDuration = 3600;

    /**
     * host + path: used to identify pages
     * @var null
     */
    public $route = null;

    /**
     * chache tag dependency
     *
     * @var string
     */
    const CACHE_TAG = 'seotools';


    private $_info = '';


    /**
     * Devuelve la url absoluta con el path
     * @return string
     */
    public function getRoute() {
        if (is_null($this->route)) {
            $this->route = Yii::$app->request->getHostInfo() . '/' . Yii::$app->request->getPathInfo();
        }
        return $this->route;
    }


    /**
     * @param string $route
     * @return array
     */
    public function getMeta($route)
    {
        $cache = Yii::$app->{$this->cache};
        $cacheId = $this->componentId . '|routes|' . $route;
        $aMeta = $cache->get($cacheId);

        if ($aMeta) {
            return $aMeta;
        }

        $oMeta = new Meta();
        $oMeta->setRoute($route);

        $aMeta = [];
        $model = Meta::findOne([
            'hash' => $oMeta->hash
        ]);

        if (!empty($model)) {
            $info = $model->toArray();

            foreach ($info as $idData => $data) {
                if (!empty($data)) {
                    $aMeta[$idData] = $data;
                }
            }
        } else {
            // Si no existe la entrada con esa ruta la creamos
            $oMeta->save();
        }

        $oTagDependency = new \yii\caching\TagDependency(['tags' => self::CACHE_TAG ]);

        $cache->set($cacheId, $aMeta, $this->cacheDuration, $oTagDependency);

        return $aMeta;
    }

    /**
     * Register the robots meta
     * $index must be index or noindex or empty/null
     * $follow must be follow or nofollow or empty/null
     * @param string $index
     * @param string $follow
     */
    public function setRobots($index = '', $follow = '')
    {
        $v = [];

        if (!empty($index)) {
            $v[] = $index;
        }

        if (!empty($follow)) {
            $v[] = $follow;
        }

        if (!empty($v)) {
            Yii::$app->view->registerMetaTag(['name' => 'robots', 'content' => strtolower(implode(',', $v))], 'robots');
        }
        return $this;
    }

    /**
     * Register the author meta
     * @param string $author
     */
    public function setAuthor($author)
    {
        if (!empty($author)) {
            Yii::$app->view->registerMetaTag(['name' => 'author', 'content' => $author], 'author');
        }
        return $this;
    }

    /**
     * Register Open Graph Type meta
     * @param string $type
     */
    public function setOpenGraphType($type)
    {
        if (!empty($type)) {
            Yii::$app->view->registerMetaTag(['name' => 'og:type', 'content' => $type], 'og:type');
        }
        return $this;
    }

    /**
     * Register title meta and open graph title meta
     * @param string $title
     */
    public function setTitle($title)
    {
        if (!empty($title)) {
            Yii::$app->view->registerMetaTag(['name' => 'title', 'content' => $title], 'title');
            Yii::$app->view->registerMetaTag(['property' => 'og:title', 'content' => $title], 'og:title');
            Yii::$app->view->title = $title;
        }
        return $this;
    }

    /**
     * Register description meta and open graph description meta
     * @param string $description
     */
    public function setDescription($description)
    {
        if (!empty($description)) {
            Yii::$app->view->registerMetaTag(['name' => 'description', 'content' => $description], 'description');
            Yii::$app->view->registerMetaTag(['property' => 'og:description', 'content' => $description], 'og:description');
        }
        return $this;
    }

    /**
     * Register Canonical url
     * @param string $url
     */
    public function setCanonical($url)
    {
        Yii::$app->view->registerLinkTag(['href' => $url, 'rel' => 'canonical'], 'canonical');
        return $this;
    }

    /**
     * Register Open Graph Page Url
     * @param string $url
     */
    public function setOpenGraphUrl($url)
    {
        Yii::$app->view->registerMetaTag(['property' => 'og:url', 'content' => $url], 'og:url');
        return $this;
    }

    /**
     * Register text associated to a Url
     * @param string $info
     */
    public function setInfotext($info)
    {
        $this->_info = $info;
        return $this;
    }


    public function getInfotext() {
        return $this->_info;
    }

    /**
     * @param array $metadata
     * @param bool $setCanonical true, try to create a canonical url and og url, action needs to have params
     * @param bool $checkDb try to get from DB params
     */
    public function setMeta($metadata = [], $setCanonical = false, $checkDb = false)
    {
        // Set to empty not given values
        $metadataReset = ['robots_index' => '', 'robots_follow' => '', 'author' => '',
            'title' => '', 'description' => '', 'info' => '', 'params_url' => ''];

        $metadata = array_merge($metadataReset, $metadata);

        if ($checkDb) {
            // Merge passed parameter meta with route meta
            $metadata = array_merge($metadata, $this->getMeta($this->getRoute()));
        }

        // Override meta with the defaults via merge
        $metadata = array_merge($metadata, $this->defaults);

        $this->setRobots($metadata['robots_index'], $metadata['robots_follow'])
            ->setAuthor($metadata['author'])
            ->setTitle($metadata['title'])
            ->setDescription($metadata['description'])
            ->setOpenGraphType($metadata['og:type'])
            ->setInfotext($metadata['info']);

        if ($setCanonical == true) {

            if (!isset($metadata['params'])) {
                $params = Yii::$app->controller->actionParams;
            } else {
                $params = $metadata['params'];
            }

            $params[0] = Yii::$app->controller->getRoute();

            $url = Yii::$app->getUrlManager()->createAbsoluteUrl($params);

            $this->setCanonical($url);
            $this->setOpenGraphUrl($url);
        }

    }
}
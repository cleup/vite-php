<?php

namespace Cleup\Foundation;

class Vite
{
    /**
     * Development Host
     * @var string
     */
    protected $host = '';

    /**
     * If the Vite module is initiated
     * @var bool
     */
    protected $isModuleLoaded = false;

    /**
     * Manifest file
     * @var string
     */
    protected $manifestFile = 'manifest.json';

    /**
     * Data from the manifest.json file
     * @var array
     */
    protected $manifestData = array();

    /**
     * Configurations
     * 
     * @var array
     */
    private $config = array();

    /**
     * Initialization
     * 
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->config = array(
            'dev' => false,
            'buildDir' => 'build',
        );

        $this->config = array_merge($this->config, $config);
    }

    /**
     * The working directory relative to the entry point
     * 
     * @param string $path
     * @return string
     */
    public function workPath($path = '')
    {
        return realpath(getcwd()) . ($path ? '/' . $path : '');
    }

    /**
     * If the file is a style file
     * 
     * @param string $path
     * @return string
     */
    protected function isStyle($path)
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $path) === 1;
    }

    /**
     * Determine if the HMR server is running.
     *
     * @return bool
     */
    public function isDev()
    {
        return is_file($this->hotFile()) || $this->config['dev'] === true;
    }

    /**
     * The build path
     * 
     * @return string
     */

    public function buildDir()
    {
        return '/' . (trim($this->config['buildDir'], '/')) . '/';
    }

    /**
     * Get a development host
     * 
     * @return string
     */
    public function getHost()
    {
        if ($this->host)
            return $this->host;

        if (!file_exists($this->hotFile()))
            return '';

        $this->host = $this->hotAsset('');

        return $this->host;
    }

    /**
     * Get the path to a given asset when running in HMR mode.
     * 
     * @return string
     */
    protected function hotAsset($asset)
    {
        return rtrim(
            file_get_contents(
                $this->hotFile()
            )
        ) . '/' . $asset;
    }

    /**
     * Get the Vite "hot" file path.
     * 
     * @return string
     */
    public function hotFile()
    {
        return $this->workPath('/hot');
    }

    /**
     * Get the data from the manifest.json file
     * 
     * @return array
     */
    public function getManifest()
    {
        if ($this->manifestData)
            return $this->manifestData;

        $manifest =  $this->workPath(
            $this->buildDir() . '/' . $this->manifestFile
        );

        if (!file_exists($manifest))
            return array();

        $this->manifestData = json_decode(
            file_get_contents(
                $manifest
            ),
            true
        );

        return $this->manifestData;
    }

    /**
     * Start the Vite module
     * 
     * @return string
     */
    private function viteModule()
    {
        if (!$this->isDev())
            return '';

        if ($this->isModuleLoaded)
            return '';

        $this->isModuleLoaded = true;

        return $this->makeScriptTag('@vite/client');
    }

    /**
     * Obtaining a URL depending on the activity status in development mode
     * 
     * @param string $url
     * @return string
     */
    public function getUrl($url = '')
    {
        return $this->isDev() ?
            $this->getHost() . $url :
            $this->buildDir() . $url;
    }

    /**
     * Generate a script tag with attributes for the given URL.
     *
     * @param  string  $url
     * @param  array   $attributes
     * @return string
     */
    private function makeScriptTag($url, $attributes = array())
    {
        $attributes = array_merge([
            'type' => 'module'
        ], $attributes);

        $attributes['src'] = $this->getUrl($url);
        $attributes = $this->htmlAttributes($attributes);

        return "<script {$attributes}></script>\n";
    }

    /**
     * Generate a link tag with attributes for the given URL.
     *
     * @param  string  $url
     * @param  array   $attributes
     * @return string
     */
    protected function makeStylesheetTag($url, $attributes = array())
    {
        $attributes = array_merge([
            'rel' => 'stylesheet',
            'type' => 'text/css'
        ], $attributes);

        $attributes['href'] = $this->getUrl($url);
        $attributes = $this->htmlAttributes($attributes);

        return "<link {$attributes} />\n";
    }

    /**
     * Use
     * 
     * @param string|array $entry
     * @param array $attributes
     * @return string
     */
    public function use($entry, $attributes = array())
    {
        $js = array();
        $css = array();
        $files = array();
        $result = '';

        if (is_string($entry))
            $files[] = $entry;
        else
            $files = $entry;

        if (!$this->isDev())
            $manifest = $this->getManifest();

        foreach ($files as $key => $value) {
            $isAssocAttr = is_array($value) && !array_is_list($value);
            $this->filter(
                $isAssocAttr ? $key : $value,
                $isAssocAttr ? $value : $attributes,
                $js,
                $css,
                $manifest ?? array()
            );
        }

        foreach ($js as $key => $value) {
            $result .= $this->makeScriptTag($key, $value);
        }

        if (!$this->isModuleLoaded)
            $result = $this->viteModule() . $result;

        foreach ($css as $key => $value) {
            $result = $this->makeStylesheetTag($key, $value) . $result;
        }

        return $result;
    }

    /**
     * File type filter based on development mode
     * 
     * @param string $name
     * @param array $attributes
     * @param array $js 
     * @param array $css
     * @param array $mainfest
     */
    private function filter($name, $attributes, &$js, &$css, $mainfest = array()): void
    {
        if ($this->isDev()) {
            if (!$this->isStyle($name))
                $this->filterByType($name, $attributes, $js, $css);
        } else {
            $this->extractCss($name, $attributes, $css, $mainfest);

            if (!empty($mainfest[$name]['file']))
                $this->filterByType($mainfest[$name]['file'], $attributes, $js, $css);
        }
    }

    /**
     * Extract css styles from the entry point
     * 
     * @param string $name
     * @param array $attributes
     * @param array $css 
     * @param array $manifest
     */
    private function extractCss($name, $attributes, &$css, $mainfest): void
    {
        if (!empty($mainfest[$name]['css'])) {
            foreach ($mainfest[$name]['css'] as $style) {
                $css[$style] = $attributes;
            }
        }
    }

    /**
     * Filter by file type
     * 
     * @param string $name
     * @param array $attributes
     * @param array $js 
     * @param array $css
     */
    private function filterByType($name, $attributes, &$js, &$css): void
    {
        if ($this->isStyle($name) && !$this->isDev())
            $css[$name] = $attributes;
        else
            $js[$name] = $attributes;
    }

    /**
     * Generate html attributes from an array
     * 
     * @param array $array
     * @return string
     */
    private function htmlAttributes($array = array())
    {
        if (empty($array))
            return '';

        $result = "";

        foreach ($array as $key =>  $value) {
            $result .= ' ' . $key . '="' . $value . '"';
        }

        return $result;
    }
}

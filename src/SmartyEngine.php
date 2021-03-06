<?php
/**
 * Created by PhpStorm.
 * User: liujun
 * Date: 2017/4/20
 * Time: 23:18
 */
namespace TJDS\Lib;

use Phalcon\DiInterface;
use Phalcon\Mvc\View\Engine;
use Phalcon\Mvc\View\EngineInterface;
use Phalcon\Mvc\ViewBaseInterface;

/**
 * Phalcon\Mvc\View\Engine\Smarty
 * Adapter to use Smarty library as templating engine
 */
class SmartyEngine extends Engine implements EngineInterface
{
    /**
     * @var \Smarty
     */
    protected $smarty;
    protected  $di;
    /**
     * {@inheritdoc}
     *
     * @param ViewBaseInterface $view
     * @param DiInterface       $di
     */
    public function __construct(ViewBaseInterface $view, DiInterface $di = null)
    {
        $this->smarty               = new \Smarty();
        $this->smarty->template_dir = '.';
        $this->smarty->compile_dir  = BASE_PATH . '/templates_c';
        $this->smarty->config_dir   = SMARTY_DIR . 'configs';
        $this->smarty->cache_dir    = BASE_PATH . '/cache';
        $this->smarty->caching      = false;
        $this->smarty->debugging    = true;
        $this->smarty ->left_delimiter= "{% ";
        $this->smarty ->right_delimiter= " %}";
        $this->di = $di;
        parent::__construct($view, $di);
        $tpl =  trim($this->di->getShared('tpl'));

        if( $tpl != "" && $tpl !== false) {
            $this->smarty->assign('data', $this->di->getShared('data'));
            $this->smarty->display($tpl);
        }
    }
    /**
     * {@inheritdoc}
     *
     * @param string  $path
     * @param array   $params
     * @param boolean $mustClean
     */
    public function render($path, $params, $mustClean = false)
    {
        if (!isset($params['content'])) {
            $params['content'] = $this->_view->getContent();
        }
        foreach ($params as $key => $value) {
            if (isset($params['_' . $key]) && $params['_' . $key] === true) {
                $this->smarty->assign($key, $value, true);
            } else {
                $this->smarty->assign($key, $value);
            }
        }
        $content = $this->smarty->fetch($path);
        if ($mustClean) {
            $this->_view->setContent($content);
        } else {
            echo $content;
        }
    }
    /**
     * Set Smarty's options
     *
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $k => $v) {
            $this->smarty->$k = $v;
        }
    }

    /**
     * Get Smarty object
     *
     * @return \Smarty
     */
    public function getSmarty()
    {
        return $this->smarty;
    }
}
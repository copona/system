<?php

namespace Copona\System\Library\Template;

final class PHP
{
    private $data = array();

    public function __construct($registry)
    {
        $this->config = $registry->get('config');
        //$this->db = $registry->get('db');
        //$this->request = $registry->get('request');
        $this->session = $registry->get('session');
        $this->request = $registry->get('request');
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function render($template)
    {
        $file = DIR_TEMPLATE . $template;

        if (is_file($file)) {
            extract($this->data);

            ob_start();

            require($file);

            return ob_get_clean();
        }

        trigger_error('Error: Could not load template ' . $file . '!');
        exit();
    }

}
<?php

namespace Solido\WP\Response;

class JSON extends \Solido\WP\Response
{
    public function __construct($response = null)
    {
        $this->data = $response;
    }

    public function process($app)
    {
        echo json_encode($this->data);
        exit;
    }
}

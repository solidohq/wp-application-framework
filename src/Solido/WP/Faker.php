<?php

namespace Solido\WP;

class Faker
{
    protected static $postDefaults = array(
        'ID' => -1,
        'post_author' => -1,
        'post_name' => 'unknown',
        'post_title' => 'Solido WP Router',
        'post_content' => '',
        'post_status' => 'static',
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'comment_count' => 0,
    );

    public static function post($postarr = array())
    {
        $post = new \StdClass();

        $_postarr = array_merge(self::$postDefaults, array(
            'post_date' => current_time('mysql'),
            'post_date_gmt' => current_time('mysql', 1),
        ));
        $_postarr = array_merge(self::$postDefaults, $postarr);

        foreach ($_postarr as $k => $v) {
            $post->$k = $v;
        }

        return $post;
    }
}

<?php

namespace WpStore\Api;

use WP_REST_Response;

class CaptchaController
{
    public function register_routes()
    {
        register_rest_route('wp-store/v1', '/captcha/new', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'new_captcha'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    private function random_code($length = 5)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $idx = wp_rand(0, strlen($chars) - 1);
            $code .= $chars[$idx];
        }
        return $code;
    }

    private function svg_image($code)
    {
        $w = 160;
        $h = 48;
        $bg = '#f3f4f6';
        $fg = '#111827';
        $noise = '';
        for ($i = 0; $i < 6; $i++) {
            $x1 = wp_rand(0, $w);
            $y1 = wp_rand(0, $h);
            $x2 = wp_rand(0, $w);
            $y2 = wp_rand(0, $h);
            $c = sprintf('#%06X', wp_rand(0, 0xFFFFFF));
            $noise .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $c . '" stroke-width="1" opacity="0.3"/>';
        }
        $letters = str_split($code);
        $text = '';
        $x = 14;
        foreach ($letters as $i => $ch) {
            $rotate = wp_rand(-15, 15);
            $y = 30 + wp_rand(-5, 5);
            $color = $fg;
            $text .= '<text x="' . $x . '" y="' . $y . '" fill="' . $color . '" font-family="Arial, sans-serif" font-size="24" transform="rotate(' . $rotate . ' ' . $x . ',' . $y . ')">' . esc_html($ch) . '</text>';
            $x += 26;
        }
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '"><rect width="100%" height="100%" fill="' . $bg . '"/>' . $noise . $text . '</svg>';
        return $svg;
    }

    public function new_captcha()
    {
        $code = $this->random_code(5);
        $id = wp_generate_uuid4();
        set_transient('wp_store_captcha_' . $id, $code, 10 * MINUTE_IN_SECONDS);
        $svg = $this->svg_image($code);
        return new WP_REST_Response([
            'success' => true,
            'id' => $id,
            'svg' => $svg
        ], 200);
    }
}

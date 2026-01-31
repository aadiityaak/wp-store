<?php

namespace WpStore\Frontend;

class Template
{
    public static function render(string $template, array $data = []): string
    {
        $path = WP_STORE_PATH . 'templates/frontend/' . str_replace(['\\', '..'], ['/', ''], $template) . '.php';
        if (!file_exists($path)) {
            return '';
        }
        if (!empty($data)) {
            extract($data, EXTR_SKIP);
        }
        ob_start();
        require $path;
        $html = ob_get_clean();
        return self::processComponents($html);
    }

    public static function processComponents(string $html): string
    {
        if ($html === '') {
            return '';
        }
        $html = preg_replace_callback('/<\\s*wps-icon\\b([^>]*)\\/?\\s*>/i', function ($m) {
            $attrString = isset($m[1]) ? $m[1] : '';
            $attrs = [];
            if ($attrString !== '') {
                if (preg_match_all('/([a-zA-Z][\\w-]*)\\s*=\\s*(?:"([^"]*)"|\'([^\']*)\')/', $attrString, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $a) {
                        $key = strtolower($a[1]);
                        $val = $a[2] !== '' ? $a[2] : $a[3];
                        $attrs[$key] = $val;
                    }
                }
            }
            $data = [];
            if (isset($attrs['name'])) $data['name'] = $attrs['name'];
            if (isset($attrs['size'])) $data['size'] = (int) $attrs['size'];
            if (isset($attrs['class'])) $data['class'] = $attrs['class'];
            if (isset($attrs['stroke-color'])) $data['stroke_color'] = $attrs['stroke-color'];
            if (isset($attrs['stroke_color'])) $data['stroke_color'] = $attrs['stroke_color'];
            if (isset($attrs['stroke-width'])) $data['stroke_width'] = (int) $attrs['stroke-width'];
            if (isset($attrs['stroke_width'])) $data['stroke_width'] = (int) $attrs['stroke_width'];
            return self::render('components/icons', $data);
        }, $html);
        return $html;
    }
}

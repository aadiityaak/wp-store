<?php

namespace WpStore\Frontend;

class Component
{
    public static function icon($name = 'cart', $size = 24, $class = '', $stroke_width = 2)
    {
        $name = sanitize_key($name);
        $size = (int) $size;
        if ($size <= 0) $size = 24;
        $class = is_string($class) ? $class : '';
        $sw = (int) $stroke_width;
        if ($sw <= 0) $sw = 2;
        if ($name === 'cart') {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>';
        }
        return '';
    }
}

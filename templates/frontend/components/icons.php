<?php
$name = isset($name) ? sanitize_key($name) : 'cart';
$size = isset($size) ? (int) $size : 24;
if ($size <= 0) $size = 24;
$class = isset($class) && is_string($class) ? $class : '';
$sw = isset($stroke_width) ? (int) $stroke_width : 2;
if ($sw <= 0) $sw = 2;
$stroke = 'currentColor';
if (isset($stroke_color) && is_string($stroke_color)) {
    $hex = sanitize_hex_color($stroke_color);
    if ($hex) {
        $stroke = $hex;
    }
}
if ($name === 'cart') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>';
} elseif ($name === 'credit-card' || $name === 'creditcard') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"></rect><line x1="2" y1="8" x2="22" y2="8"></line><rect x="6" y="12" width="6" height="2" rx="1" ry="1"></rect></svg>';
} elseif ($name === 'user') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><path d="M20 21v-2a4 4 0 0 0-3-3.87"></path><path d="M4 21v-2a4 4 0 0 1 3-3.87"></path><circle cx="12" cy="7" r="4"></circle></svg>';
} elseif ($name === 'settings' || $name === 'gear') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.5 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.5-1H3a2 2 0 1 1 0 4h.09a1.65 1.65 0 0 0 1.5-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33 1.65 1.65 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.5 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82 1.65 1.65 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.5 1z"></path></svg>';
} elseif ($name === 'heart') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"></path></svg>';
} elseif ($name === 'close' || $name === 'x' || $name === 'remove') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
} elseif ($name === 'spinner') {
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="' . esc_attr($size) . '" height="' . esc_attr($size) . '" viewBox="0 0 24 24" fill="none" stroke="' . esc_attr($stroke) . '" stroke-width="' . esc_attr($sw) . '" stroke-linecap="round" stroke-linejoin="round" class="' . esc_attr($class) . '"><g><circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="40"></circle><animateTransform attributeName="transform" type="rotate" from="0 12 12" to="360 12 12" dur="0.9s" repeatCount="indefinite"/></g></svg>';
} else {
    echo '';
}

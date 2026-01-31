<div class="wps-p-4">
    <div class="wps-text-lg wps-font-medium wps-text-gray-900 wps-mb-4"><?php echo esc_html($title); ?></div>
    <div class="wps-flex wps-gap-4 wps-items-start">
        <div style="flex: 1;">
            <?php $image_src = (!empty($image) ? $image : (WP_STORE_URL . 'assets/frontend/img/noimg.webp')); ?>
            <?php
            $gallery_raw = get_post_meta((int) $id, '_store_gallery_ids', true);
            $settings_theme = get_option('wp_store_settings', []);
            $primary_color = isset($settings_theme['theme_primary']) ? sanitize_hex_color($settings_theme['theme_primary']) : '#2563eb';
            $items = [];
            $featured_thumb = get_the_post_thumbnail_url((int) $id, 'thumbnail');
            $featured_thumb = $featured_thumb ? $featured_thumb : $image_src;
            $items[] = [
                'full' => $image_src,
                'thumb' => $featured_thumb
            ];
            if (is_array($gallery_raw) && !empty($gallery_raw)) {
                foreach ($gallery_raw as $k => $v) {
                    $aid = is_numeric($k) ? (int) $k : 0;
                    $full = $aid ? (wp_get_attachment_image_url($aid, 'large') ?: (is_string($v) ? $v : '')) : (is_string($v) ? $v : '');
                    $thumb = $aid ? (wp_get_attachment_image_url($aid, 'thumbnail') ?: $full) : $full;
                    if ($full) {
                        $items[] = ['full' => $full, 'thumb' => $thumb];
                    }
                }
            }
            ?>
            <?php if (count($items) > 1) : ?>
                <div class="wps-flex wps-gap-2 wps-items-start">
                    <div id="wps-thumbs-<?php echo esc_attr($id); ?>" class="wps-flex wps-flex-col wps-gap-2" style="width:64px; max-height:320px; overflow-y:auto; overflow-x:hidden; position:relative;">
                        <?php foreach ($items as $idx => $gi) : ?>
                            <img src="<?php echo esc_url($gi['thumb']); ?>" alt="" class="wps-img-60 wps-rounded wps-gallery-thumb" style="border:1px solid #e5e7eb; cursor:pointer;" data-full="<?php echo esc_attr($gi['full']); ?>" data-idx="<?php echo esc_attr($idx); ?>">
                        <?php endforeach; ?>
                    </div>
                    <style>
                        #<?php echo 'wps-thumbs-' . esc_attr($id); ?>::-webkit-scrollbar {
                            width: 0;
                            height: 0
                        }

                        #<?php echo 'wps-thumbs-' . esc_attr($id); ?> {
                            scrollbar-width: none;
                            -ms-overflow-style: none
                        }
                    </style>
                    <div style="position:relative;display:block;flex:1;overflow:hidden;">
                        <img id="wps-main-img-<?php echo esc_attr($id); ?>" class="wps-w-full wps-rounded wps-img-320 wps-transition" src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($title); ?>">
                        <button type="button" class="wps-gallery-prev" style="position:absolute;left:8px;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:#111827cc;color:#fff;border:0;cursor:pointer;">
                            <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'chevron-left', 'size' => 16]); ?>
                        </button>
                        <button type="button" class="wps-gallery-next" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:9999px;background:#111827cc;color:#fff;border:0;cursor:pointer;">
                            <?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'chevron-right', 'size' => 16]); ?>
                        </button>
                        <?php
                        $ptype_single = get_post_meta((int) $id, '_store_product_type', true);
                        $is_digital_single = ($ptype_single === 'digital') || (bool) get_post_meta((int) $id, '_store_is_digital', true);
                        if ($is_digital_single) {
                            echo '<span class="wps-text-xs wps-text-white" style="position:absolute;top:8px;left:8px;display:flex;align-items:center;background:#111827cc;color:#fff;border-radius:9999px;padding:2px 6px;backdrop-filter:saturate(180%) blur(4px);">'
                                . \WpStore\Frontend\Template::render('components/icons', ['name' => 'cloud-download', 'size' => 12, 'stroke_color' => '#ffffff'])
                                . '<span style="color:#fff;font-size:10px;margin-left:4px;">Digital</span>'
                                . '</span>';
                        }
                        $lbl_single = get_post_meta((int) $id, '_store_label', true);
                        if (is_string($lbl_single) && $lbl_single !== '') {
                            $txt = $lbl_single === 'label-best' ? 'Best Seller' : ($lbl_single === 'label-limited' ? 'Limited' : ($lbl_single === 'label-new' ? 'New' : ''));
                            $bg  = $lbl_single === 'label-best' ? '#f59e0b' : ($lbl_single === 'label-limited' ? '#ef4444' : ($lbl_single === 'label-new' ? '#10b981' : '#374151'));
                            if ($txt !== '') {
                                echo '<span class="wps-text-xs" style="position:absolute;top:8px;right:8px;display:inline-flex;align-items:center;background:' . esc_attr($bg) . ';color:#fff;border-radius:9999px;padding:2px 6px;">'
                                    . '<?php echo wps_icon(['name' => 'heart', 'size' => 10, 'stroke_color' => '#ffffff']); ?>'
                                    . '<span style="color:#fff;font-size:10px;margin-left:4px;">' . esc_html($txt) . '</span>'
                                    . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <script>
                    (function() {
                        var container = document.currentScript.previousElementSibling;
                        var mainImg = container.querySelector('#<?php echo 'wps-main-img-' . esc_js($id); ?>');
                        var thumbs = container.querySelectorAll('img[data-full]');
                        var idx = 0;

                        function setActive(i) {
                            idx = i;
                            thumbs.forEach(function(t) {
                                t.style.border = '1px solid #e5e7eb';
                            });
                            var el = container.querySelector('img[data-idx="' + i + '"]');
                            if (el) {
                                el.style.border = '2px solid <?php echo esc_js($primary_color); ?>';
                            }
                        }
                        setActive(0);
                        var animating = false;

                        function slideTo(newSrc, direction) {
                            if (!mainImg) return;
                            if (animating) return;
                            animating = true;
                            var out = direction === 'left' ? '-100%' : '100%';
                            var inn = direction === 'left' ? '100%' : '-100%';
                            mainImg.style.transform = 'translateX(' + out + ')';
                            mainImg.style.opacity = '0';
                            var onOut = function() {
                                mainImg.removeEventListener('transitionend', onOut);
                                mainImg.setAttribute('src', newSrc);
                                mainImg.style.transform = 'translateX(' + inn + ')';
                                mainImg.style.opacity = '0';
                                requestAnimationFrame(function() {
                                    requestAnimationFrame(function() {
                                        mainImg.style.transform = 'translateX(0)';
                                        mainImg.style.opacity = '1';
                                    });
                                });
                                mainImg.addEventListener('transitionend', onIn);
                            };
                            var onIn = function() {
                                mainImg.removeEventListener('transitionend', onIn);
                                animating = false;
                            };
                            mainImg.addEventListener('transitionend', onOut);
                        }
                        thumbs.forEach(function(t) {
                            t.addEventListener('click', function() {
                                var full = this.getAttribute('data-full');
                                if (full) {
                                    var target = parseInt(this.getAttribute('data-idx'), 10);
                                    var direction = target > idx ? 'right' : 'left';
                                    setActive(target);
                                    slideTo(full, direction);
                                }
                            });
                        });
                        var prevBtn = container.querySelector('.wps-gallery-prev');
                        var nextBtn = container.querySelector('.wps-gallery-next');

                        function go(delta) {
                            var n = thumbs.length;
                            var next = (idx + delta + n) % n;
                            var el = container.querySelector('img[data-idx="' + next + '"]');
                            if (el) {
                                var full = el.getAttribute('data-full');
                                if (full) {
                                    var direction = delta > 0 ? 'right' : 'left';
                                    setActive(next);
                                    slideTo(full, direction);
                                }
                            }
                        }
                        if (prevBtn) prevBtn.addEventListener('click', function() {
                            go(-1);
                        });
                        if (nextBtn) nextBtn.addEventListener('click', function() {
                            go(1);
                        });
                        var thumbsWrap = container.querySelector('#<?php echo 'wps-thumbs-' . esc_js($id); ?>');
                        if (thumbsWrap) {
                            var track = document.createElement('div');
                            track.style.position = 'absolute';
                            track.style.top = '0';
                            track.style.right = '0';
                            track.style.width = '4px';
                            track.style.height = '100%';
                            track.style.background = '#e5e7eb';
                            track.style.borderRadius = '9999px';
                            track.style.zIndex = '2';
                            track.style.opacity = '0';
                            track.style.transition = 'opacity .15s ease';
                            track.style.pointerEvents = 'none';
                            var knob = document.createElement('div');
                            knob.style.position = 'absolute';
                            knob.style.top = '0';
                            knob.style.left = '0';
                            knob.style.width = '100%';
                            knob.style.background = '<?php echo esc_js($primary_color); ?>';
                            knob.style.borderRadius = '9999px';
                            knob.style.cursor = 'grab';
                            knob.style.opacity = '0';
                            knob.style.transition = 'opacity .15s ease';
                            track.appendChild(knob);
                            thumbsWrap.appendChild(track);

                            function updateScrollbar() {
                                var viewH = thumbsWrap.clientHeight;
                                var scrollH = thumbsWrap.scrollHeight;
                                if (scrollH <= viewH) {
                                    track.style.display = 'none';
                                    return;
                                }
                                track.style.display = 'block';
                                var ratio = viewH / scrollH;
                                var knobH = Math.max(20, Math.floor(viewH * ratio));
                                knob.style.height = knobH + 'px';
                                var maxScroll = scrollH - viewH;
                                var maxKnob = viewH - knobH;
                                var y = Math.floor((thumbsWrap.scrollTop / maxScroll) * maxKnob);
                                knob.style.transform = 'translateY(' + y + 'px)';
                            }
                            var dragging = false;
                            var startY = 0;
                            var startScroll = 0;
                            knob.addEventListener('mousedown', function(e) {
                                dragging = true;
                                startY = e.clientY;
                                startScroll = thumbsWrap.scrollTop;
                                knob.style.cursor = 'grabbing';
                                e.preventDefault();
                            });
                            document.addEventListener('mousemove', function(e) {
                                if (!dragging) return;
                                var viewH = thumbsWrap.clientHeight;
                                var scrollH = thumbsWrap.scrollHeight;
                                var knobH = knob.offsetHeight;
                                var maxScroll = scrollH - viewH;
                                var maxKnob = viewH - knobH;
                                var dy = e.clientY - startY;
                                var scrollDelta = (dy / maxKnob) * maxScroll;
                                var nextScroll = Math.max(0, Math.min(maxScroll, startScroll + scrollDelta));
                                thumbsWrap.scrollTop = nextScroll;
                                updateScrollbar();
                            });
                            document.addEventListener('mouseup', function() {
                                if (!dragging) return;
                                dragging = false;
                                knob.style.cursor = 'grab';
                            });
                            thumbsWrap.addEventListener('scroll', updateScrollbar);
                            window.addEventListener('resize', updateScrollbar);
                            updateScrollbar();
                            thumbs.forEach(function(img) {
                                if (img.complete) {
                                    updateScrollbar();
                                } else {
                                    img.addEventListener('load', updateScrollbar);
                                }
                            });

                            var hideTimer = null;

                            function showTrack() {
                                updateScrollbar();
                                if (track.style.display === 'none') return;
                                track.style.opacity = '0.6';
                                knob.style.opacity = '0.6';
                                track.style.pointerEvents = 'auto';
                                if (hideTimer) {
                                    clearTimeout(hideTimer);
                                    hideTimer = null;
                                }
                            }

                            function scheduleHide() {
                                if (dragging) return;
                                if (hideTimer) clearTimeout(hideTimer);
                                hideTimer = setTimeout(function() {
                                    track.style.opacity = '0';
                                    knob.style.opacity = '0';
                                    track.style.pointerEvents = 'none';
                                }, 500);
                            }
                            thumbsWrap.addEventListener('mouseenter', function() {
                                showTrack();
                            });
                            thumbsWrap.addEventListener('mousemove', function() {
                                showTrack();
                                scheduleHide();
                            });
                            thumbsWrap.addEventListener('mouseleave', function() {
                                scheduleHide();
                            });
                            container.addEventListener('mouseenter', function() {
                                showTrack();
                            });
                            container.addEventListener('mousemove', function() {
                                showTrack();
                                scheduleHide();
                            });
                            thumbsWrap.addEventListener('wheel', function() {
                                showTrack();
                                scheduleHide();
                            });
                            knob.addEventListener('mousedown', function() {
                                showTrack();
                                if (hideTimer) {
                                    clearTimeout(hideTimer);
                                    hideTimer = null;
                                }
                            });
                            document.addEventListener('mouseup', function() {
                                scheduleHide();
                            });
                        }
                    })();
                </script>
            <?php else : ?>
                <div style="position:relative;display:block;">
                    <img class="wps-w-full wps-rounded wps-img-320" src="<?php echo esc_url($image_src); ?>" alt="<?php echo esc_attr($title); ?>">
                    <?php
                    $ptype_single = get_post_meta((int) $id, '_store_product_type', true);
                    $is_digital_single = ($ptype_single === 'digital') || (bool) get_post_meta((int) $id, '_store_is_digital', true);
                    if ($is_digital_single) {
                        echo '<span class="wps-text-xs wps-text-white" style="position:absolute;top:8px;left:8px;display:flex;align-items:center;background:#111827cc;color:#fff;border-radius:9999px;padding:2px 6px;backdrop-filter:saturate(180%) blur(4px);">'
                            . \WpStore\Frontend\Template::render('components/icons', ['name' => 'cloud-download', 'size' => 12, 'stroke_color' => '#ffffff'])
                            . '<span style="color:#fff;font-size:10px;margin-left:4px;">Digital</span>'
                            . '</span>';
                    }
                    $lbl_single = get_post_meta((int) $id, '_store_label', true);
                    if (is_string($lbl_single) && $lbl_single !== '') {
                        $txt = $lbl_single === 'label-best' ? 'Best Seller' : ($lbl_single === 'label-limited' ? 'Limited' : ($lbl_single === 'label-new' ? 'New' : ''));
                        $bg  = $lbl_single === 'label-best' ? '#f59e0b' : ($lbl_single === 'label-limited' ? '#ef4444' : ($lbl_single === 'label-new' ? '#10b981' : '#374151'));
                        if ($txt !== '') {
                            echo '<span class="wps-text-xs" style="position:absolute;top:8px;right:8px;display:inline-flex;align-items:center;background:' . esc_attr($bg) . ';color:#fff;border-radius:9999px;padding:2px 6px;">'
                                . \WpStore\Frontend\Template::render('components/icons', ['name' => 'heart', 'size' => 10, 'stroke_color' => '#ffffff'])
                                . '<span style="color:#fff;font-size:10px;margin-left:4px;">' . esc_html($txt) . '</span>'
                                . '</span>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <div style="flex: 1;">
            <div class="wps-text-sm wps-text-gray-900 wps-mb-4">
                <?php if ($price !== null) : ?>
                    <?php echo esc_html(($currency ?? 'Rp') . ' ' . number_format_i18n((float) $price, 0)); ?>
                <?php endif; ?>
            </div>
            <div class="wps-mb-4">
                <?php
                $sku = get_post_meta($id, '_store_sku', true);
                $min_order = get_post_meta($id, '_store_min_order', true);
                $weight_kg = get_post_meta($id, '_store_weight_kg', true);
                $ptype = get_post_meta($id, '_store_product_type', true);
                $sale_price = get_post_meta($id, '_store_sale_price', true);
                $terms = get_the_terms($id, 'store_product_cat');
                $cats = [];
                if (is_array($terms)) {
                    foreach ($terms as $t) {
                        $cats[] = $t->name;
                    }
                }
                ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <tbody>
                        <?php if (is_string($sku) && $sku !== '') : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Kode Produk</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html($sku); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($weight_kg !== '' && $weight_kg !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Berat</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html(number_format_i18n((float) $weight_kg, 2)); ?> kg</td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($min_order !== '' && $min_order !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Minimal Order</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html((int) $min_order); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($stock !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Stok</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html((int) $stock); ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php if (is_string($ptype) && $ptype !== '') : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Tipe</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900">
                                    <?php echo esc_html($ptype === 'digital' ? 'Produk Digital' : 'Produk Fisik'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($sale_price !== '' && $sale_price !== null) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Harga Promo</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900">
                                    <?php echo esc_html(($currency ?? 'Rp') . ' ' . number_format_i18n((float) $sale_price, 0)); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if (!empty($cats)) : ?>
                            <tr style="border-top: 1px solid #e5e7eb;">
                                <td style="padding: 8px; color:#6b7280; font-size:12px;">Kategori</td>
                                <td style="padding: 8px; text-align: right;" class="wps-text-sm wps-text-gray-900"><?php echo esc_html(implode(', ', $cats)); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="wps-flex wps-gap-2 wps-items-center wps-mb-2">
                <div><?php echo do_shortcode('[wp_store_add_to_cart id="' . esc_attr($id) . '"]'); ?></div>
                <div><?php echo do_shortcode('[wp_store_add_to_wishlist id="' . esc_attr($id) . '"]'); ?></div>
            </div>
            <div class="wps-mb-4">
                <?php
                $share_link = get_permalink($id);
                $share_title = $title;
                $enc_url = rawurlencode($share_link);
                $enc_title = rawurlencode($share_title);
                $wa_url = 'https://api.whatsapp.com/send?text=' . rawurlencode($share_title . ' ' . $share_link);
                $x_url = 'https://twitter.com/intent/tweet?text=' . $enc_title . '&url=' . $enc_url;
                $fb_url = 'https://www.facebook.com/sharer/sharer.php?u=' . $enc_url;
                $mail_url = 'mailto:?subject=' . rawurlencode($share_title) . '&body=' . rawurlencode($share_link);
                ?>
                <div class="wps-flex wps-gap-2 wps-items-center">
                    <a href="<?php echo esc_url($wa_url); ?>" target="_blank" rel="noopener" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'whatsapp', 'size' => 18]); ?></a>
                    <a href="<?php echo esc_url($x_url); ?>" target="_blank" rel="noopener" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'x', 'size' => 18]); ?></a>
                    <a href="<?php echo esc_url($fb_url); ?>" target="_blank" rel="noopener" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'facebook', 'size' => 18]); ?></a>
                    <a href="<?php echo esc_url($mail_url); ?>" class="wps-btn wps-btn-secondary wps-btn-sm"><?php echo \WpStore\Frontend\Template::render('components/icons', ['name' => 'email', 'size' => 18]); ?></a>
                </div>
            </div>
            <div class="wps-text-sm wps-text-gray-500">
                <?php echo $content; ?>
            </div>
        </div>
    </div>
</div>

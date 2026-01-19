<?php
$uid = substr(md5(uniqid('', true)), 0, 8);
?>
<div id="wps-captcha-<?php echo esc_attr($uid); ?>" class="wps-mt-2">
    <div id="wps-captcha-image-<?php echo esc_attr($uid); ?>"></div>
    <div class="wps-flex wps-items-center wps-gap-2 wps-mt-2">
        <input type="text" name="captcha_value" class="wps-input" placeholder="Masukkan teks">
        <input type="hidden" name="captcha_id" id="wps-captcha-id-<?php echo esc_attr($uid); ?>" value="">
        <button type="button" class="wps-btn wps-btn-secondary wps-btn-sm" id="wps-captcha-refresh-<?php echo esc_attr($uid); ?>">Refresh</button>
    </div>
</div>
<script>
(function(){
    var img = document.getElementById('wps-captcha-image-<?php echo esc_js($uid); ?>');
    var idf = document.getElementById('wps-captcha-id-<?php echo esc_js($uid); ?>');
    var btn = document.getElementById('wps-captcha-refresh-<?php echo esc_js($uid); ?>');
    function load() {
        fetch('/wp-json/wp-store/v1/captcha/new').then(function(r){return r.json()}).then(function(j){
            if (j && j.success) {
                idf.value = j.id;
                img.innerHTML = j.svg;
            }
        });
    }
    document.addEventListener('DOMContentLoaded', load);
    if (btn) btn.addEventListener('click', load);
})();
</script>

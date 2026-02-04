<?php
$categories = isset($categories) && is_array($categories) ? $categories : [];
$current = isset($current) && is_array($current) ? $current : ['sort'=>'','min_price'=>'','max_price'=>'','cats'=>[],'labels'=>[]];
$reset_url = isset($reset_url) ? (string) $reset_url : '';
$show_labels = isset($show_labels) ? (bool) $show_labels : true;
?>
<form x-data="wpStoreFilters()" x-init="init()" @submit.prevent="update" method="get" action="" class="wps-card wps-p-4" style="margin-bottom:12px;">
  <div class="wps-text-lg wps-font-medium wps-mb-3 wps-text-bold">Filter & Urutkan</div>
  <div class="wps-grid wps-grid-cols-2 wps-gap-3">
    <div>
      <label class="wps-label">Urutkan</label>
      <select class="wps-select" name="sort" x-model="sort" @change="update">
        <option value="">Default</option>
        <option value="az" <?php echo $current['sort']==='az'?'selected':''; ?>>A → Z</option>
        <option value="za" <?php echo $current['sort']==='za'?'selected':''; ?>>Z → A</option>
        <option value="cheap" <?php echo $current['sort']==='cheap'?'selected':''; ?>>Termurah</option>
        <option value="expensive" <?php echo $current['sort']==='expensive'?'selected':''; ?>>Termahal</option>
      </select>
    </div>
    <div class="wps-grid wps-grid-cols-2 wps-gap-3">
      <div>
        <label class="wps-label">Harga Minimum</label>
        <input class="wps-input" type="number" min="0" step="1" name="min_price" x-model.number="min_price" @change="update" value="<?php echo esc_attr($current['min_price']); ?>">
      </div>
      <div>
        <label class="wps-label">Harga Maksimum</label>
        <input class="wps-input" type="number" min="0" step="1" name="max_price" x-model.number="max_price" @change="update" value="<?php echo esc_attr($current['max_price']); ?>">
      </div>
    </div>
  </div>
  <div class="wps-mt-3">
    <div class="wps-text-sm wps-text-gray-700 wps-mb-1">Kategori</div>
    <div class="wps-flex wps-flex-wrap" style="gap:8px;">
      <?php foreach ($categories as $cat): ?>
        <label class="wps-checkbox-label" style="display:inline-flex; align-items:center; gap:6px;">
          <input type="checkbox" class="wps-checkbox" name="cats[]" :value="<?php echo esc_attr($cat['id']); ?>" x-model="cats" @change="update" <?php echo in_array($cat['id'], $current['cats'], true) ? 'checked' : ''; ?>>
          <span class="wps-text-sm wps-text-gray-900"><?php echo esc_html($cat['name']); ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if ($show_labels): ?>
  <div class="wps-mt-3">
    <div class="wps-text-sm wps-text-gray-700 wps-mb-1">Label</div>
    <div class="wps-flex wps-flex-wrap" style="gap:8px;">
      <label class="wps-checkbox-label" style="display:inline-flex; align-items:center; gap:6px;">
        <input type="checkbox" class="wps-checkbox" name="labels[]" value="best" x-model="labels" @change="update" <?php echo in_array('best', $current['labels'], true) ? 'checked' : ''; ?>>
        <span class="wps-text-sm wps-text-gray-900">Best Seller</span>
      </label>
      <label class="wps-checkbox-label" style="display:inline-flex; align-items:center; gap:6px;">
        <input type="checkbox" class="wps-checkbox" name="labels[]" value="limited" x-model="labels" @change="update" <?php echo in_array('limited', $current['labels'], true) ? 'checked' : ''; ?>>
        <span class="wps-text-sm wps-text-gray-900">Limited</span>
      </label>
      <label class="wps-checkbox-label" style="display:inline-flex; align-items:center; gap:6px;">
        <input type="checkbox" class="wps-checkbox" name="labels[]" value="new" x-model="labels" @change="update" <?php echo in_array('new', $current['labels'], true) ? 'checked' : ''; ?>>
        <span class="wps-text-sm wps-text-gray-900">New</span>
      </label>
    </div>
  </div>
  <?php endif; ?>
  <div class="wps-mt-4 wps-flex wps-justify-between wps-items-center">
    <a href="<?php echo esc_url($reset_url); ?>" class="wps-btn wps-btn-secondary"><?php echo wps_icon(['name'=>'trash', 'size'=>16, 'class'=>'wps-mr-2']); ?>Reset</a>
    <button type="submit" class="wps-btn wps-btn-primary"><?php echo wps_icon(['name'=>'sliders2', 'size'=>16, 'class'=>'wps-mr-2']); ?>Terapkan</button>
  </div>
</form>
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('wpStoreFilters', () => ({
    sort: <?php echo wp_json_encode((string) ($current['sort'] ?? '')); ?>,
    min_price: <?php echo is_numeric($current['min_price'] ?? '') ? (float) $current['min_price'] : '""'; ?>,
    max_price: <?php echo is_numeric($current['max_price'] ?? '') ? (float) $current['max_price'] : '""'; ?>,
    cats: <?php echo wp_json_encode(array_values($current['cats'] ?? [])); ?>,
    labels: <?php echo wp_json_encode(array_values($current['labels'] ?? [])); ?>,
    updating: false,
    init() {
      this.$watch('sort', () => this.update());
      this.$watch('min_price', () => this.update());
      this.$watch('max_price', () => this.update());
      this.$watch('cats', () => this.update());
      this.$watch('labels', () => this.update());
    },
    buildQuery() {
      const p = new URLSearchParams();
      if (this.sort) p.set('sort', this.sort);
      if (this.min_price !== '' && this.min_price !== null && !isNaN(this.min_price)) p.set('min_price', this.min_price);
      if (this.max_price !== '' && this.max_price !== null && !isNaN(this.max_price)) p.set('max_price', this.max_price);
      (Array.isArray(this.cats) ? this.cats : []).forEach((c) => {
        const n = parseInt(c, 10);
        if (Number.isFinite(n) && n > 0) p.append('cats[]', String(n));
      });
      (Array.isArray(this.labels) ? this.labels : []).forEach((l) => {
        const s = String(l).toLowerCase();
        if (['best','limited','new'].includes(s)) p.append('labels[]', s);
      });
      return p.toString();
    },
    update() {
      if (this.updating) return;
      this.updating = true;
      const url = new URL(window.location.href);
      url.search = this.buildQuery();
      history.replaceState({}, '', url.toString());
      fetch(url.toString(), { credentials: 'same-origin' })
        .then((r) => r.text())
        .then((html) => {
          const doc = new DOMParser().parseFromString(html, 'text/html');
          const newBlock = doc.querySelector('#wps-shop');
          const curBlock = document.querySelector('#wps-shop');
          if (newBlock && curBlock) {
            curBlock.innerHTML = newBlock.innerHTML;
          }
        })
        .finally(() => { this.updating = false; });
    }
  }));
});
</script>

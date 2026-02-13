<template x-if="cart.length === 0">
    <div class="wps-text-sm wps-text-gray-500 wps-flex wps-items-center wps-gap-2">
        <span><?php echo wps_icon(['name' => 'cart', 'size' => 16]); ?></span>
        <span>Keranjang kosong.</span>
    </div>
</template>
<template x-if="cart.length > 0">
    <table class="wps-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th class="wps-text-left wps-text-xs wps-text-gray-500" style="padding:8px;">Foto</th>
                <th class="wps-text-left wps-text-xs wps-text-gray-500" style="padding:8px;">Nama Produk</th>
                <th class="wps-text-left wps-text-xs wps-text-gray-500" style="padding:8px;">Qty</th>
                <th class="wps-text-right wps-text-xs wps-text-gray-500" style="padding:8px;">Total</th>
            </tr>
        </thead>
        <tbody>
            <template x-for="item in cart" :key="item.id + ':' + (item.options ? JSON.stringify(item.options) : '')">
                <tr class="wps-divider">
                    <td style="padding:8px; width:56px;">
                        <img :src="item.image ? item.image : '<?php echo esc_url(WP_STORE_URL . 'assets/frontend/img/noimg.webp'); ?>'" alt="" class="wps-img-40">
                    </td>
                    <td style="padding:8px;">
                        <div x-text="item.title" class="wps-text-sm wps-text-gray-900"></div>
                    </td>
                    <td style="padding:8px;">
                        <div class="wps-flex wps-items-center wps-gap-1">
                            <button type="button" @click="decrement(item)" class="wps-btn wps-btn-secondary wps-btn-sm" style="padding: 2px 8px; font-size: 12px; line-height: 1; min-width: 24px; height: 22px;">-</button>
                            <span x-text="item.qty" class="wps-badge wps-badge-sm" style="font-size: 12px; padding: 2px 6px; line-height: 1;"></span>
                            <button type="button" @click="increment(item)" class="wps-btn wps-btn-secondary wps-btn-sm" style="padding: 2px 8px; font-size: 12px; line-height: 1; min-width: 24px; height: 22px;">+</button>
                        </div>
                    </td>
                    <td style="padding:8px; text-align:right;">
                        <div class="wps-text-sm wps-text-gray-900" x-text="formatPrice(item.subtotal)"></div>
                        <button type="button" @click="remove(item)" :disabled="loading && updatingKey === getItemKey(item)" class="wps-btn wps-btn-danger wps-btn-sm" :style="(loading && updatingKey === getItemKey(item)) ? 'opacity:.7; pointer-events:none; margin-top:6px;' : 'margin-top:6px;'">
                            <template x-if="loading && updatingKey === getItemKey(item)">
                                <span><?php echo wps_icon(['name' => 'spinner', 'size' => 14]); ?></span>
                            </template>
                            <template x-if="!loading || updatingKey !== getItemKey(item)">
                                <span><?php echo wps_icon(['name' => 'close', 'size' => 14]); ?></span>
                            </template>
                        </button>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>
</template>
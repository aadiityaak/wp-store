jQuery(function ($) {
    let frame;
    const $container = $('#wp_store_gallery_container');
    const $hidden = $('#wp_store_gallery_ids');
    const $addBtn = $('#wp_store_add_gallery');

    function getIds() {
        const raw = ($hidden.val() || '').toString().trim();
        if (!raw) {
            return [];
        }
        return raw
            .split(',')
            .map((v) => parseInt(v, 10))
            .filter((v) => Number.isFinite(v) && v > 0);
    }

    function setIds(ids) {
        const uniq = Array.from(new Set(ids));
        $hidden.val(uniq.join(','));
    }

    function appendImage(id, url) {
        const html = `
            <div class="wp-store-gallery-item" data-id="${id}" style="display:inline-block;margin:5px;position:relative;">
                <img src="${url}" style="width:100px;height:100px;object-fit:cover;border:1px solid #ccc;" />
                <button type="button" class="wp-store-remove-image" style="position:absolute;top:0;right:0;background:red;color:white;border:none;cursor:pointer;padding:2px 6px;line-height:1;">×</button>
            </div>
        `;
        $container.append(html);
    }

    $addBtn.on('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'Pilih Gambar Produk',
            button: { text: 'Gunakan Gambar Ini' },
            multiple: true
        });

        frame.on('select', function () {
            const selection = frame.state().get('selection').toJSON();
            const ids = getIds();

            selection.forEach(function (attachment) {
                const id = parseInt(attachment.id, 10);
                if (!Number.isFinite(id) || id <= 0) {
                    return;
                }
                if (ids.indexOf(id) !== -1) {
                    return;
                }

                let url = attachment.url;
                if (attachment.sizes && attachment.sizes.thumbnail && attachment.sizes.thumbnail.url) {
                    url = attachment.sizes.thumbnail.url;
                }

                ids.push(id);
                appendImage(id, url);
            });

            setIds(ids);
        });

        frame.open();
    });

    $container.on('click', '.wp-store-remove-image', function (e) {
        e.preventDefault();
        const $item = $(this).closest('.wp-store-gallery-item');
        const id = parseInt($item.data('id'), 10);

        let ids = getIds();
        ids = ids.filter((v) => v !== id);
        setIds(ids);
        $item.remove();
    });
});


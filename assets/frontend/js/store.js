(() => {
  const wpStoreFactory = (perPage) => ({
    loading: false,
    products: [],
    cart: [],
    wishlist: [],
    perPage: perPage || 12,
    page: 1,
    customer: {
      name: "",
      email: "",
      phone: "",
    },
    submitting: false,
    message: "",
    async init() {
      this.loading = true;
      try {
        await this.fetchCart();
        await this.fetchWishlist();
        await this.fetchProducts();
      } catch (e) {
      } finally {
        this.loading = false;
      }
      document.addEventListener('wp-store:cart-updated', (e) => {
          if (e.detail && e.detail.items) {
              this.cart = e.detail.items;
          } else {
              this.fetchCart();
          }
      });
      document.addEventListener('wp-store:wishlist-updated', (e) => {
          if (e.detail && e.detail.items) {
              this.wishlist = e.detail.items;
          } else {
              this.fetchWishlist();
          }
      });
    },
    async fetchCart() {
      try {
        const response = await fetch(wpStoreSettings.restUrl + "cart", {
          credentials: "same-origin",
        });
        if (!response.ok) {
          throw new Error("Gagal mengambil keranjang");
        }
        const data = await response.json();
        this.cart = data.items || [];
      } catch (e) {
        this.cart = [];
      }
    },
    async fetchWishlist() {
      try {
        const response = await fetch(wpStoreSettings.restUrl + "wishlist", {
          credentials: "same-origin",
        });
        if (!response.ok) {
          throw new Error("Gagal mengambil wishlist");
        }
        const data = await response.json();
        this.wishlist = data.items || [];
      } catch (e) {
        this.wishlist = [];
      }
    },
    async fetchProducts() {
      this.loading = true;
      try {
        const url = new URL(wpStoreSettings.restUrl + "products");
        url.searchParams.set("per_page", this.perPage);
        url.searchParams.set("page", this.page);
        const response = await fetch(url.toString());
        if (!response.ok) {
          throw new Error("Gagal mengambil produk");
        }
        const data = await response.json();
        this.products = data.items || [];
        if (!this.products || this.products.length === 0) {
          await this.fetchProductsFallback();
        }
      } catch (e) {
        await this.fetchProductsFallback();
      } finally {
        this.loading = false;
      }
    },
    async fetchProductsFallback() {
      try {
        const base = String(wpStoreSettings.restUrl).replace(
          /wp-store\/v1\/?$/,
          ""
        );
        const url = new URL(base + "wp/v2/store_product");
        url.searchParams.set("per_page", this.perPage);
        url.searchParams.set("page", this.page);
        url.searchParams.set("_embed", "1");
        const res = await fetch(url.toString(), { credentials: "same-origin" });
        if (!res.ok) {
          return;
        }
        const items = await res.json();
        this.products = (items || []).map((p) => {
          const embedded = p._embedded || {};
          const media = Array.isArray(embedded["wp:featuredmedia"])
            ? embedded["wp:featuredmedia"][0]
            : null;
          const img = media && media.source_url ? media.source_url : null;
          const excerptText =
            p.excerpt && p.excerpt.rendered
              ? p.excerpt.rendered.replace(/<[^>]+>/g, "").trim()
              : "";
          return {
            id: p.id,
            title: p.title && p.title.rendered ? p.title.rendered : "",
            slug: p.slug || "",
            excerpt: excerptText,
            price: null,
            stock: null,
            image: img,
            link: p.link || "",
          };
        });
      } catch (err) {}
    },
    async addToCart(product) {
      const existing = this.cart.find((item) => item.id === product.id);
      const nextQty = existing ? existing.qty + 1 : 1;
      await this.updateCartItem(product.id, nextQty);
    },
    async increment(item) {
      await this.updateCartItem(item.id, item.qty + 1);
    },
    async decrement(item) {
      const nextQty = item.qty > 1 ? item.qty - 1 : 0;
      await this.updateCartItem(item.id, nextQty);
    },
    async remove(item) {
      await this.updateCartItem(item.id, 0);
    },
    get total() {
      return this.cart.reduce((sum, item) => sum + item.price * item.qty, 0);
    },
    async updateCartItem(id, qty) {
      try {
        const response = await fetch(wpStoreSettings.restUrl + "cart", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
          body: JSON.stringify({ id, qty }),
        });
        const data = await response.json();
        if (!response.ok) {
          this.message = data.message || "Gagal update keranjang.";
          return;
        }
        this.cart = data.items || [];
      } catch (e) {
        this.message = "Terjadi kesalahan jaringan.";
      }
    },
    formatPrice(value) {
      if (typeof value !== "number") {
        value = parseFloat(value || 0);
      }
      return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
      }).format(value);
    },
    async checkout() {
      if (!this.customer.name || this.cart.length === 0) {
        this.message = "Isi nama dan keranjang terlebih dahulu.";
        return;
      }
      this.submitting = true;
      this.message = "";
      try {
        const response = await fetch(wpStoreSettings.restUrl + "checkout", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": wpStoreSettings.nonce,
          },
          body: JSON.stringify({
            name: this.customer.name,
            email: this.customer.email,
            phone: this.customer.phone,
            items: this.cart.map((item) => ({
              id: item.id,
              qty: item.qty,
            })),
          }),
        });
        const data = await response.json();
        if (!response.ok) {
          this.message = data.message || "Gagal mengirim pesanan.";
          return;
        }
        this.message = data.message || "Pesanan berhasil dibuat.";
        this.cart = [];
      } catch (e) {
        this.message = "Terjadi kesalahan jaringan.";
      } finally {
        this.submitting = false;
      }
    },
  });

  window.wpStore = wpStoreFactory;
  window.wpStoreReady = true;
  document.dispatchEvent(new Event("wp-store:ready"));

  if (window.Alpine && typeof window.Alpine.data === "function") {
    window.Alpine.data("wpStore", wpStoreFactory);
    if (typeof window.Alpine.initTree === "function") {
      window.Alpine.initTree(document.body);
    } else if (typeof window.Alpine.start === "function") {
      window.Alpine.start();
    }
  } else {
    document.addEventListener("alpine:init", () => {
      Alpine.data("wpStore", wpStoreFactory);
    });
  }
  const initCarousels = () => {
    if (!window.Flickity) return;
    const nodes = document.querySelectorAll('[data-wps-carousel]');
    nodes.forEach((node) => {
      const track = node.querySelector('.main-carousel');
      if (!track || track.__flickity) return;
      const d = node.dataset;
      const groupCellsVal = parseInt(d.groupCells || '0', 10);
      const lazyVal = parseInt(d.lazyLoad || '0', 10);
      const autoPlayVal = parseInt(d.autoplay || '0', 10);
      const opts = {
        cellAlign: d.cellAlign || 'center',
        contain: d.contain === 'false' ? false : true,
        wrapAround: d.wrapAround === 'true',
        pageDots: d.pageDots === 'false' ? false : true,
        prevNextButtons: d.prevNextButtons === 'false' ? false : true,
        groupCells: groupCellsVal > 1 ? groupCellsVal : false,
        lazyLoad: lazyVal > 0 ? lazyVal : false,
        autoPlay: autoPlayVal > 0 ? autoPlayVal : false,
        pauseAutoPlayOnHover: d.pauseOnHover === 'false' ? false : true,
        draggable: d.draggable === 'false' ? false : true
      };
      track.__flickity = new window.Flickity(track, opts);
    });
  };
  if (document.readyState !== 'loading') {
    initCarousels();
  } else {
    document.addEventListener('DOMContentLoaded', initCarousels);
  }
  document.addEventListener('wp-store:ready', initCarousels);
})();

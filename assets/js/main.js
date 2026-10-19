/**
 * AYERKO BEADS • Madina, Accra, Ghana
 * Main Frontend Interactivity, Currency Engine & Cart Controller
 */

// Cart State Management
const Cart = {
    items: [],

    init() {
        try {
            const saved = localStorage.getItem('ayerko_cart');
            if (saved) {
                this.items = JSON.parse(saved);
            }
        } catch (e) {
            this.items = [];
        }
        this.updateBadge();
        this.renderDrawer();
    },

    save() {
        localStorage.setItem('ayerko_cart', JSON.stringify(this.items));
        this.updateBadge();
        this.renderDrawer();
    },

    add(product) {
        const existing = this.items.find(i => i.id === product.id);
        if (existing) {
            existing.quantity += (product.quantity || 1);
        } else {
            this.items.push({
                id: product.id,
                name: product.name,
                priceUSD: parseFloat(product.priceUSD),
                image: product.image,
                category: product.category,
                quantity: product.quantity || 1
            });
        }
        this.save();
        this.openDrawer();
        showToast(`✨ "${product.name}" added to your royal box.`);
    },

    updateQty(id, delta) {
        const item = this.items.find(i => i.id === id);
        if (item) {
            item.quantity += delta;
            if (item.quantity <= 0) {
                this.items = this.items.filter(i => i.id !== id);
                showToast(`Item removed from collection.`);
            }
            this.save();
        }
    },

    remove(id) {
        this.items = this.items.filter(i => i.id !== id);
        this.save();
        showToast(`Item removed from collection.`);
    },

    clear() {
        this.items = [];
        this.save();
    },

    getTotalUSD() {
        return this.items.reduce((sum, item) => sum + (item.priceUSD * item.quantity), 0);
    },

    getCount() {
        return this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    updateBadge() {
        const badges = document.querySelectorAll('.cart-count-badge');
        const count = this.getCount();
        badges.forEach(b => {
            b.textContent = count;
            b.style.display = count > 0 ? 'inline-flex' : 'none';
        });
    },

    openDrawer() {
        const drawer = document.getElementById('cartDrawer');
        const overlay = document.getElementById('cartOverlay');
        if (drawer && overlay) {
            drawer.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeDrawer() {
        const drawer = document.getElementById('cartDrawer');
        const overlay = document.getElementById('cartOverlay');
        if (drawer && overlay) {
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    renderDrawer() {
        const list = document.getElementById('cartItemsList');
        const subtotalEl = document.getElementById('cartSubtotalVal');
        const checkoutBtn = document.getElementById('cartCheckoutBtn');
        const waCheckoutBtn = document.getElementById('cartWaCheckoutBtn');

        if (!list) return;

        if (this.items.length === 0) {
            list.innerHTML = `
                <div class="cart-empty">
                    <div class="cart-empty-icon">📿</div>
                    <p style="font-family: var(--font-heading); color: var(--gold-light); margin-bottom: 8px;">Your Royal Box is Empty</p>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">Explore our handmade Krobo glass, bridal corals & waist beads.</p>
                </div>
            `;
            if (subtotalEl) subtotalEl.textContent = formatCurrency(0);
            if (checkoutBtn) checkoutBtn.style.display = 'none';
            if (waCheckoutBtn) waCheckoutBtn.style.display = 'none';
            return;
        }

        if (checkoutBtn) checkoutBtn.style.display = 'inline-flex';
        if (waCheckoutBtn) waCheckoutBtn.style.display = 'inline-flex';

        let html = '';
        this.items.forEach(item => {
            const itemPrice = convertPrice(item.priceUSD);
            const subtotal = itemPrice * item.quantity;
            html += `
                <div class="cart-item-card">
                    <img src="${item.image}" alt="${escapeHtml(item.name)}" class="cart-item-thumb">
                    <div class="cart-item-details">
                        <h4>${escapeHtml(item.name)}</h4>
                        <div class="cart-item-price">${formatCurrency(itemPrice)}</div>
                        <div class="cart-qty-ctrl">
                            <button class="qty-btn" onclick="Cart.updateQty(${item.id}, -1)">-</button>
                            <span class="qty-num">${item.quantity}</span>
                            <button class="qty-btn" onclick="Cart.updateQty(${item.id}, 1)">+</button>
                        </div>
                    </div>
                    <button class="cart-item-remove" onclick="Cart.remove(${item.id})" title="Remove">✕</button>
                </div>
            `;
        });

        list.innerHTML = html;
        if (subtotalEl) {
            const totalUSD = this.getTotalUSD();
            subtotalEl.textContent = formatCurrency(convertPrice(totalUSD));
        }
    }
};

// Global Currency Settings (injected from PHP window.STORE_CONFIG)
function getActiveCurrency() {
    return window.STORE_CONFIG ? window.STORE_CONFIG.currency : 'GHS';
}

function getCurrencyRate() {
    const curr = getActiveCurrency();
    if (!window.STORE_CONFIG || !window.STORE_CONFIG.rates) return 15.8;
    return window.STORE_CONFIG.rates[curr] || 15.8;
}

function convertPrice(priceUSD) {
    const rate = getCurrencyRate();
    return priceUSD * rate;
}

function formatCurrency(amount) {
    const curr = getActiveCurrency();
    switch (curr) {
        case 'GHS':
            return 'GH₵ ' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        case 'NGN':
            return '₦' + Math.round(amount).toLocaleString();
        case 'GBP':
            return '£' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        case 'EUR':
            return '€' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        case 'USD':
        default:
            return '$' + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function escapeHtml(string) {
    return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Toast notification
function showToast(message) {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.innerHTML = `<span>👑</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// Currency Switcher
function changeCurrency(currency) {
    const url = new URL(window.location.href);
    url.searchParams.set('currency', currency);
    window.location.href = url.toString();
}

// Category Filter & Search
function initCatalogFilters() {
    const pills = document.querySelectorAll('.category-pill');
    const searchInput = document.getElementById('catalogSearch');
    const cards = document.querySelectorAll('.product-card');

    let activeCategory = 'all';
    let searchQuery = '';

    function filterGrid() {
        let visibleCount = 0;
        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            const title = card.getAttribute('data-title').toLowerCase();
            const materials = (card.getAttribute('data-materials') || '').toLowerCase();

            const matchCat = (activeCategory === 'all' || cardCat === activeCategory);
            const matchSearch = (searchQuery === '' || title.includes(searchQuery) || materials.includes(searchQuery));

            if (matchCat && matchSearch) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        const emptyMsg = document.getElementById('catalogEmptyMsg');
        if (emptyMsg) {
            emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            activeCategory = pill.getAttribute('data-category');
            filterGrid();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim().toLowerCase();
            filterGrid();
        });
    }
}

// Modal Controllers
function openQuickView(productData) {
    const modal = document.getElementById('quickViewModal');
    const overlay = document.getElementById('quickViewOverlay');
    if (!modal || !overlay) return;

    const formattedPrice = formatCurrency(convertPrice(productData.priceUSD));
    const waUrl = getDirectWhatsAppUrl(productData, formattedPrice);

    document.getElementById('qvImage').src = productData.image;
    document.getElementById('qvCategory').textContent = productData.category;
    document.getElementById('qvTitle').textContent = productData.name;
    document.getElementById('qvPrice').textContent = formattedPrice;
    document.getElementById('qvDesc').textContent = productData.description;
    document.getElementById('qvMaterials').textContent = productData.materials;
    document.getElementById('qvStock').textContent = `${productData.stock} authentic pieces available`;

    const addBtn = document.getElementById('qvAddBtn');
    addBtn.onclick = () => {
        Cart.add({
            id: productData.id,
            name: productData.name,
            priceUSD: productData.priceUSD,
            image: productData.image,
            category: productData.category,
            quantity: 1
        });
        closeQuickView();
    };

    const waBtn = document.getElementById('qvWaBtn');
    waBtn.href = waUrl;

    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeQuickView() {
    const overlay = document.getElementById('quickViewOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// WhatsApp Link Generator for Ayerko Beads (Madina, Accra)
function getDirectWhatsAppUrl(product, formattedPrice) {
    const phone = window.STORE_CONFIG ? window.STORE_CONFIG.whatsappClean : '233243825825';
    const curr = getActiveCurrency();
    const msg = `👑 *AYERKO BEADS • INQUIRY*\n`
              + `📍 *Madina, Accra, Ghana*\n\n`
              + `Hello Ayerko Beads, I am inquiring about this handcrafted creation:\n`
              + `• *Creation:* ${product.name}\n`
              + `• *Category:* ${product.category}\n`
              + `• *Price:* ${formattedPrice} (${curr})\n`
              + `• *Item ID:* #${product.id}\n\n`
              + `I would like to inquire about waist/bridal sizing and delivery. Thank you!`;
    return `https://wa.me/${phone}?text=${encodeURIComponent(msg)}`;
}

// Open Checkout Modal
function openCheckout(paymentMethod = 'WhatsApp Order') {
    if (Cart.items.length === 0) {
        showToast('Your royal box is empty.');
        return;
    }
    Cart.closeDrawer();

    const overlay = document.getElementById('checkoutOverlay');
    if (overlay) {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        const methodInput = document.getElementById('checkoutMethod');
        if (methodInput) methodInput.value = paymentMethod;

        const summaryEl = document.getElementById('checkoutSummaryList');
        const totalEl = document.getElementById('checkoutTotalVal');
        if (summaryEl) {
            let html = '';
            Cart.items.forEach(i => {
                const price = convertPrice(i.priceUSD);
                html += `<div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 6px;">
                    <span>${escapeHtml(i.name)} × ${i.quantity}</span>
                    <span style="color: var(--gold-light); font-weight: 600;">${formatCurrency(price * i.quantity)}</span>
                </div>`;
            });
            summaryEl.innerHTML = html;
        }
        if (totalEl) {
            totalEl.textContent = formatCurrency(convertPrice(Cart.getTotalUSD()));
        }
    }
}

function closeCheckout() {
    const overlay = document.getElementById('checkoutOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Submit Order to backend (api/order.php)
async function submitOrder(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Generating Order Tracking...';

    const formData = new FormData(form);
    const orderData = {
        customer_name: formData.get('customer_name'),
        customer_phone: formData.get('customer_phone'),
        customer_email: formData.get('customer_email'),
        shipping_address: formData.get('shipping_address'),
        city: formData.get('city'),
        country: formData.get('country') || 'Ghana',
        notes: formData.get('notes'),
        payment_method: formData.get('payment_method') || 'WhatsApp / MoMo Order',
        currency: getActiveCurrency(),
        total_amount: convertPrice(Cart.getTotalUSD()),
        items: Cart.items.map(item => ({
            id: item.id,
            name: item.name,
            price: convertPrice(item.priceUSD),
            quantity: item.quantity,
            subtotal: convertPrice(item.priceUSD) * item.quantity
        }))
    };

    try {
        const response = await fetch('api/order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });

        const res = await response.json();
        if (res.success) {
            Cart.clear();
            closeCheckout();
            showOrderSuccessModal(res);
        } else {
            alert('Order error: ' + (res.error || 'Please check your details'));
        }
    } catch (err) {
        console.error(err);
        alert('Connection error. You can place your order directly via WhatsApp at 0243825825.');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Confirm Royal Order';
    }
}

function showOrderSuccessModal(orderRes) {
    const modal = document.getElementById('orderSuccessModal');
    if (!modal) {
        if (orderRes.whatsapp_url) {
            window.location.href = orderRes.whatsapp_url;
        }
        return;
    }

    document.getElementById('successOrderNo').textContent = orderRes.order_number;
    document.getElementById('successClient').textContent = orderRes.customer_name;
    document.getElementById('successTotal').textContent = formatCurrency(orderRes.total_amount);
    
    const waLink = document.getElementById('successWaBtn');
    if (waLink && orderRes.whatsapp_url) {
        waLink.href = orderRes.whatsapp_url;
    }

    const trackLink = document.getElementById('successTrackBtn');
    if (trackLink) {
        trackLink.href = `track.php?order=${encodeURIComponent(orderRes.order_number)}`;
    }

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeOrderSuccess() {
    const modal = document.getElementById('orderSuccessModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        window.location.reload();
    }
}

// Document Ready
document.addEventListener('DOMContentLoaded', () => {
    Cart.init();
    initCatalogFilters();

    // Close overlays with ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Cart.closeDrawer();
            closeQuickView();
            closeCheckout();
        }
    });
});

import Alpine from 'alpinejs';

const currency = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' });

const isEmpty = (value) => value === '' || value === null || Number.isNaN(value);
const round2 = (value) => Math.round(value * 100) / 100;

/**
 * Formulario de producto: costo, margen y precio se calculan entre sí,
 * IVA sugerido o manual, y vista previa de la imagen.
 * Se usa en resources/views/erp/products/_form.blade.php
 *
 *  - Cambiar el margen  -> recalcula el precio.
 *  - Cambiar el precio  -> recalcula el margen.
 *  - Cambiar el costo   -> mantiene el margen y recalcula el precio.
 */
Alpine.data('productForm', ({ cost, price, tax, taxPresets, image }) => ({
    cost,
    price,
    tax,
    margin: null,
    taxOption: '',
    preview: image,
    removeImage: false,

    init() {
        this.margin = this.marginFor(this.price);
        this.taxOption = taxPresets.includes(this.tax) ? String(this.tax) : 'custom';
    },

    // Margen sobre el costo: costo 1000 y precio 1500 = 50%.
    marginFor(price) {
        if (!(this.cost > 0) || isEmpty(price)) return null;

        return round2(((price - this.cost) / this.cost) * 100);
    },

    onCostInput() {
        if (isEmpty(this.margin)) {
            this.onPriceInput();
        } else {
            this.onMarginInput();
        }
    },

    onMarginInput() {
        if (isEmpty(this.margin) || isEmpty(this.cost)) return;

        this.price = round2(this.cost * (1 + this.margin / 100));
    },

    onPriceInput() {
        this.margin = this.marginFor(this.price);
    },

    onTaxOptionChange() {
        if (this.taxOption === 'custom') {
            this.$nextTick(() => this.$refs.customTax.focus());
        } else {
            this.tax = Number(this.taxOption);
        }
    },

    get profit() {
        return (this.price || 0) - (this.cost || 0);
    },

    get taxAmount() {
        return ((this.price || 0) * (this.tax || 0)) / 100;
    },

    get finalPrice() {
        return (this.price || 0) + this.taxAmount;
    },

    money(value) {
        return currency.format(value || 0);
    },

    pickImage(event) {
        const [file] = event.target.files;
        if (!file) return;

        this.preview = URL.createObjectURL(file);
        this.removeImage = false;
    },

    clearImage() {
        this.$refs.image.value = '';
        this.preview = null;
        this.removeImage = true;
    },
}));

/**
 * Selector de imagen con vista previa (foto de portada de categorías).
 */
Alpine.data('imagePicker', (image) => ({
    preview: image,

    pick(event) {
        const [file] = event.target.files;
        if (file) this.preview = URL.createObjectURL(file);
    },
}));

/**
 * Armado del catálogo PDF: resumen en vivo de categorías, productos y páginas.
 * Se usa en resources/views/erp/catalog/index.blade.php
 */
Alpine.data('catalogBuilder', (categories, selected, perPage) => ({
    categories, // [{ id, count }] con count = productos con stock
    selected, // ids marcados (como texto, por x-model)

    get chosen() {
        return this.categories.filter((category) => this.selected.includes(String(category.id)));
    },

    get productCount() {
        return this.chosen.reduce((total, category) => total + category.count, 0);
    },

    // Portada + por cada categoría: su página de foto y las de productos.
    get pageCount() {
        if (this.productCount === 0) return 0;

        return 1 + this.chosen.reduce((total, category) => total + 1 + Math.ceil(category.count / perPage), 0);
    },

    selectAll() {
        this.selected = this.categories.filter((category) => category.count > 0).map((category) => String(category.id));
    },

    selectNone() {
        this.selected = [];
    },
}));

/**
 * Ajuste de stock: muestra cómo queda el stock antes de guardar y filtra los motivos según el modo.
 * Se usa en resources/views/erp/inventory/adjust.blade.php
 */
Alpine.data('stockAdjust', ({ products, reasons, productId, mode, quantity, reason }) => ({
    products, // { id: { stock } }
    reasons, // [{ value, label, direction: 'in' | 'out' | 'both' }]
    productId,
    mode, // 'add' | 'remove' | 'count'
    quantity,
    reason,

    get current() {
        return this.products[this.productId]?.stock ?? 0;
    },

    get difference() {
        if (this.quantity === '' || this.quantity === null) return null;
        if (this.mode === 'count') return this.quantity - this.current;

        return this.mode === 'add' ? this.quantity : -this.quantity;
    },

    get after() {
        return this.difference === null ? null : this.current + this.difference;
    },

    get availableReasons() {
        const direction = this.mode === 'add' ? 'in' : 'out';

        return this.reasons.filter((option) => [direction, 'both'].includes(option.direction));
    },

    init() {
        // Si el motivo elegido no corresponde al modo nuevo, se limpia.
        this.$watch('mode', () => {
            if (!this.availableReasons.some((option) => option.value === this.reason)) this.reason = '';
        });
    },
}));

/**
 * Carga de una compra: líneas de productos, totales y cuánto queda debiendo.
 * Se usa en resources/views/erp/purchases/create.blade.php
 */
let purchaseItemKey = 0;

Alpine.data('purchaseForm', ({ products, items, taxAmount, paidNow, errors }) => ({
    products, // { id: { cost, stock } }
    items: items.map((item) => ({ ...item, key: purchaseItemKey++ })),
    taxAmount,
    paidNow,
    errors, // errores de validación de Laravel, ej. { 'items.0.product_id': ['…'] }

    addItem() {
        this.items.push({ product_id: '', quantity: 1, unit_cost: '', key: purchaseItemKey++ });
    },

    removeItem(index) {
        this.items.splice(index, 1);
    },

    // Al elegir un producto, propone su último costo.
    pickProduct(item) {
        const product = this.products[item.product_id];
        if (product) item.unit_cost = product.cost;
    },

    costChanged(item) {
        const product = this.products[item.product_id];

        return product && item.unit_cost !== '' && Number(item.unit_cost) !== product.cost;
    },

    subtotal(item) {
        return (Number(item.quantity) || 0) * (Number(item.unit_cost) || 0);
    },

    get itemsTotal() {
        return this.items.reduce((total, item) => total + this.subtotal(item), 0);
    },

    get total() {
        return Math.round((this.itemsTotal + (Number(this.taxAmount) || 0)) * 100) / 100;
    },

    get debt() {
        return Math.max(0, this.total - (Number(this.paidNow) || 0));
    },

    money(value) {
        return currency.format(value || 0);
    },
}));

/**
 * Fila del listado de productos: doble click en Margen o IVA para editarlos.
 * Enter o click afuera guarda, Escape cancela. Se usa en resources/views/erp/products/index.blade.php
 *
 * `pricing` viene de Product::pricingSummary() y se reemplaza con la respuesta del servidor.
 */
Alpine.data('productRow', (url, pricing) => ({
    pricing,
    editing: null, // 'margin' | 'tax_rate' | null
    draft: '',
    saving: false,
    error: null,
    saved: false,

    edit(field) {
        if (this.editing === field) return; // doble click dentro del input: no reiniciar
        if (field === 'margin' && !this.pricing.has_cost) return;

        this.editing = field;
        this.draft = this.pricing[field] ?? '';
        this.error = null;

        this.$nextTick(() => {
            this.$refs[field].focus();
            this.$refs[field].select();
        });
    },

    cancel() {
        this.editing = null;
        this.error = null;
    },

    async save() {
        if (!this.editing || this.saving) return;

        const field = this.editing;

        // Sin cambios: no hace falta ir al servidor.
        if (this.draft !== '' && Number(this.draft) === Number(this.pricing[field])) {
            return this.cancel();
        }

        this.saving = true;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ [field]: this.draft }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.error = data.errors?.[field]?.[0] ?? data.message ?? 'No se pudo guardar.';
                return;
            }

            this.pricing = data;
            this.editing = null;
            this.saved = true;
            setTimeout(() => (this.saved = false), 1200);
        } catch {
            this.error = 'Error de conexión. Probá de nuevo.';
        } finally {
            this.saving = false;
        }
    },
}));

/**
 * Punto de venta: búsqueda (o lector de código de barras), carrito, descuento y cobro.
 * Se usa en resources/views/pos/index.blade.php
 *
 * El precio que se muestra es orientativo: el servidor vuelve a calcular todo con los precios de la base.
 */
const normalize = (text) =>
    String(text ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, ''); // saca las tildes: "cerámica" encuentra "ceramica"

let paymentKey = 0;

Alpine.data('posTerminal', ({ products, methods, storeUrl }) => ({
    products, // [{ id, category_id, name, sku, barcode, price, stock, image }]
    methods, // [{ value, label, icon, givesChange }]
    query: '',
    categoryId: null,
    cart: [], // [{ product_id, name, price, quantity, stock }]
    discountMode: '$', // '$' | '%'
    discountValue: '',
    method: null, // medio elegido cuando se paga con uno solo
    tendered: '', // "paga con" (efectivo)
    split: false,
    splitPayments: [], // [{ key, method, amount }]
    notes: '',
    saving: false,
    errors: {}, // errores de Laravel: { 'items.0.quantity': ['…'] }
    notice: null, // aviso corto (ej. "sin stock")
    lastSale: null, // respuesta del servidor al cobrar

    init() {
        // Con el pago dividido en una sola línea, si cambia el total la línea lo acompaña.
        this.$watch('total', (total) => {
            if (this.split && this.splitPayments.length === 1) this.splitPayments[0].amount = total;
        });
    },

    // --- Productos -----------------------------------------------------

    get results() {
        const terms = normalize(this.query).split(/\s+/).filter(Boolean);

        return this.products
            .filter((product) => !this.categoryId || product.category_id === this.categoryId)
            .filter((product) => {
                const haystack = normalize(`${product.name} ${product.sku} ${product.barcode}`);

                return terms.every((term) => haystack.includes(term));
            })
            .slice(0, 60);
    },

    // Enter en el buscador: el lector de código de barras escribe el código y manda Enter.
    searchEnter() {
        const code = normalize(this.query.trim());
        if (!code) return;

        const exact = this.products.find((product) => normalize(product.barcode) === code || normalize(product.sku) === code);
        const match = exact ?? (this.results.length === 1 ? this.results[0] : null);

        if (match) {
            this.add(match);
            this.query = '';
        } else {
            this.flash(`No encontré "${this.query.trim()}".`);
        }
    },

    add(product) {
        const line = this.cart.find((item) => item.product_id === product.id);
        const inCart = line?.quantity ?? 0;

        if (inCart >= product.stock) {
            return this.flash(product.stock > 0 ? `${product.name}: solo hay ${product.stock} en stock.` : `${product.name}: no hay stock.`);
        }

        if (line) {
            line.quantity++;
        } else {
            this.cart.push({ product_id: product.id, name: product.name, price: product.price, quantity: 1, stock: product.stock });
        }

        this.errors = {};
    },

    inCart(product) {
        return this.cart.find((item) => item.product_id === product.id)?.quantity ?? 0;
    },

    // --- Carrito -------------------------------------------------------

    increment(line) {
        if (line.quantity >= line.stock) return this.flash(`${line.name}: solo hay ${line.stock} en stock.`);
        line.quantity++;
    },

    decrement(line) {
        if (line.quantity > 1) line.quantity--;
    },

    // Cantidad escrita a mano: entre 1 y el stock.
    fixQuantity(line) {
        line.quantity = Math.min(Math.max(1, Math.floor(Number(line.quantity) || 1)), line.stock);
    },

    remove(index) {
        this.cart.splice(index, 1);
        this.errors = {};
        if (!this.cart.length) this.resetPayment();
    },

    clearCart() {
        this.cart = [];
        this.discountValue = '';
        this.notes = '';
        this.errors = {};
        this.resetPayment();
    },

    lineError(index) {
        return this.errors[`items.${index}.quantity`]?.[0] ?? this.errors[`items.${index}.product_id`]?.[0];
    },

    // --- Totales -------------------------------------------------------

    get subtotal() {
        return round2(this.cart.reduce((total, line) => total + line.price * line.quantity, 0));
    },

    get discount() {
        const value = Number(this.discountValue) || 0;
        const amount = this.discountMode === '%' ? (this.subtotal * Math.min(value, 100)) / 100 : value;

        return round2(Math.min(Math.max(amount, 0), this.subtotal));
    },

    get total() {
        return round2(this.subtotal - this.discount);
    },

    get units() {
        return this.cart.reduce((total, line) => total + Number(line.quantity), 0);
    },

    // --- Pago ----------------------------------------------------------

    methodInfo(value) {
        return this.methods.find((method) => method.value === value);
    },

    pick(value) {
        this.method = value;
        if (!this.methodInfo(value).givesChange) this.tendered = '';
    },

    get isCash() {
        return !this.split && this.method && this.methodInfo(this.method).givesChange;
    },

    // Con cuánto puede pagar: billetes redondos por encima del total.
    get cashSuggestions() {
        if (!(this.total > 0)) return [];

        const options = [1000, 2000, 10000, 20000].map((bill) => Math.ceil(this.total / bill) * bill);

        return [...new Set(options)].filter((amount) => amount > this.total).slice(0, 3);
    },

    get change() {
        const tendered = Number(this.tendered);

        return this.tendered === '' || tendered < this.total ? null : round2(tendered - this.total);
    },

    get missingCash() {
        return this.tendered !== '' && Number(this.tendered) < this.total ? round2(this.total - Number(this.tendered)) : null;
    },

    startSplit() {
        this.split = true;
        this.splitPayments = [{ key: paymentKey++, method: this.method ?? 'cash', amount: this.total }];
        this.tendered = '';
    },

    stopSplit() {
        this.split = false;
        this.splitPayments = [];
    },

    addSplitPayment() {
        const used = this.splitPayments.map((payment) => payment.method);
        const next = this.methods.find((method) => !used.includes(method.value))?.value ?? 'cash';

        this.splitPayments.push({ key: paymentKey++, method: next, amount: Math.max(0, this.remaining) });
    },

    removeSplitPayment(index) {
        this.splitPayments.splice(index, 1);
        if (!this.splitPayments.length) this.stopSplit();
    },

    get paid() {
        return round2(this.splitPayments.reduce((total, payment) => total + (Number(payment.amount) || 0), 0));
    },

    get remaining() {
        return round2(this.total - this.paid);
    },

    resetPayment() {
        this.method = null;
        this.tendered = '';
        this.stopSplit();
    },

    get payments() {
        if (this.split) {
            return this.splitPayments.map(({ method, amount }) => ({ method, amount: Number(amount) || 0 }));
        }

        if (!this.method) return [];

        return [{ method: this.method, amount: this.total, tendered: this.isCash && this.tendered !== '' ? Number(this.tendered) : null }];
    },

    // Qué falta para poder cobrar (o null si está todo listo).
    get blocker() {
        if (!this.cart.length) return 'Agregá productos';
        if (!(this.total > 0)) return 'El total tiene que ser mayor a cero';
        if (!this.payments.length) return 'Elegí cómo paga';
        if (this.split && Math.abs(this.remaining) >= 0.01) {
            return this.remaining > 0 ? `Faltan ${this.money(this.remaining)}` : `Sobran ${this.money(-this.remaining)}`;
        }
        if (this.missingCash !== null) return `Con lo que paga no alcanza`;

        return null;
    },

    async charge() {
        if (this.blocker || this.saving) return;

        this.saving = true;
        this.errors = {};

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    items: this.cart.map(({ product_id, quantity }) => ({ product_id, quantity })),
                    discount: this.discount,
                    payments: this.payments,
                    notes: this.notes || null,
                }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.errors = data.errors ?? { general: [data.message ?? 'No se pudo registrar la venta.'] };
                return;
            }

            // El stock de la pantalla queda igual al de la base.
            Object.entries(data.stocks).forEach(([id, stock]) => {
                const product = this.products.find((item) => item.id === Number(id));
                if (product) product.stock = stock;
            });

            this.lastSale = data;
            this.clearCart();
            this.$nextTick(() => this.$refs.newSale.focus());
        } catch {
            this.errors = { general: ['Error de conexión. La venta no se registró: probá de nuevo.'] };
        } finally {
            this.saving = false;
        }
    },

    // Errores que no son de una línea del carrito (pagos, descuento, conexión).
    get generalErrors() {
        return Object.entries(this.errors)
            .filter(([field]) => !field.startsWith('items.'))
            .map(([, messages]) => messages[0]);
    },

    newSale() {
        this.lastSale = null;
        this.$nextTick(() => this.$refs.search.focus());
    },

    printTicket() {
        window.open(`${this.lastSale.ticket_url}?imprimir=1`, 'ticket', 'width=420,height=640');
    },

    // --- Varios --------------------------------------------------------

    flash(message) {
        this.notice = message;
        clearTimeout(this.noticeTimer);
        this.noticeTimer = setTimeout(() => (this.notice = null), 2500);
    },

    money(value) {
        return currency.format(value || 0);
    },
}));

/**
 * Un campo del listado de productos que se guarda al cambiarlo, sin entrar a editar:
 * la categoría (desplegable) o "Web" (publicado en la tienda). Si falla, vuelve al valor anterior.
 */
Alpine.data('quickField', (url, field, value) => ({
    value,
    saving: false,
    saved: false,
    error: null,

    async save(newValue) {
        const previous = this.value;
        this.value = newValue;
        this.saving = true;
        this.error = null;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ [field]: newValue }),
            });
            const data = await response.json();

            if (!response.ok) {
                this.value = previous;
                this.error = data.errors?.[field]?.[0] ?? data.message ?? 'No se pudo guardar.';
                return;
            }

            this.value = data[field];
            this.saved = true;
            setTimeout(() => (this.saved = false), 1200);
        } catch {
            this.value = previous;
            this.error = 'Error de conexión. Probá de nuevo.';
        } finally {
            this.saving = false;
        }
    },
}));

/**
 * Corazón del listado de productos del ERP: marca el producto como destacado
 * ("Lo más buscado" en la portada de la tienda).
 */
Alpine.data('featuredToggle', (url, featured) => ({
    featured,
    saving: false,

    async toggle() {
        if (this.saving) return;

        this.saving = true;
        this.featured = !this.featured; // se ve al instante; si falla, vuelve atrás

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            if (!response.ok) throw new Error();

            this.featured = (await response.json()).is_featured;
        } catch {
            this.featured = !this.featured;
        } finally {
            this.saving = false;
        }
    },
}));

/**
 * Favoritos de la tienda: el corazón de cada producto. No hay cuentas de clientes todavía,
 * así que se guardan en el navegador del visitante (localStorage).
 */
const FAVORITES_KEY = 'decobazar:favoritos';

const readFavorites = () => {
    try {
        return JSON.parse(localStorage.getItem(FAVORITES_KEY)) ?? [];
    } catch {
        return [];
    }
};

Alpine.data('favoriteButton', (productId) => ({
    active: readFavorites().includes(productId),

    toggle() {
        const others = readFavorites().filter((id) => id !== productId);
        this.active = !this.active;

        try {
            localStorage.setItem(FAVORITES_KEY, JSON.stringify(this.active ? [...others, productId] : others));
        } catch {
            // Navegación privada o almacenamiento bloqueado: el corazón funciona igual en esta página.
        }
    },
}));

window.Alpine = Alpine;
Alpine.start();

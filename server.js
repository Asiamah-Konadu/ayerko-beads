require('dotenv').config();

const path = require('node:path');
const fs = require('node:fs/promises');
const crypto = require('node:crypto');
const express = require('express');
const cookieSession = require('cookie-session');
const multer = require('multer');
const bcrypt = require('bcryptjs');
const { put } = require('@vercel/blob');
const prisma = require('./lib/db');
const {
  formatPrice,
  getSettings,
  orderNumber,
  productWhatsAppUrl,
  slugify,
  whatsappPhone,
  currencySymbol,
} = require('./lib/store');

const app = express();
const root = __dirname;
const allowedCurrencies = ['GHS', 'USD', 'NGN', 'GBP', 'EUR'];
const orderStatuses = ['New', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
const categories = [
  'Krobo Artisan Statement',
  'Luxury Waist Beads',
  'Royal Wedding Coral',
  'Royal Crowns & Headpieces',
];
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 4 * 1024 * 1024, files: 1 },
  fileFilter(_req, file, callback) {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.mimetype)) {
      const error = new Error('Invalid image format. Only JPG, PNG, and WebP are accepted.');
      error.status = 400;
      callback(error);
      return;
    }
    callback(null, true);
  },
});

app.set('views', path.join(root, 'views'));
app.set('view engine', 'ejs');
app.set('trust proxy', 1);
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: false, limit: '1mb' }));
app.use(cookieSession({
  name: 'ayerko-session',
  keys: [process.env.SESSION_SECRET || process.env.DATABASE_URL || 'development-session-key-change-me'],
  maxAge: 7 * 24 * 60 * 60 * 1000,
  httpOnly: true,
  sameSite: 'lax',
  secure: process.env.VERCEL === '1' || process.env.NODE_ENV === 'production',
}));
app.use('/assets', express.static(path.join(root, 'assets'), { maxAge: '1d' }));

app.use((req, res, next) => {
  res.locals.currentPath = req.path;
  res.locals.formatPrice = formatPrice;
  res.locals.currencySymbol = currencySymbol;
  res.locals.whatsappPhone = whatsappPhone;
  res.locals.productWhatsAppUrl = productWhatsAppUrl;
  res.locals.jsonForScript = (value) => JSON.stringify(value).replace(/[<>&\u2028\u2029]/g, (character) => ({
    '<': '\\u003c',
    '>': '\\u003e',
    '&': '\\u0026',
    '\u2028': '\\u2028',
    '\u2029': '\\u2029',
  })[character]);
  res.locals.getImageUrl = (image) => /^https?:\/\//i.test(image || '')
    ? image
    : `/${String(image || '').replace(/^\/+/, '')}`;
  next();
});

function currentCurrency(req) {
  const requested = String(req.query.currency || '');
  if (allowedCurrencies.includes(requested)) req.session.currency = requested;
  return allowedCurrencies.includes(req.session.currency) ? req.session.currency : 'GHS';
}

function asyncRoute(handler) {
  return (req, res, next) => Promise.resolve(handler(req, res, next)).catch(next);
}

async function renderStorePage(req, res, pageTitle, template, data = {}) {
  const settings = await getSettings();
  const currency = currentCurrency(req);
  const cleanPhone = whatsappPhone(settings.whatsapp_number);
  res.render(template, {
    pageTitle,
    metaDescription: 'Handcrafted Ghanaian Krobo glass neckpieces, authentic bridal corals, and bespoke luxury waist beads in Madina, Accra.',
    settings,
    currency,
    cleanPhone,
    rates: Object.fromEntries(allowedCurrencies.map((value) => [
      value,
      Number(settings[`rate_${value}`] || 1),
    ])),
    ...data,
  });
}

function requireAdmin(req, res, next) {
  if (req.session.admin_logged_in === true) return next();
  return res.redirect('/admin/login.php');
}

function adminPage(req, res, page, data = {}) {
  return getSettings().then((settings) => res.render('admin', {
    page,
    settings,
    adminUser: req.session.admin_user || 'Master Artisan Ayerko',
    error: '',
    message: '',
    success: '',
    ...data,
  }));
}

async function uploadProductImage(file) {
  const extension = ({ 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' })[file.mimetype];
  if (!extension) throw new Error('Invalid image format. Only JPG, PNG, and WebP are accepted.');
  const filename = `bead_${crypto.randomBytes(12).toString('hex')}.${extension}`;
  const token = process.env.BLOB_READ_WRITE_TOKEN;
  if (token) {
    const blob = await put(`product-images/${filename}`, file.buffer, {
      access: 'public',
      token,
      contentType: file.mimetype,
    });
    return blob.url;
  }

  if (process.env.VERCEL) {
    throw new Error('Vercel Blob is not configured. Add BLOB_READ_WRITE_TOKEN to this Vercel project.');
  }

  const uploadDirectory = path.join(root, 'assets', 'uploads');
  await fs.mkdir(uploadDirectory, { recursive: true });
  await fs.writeFile(path.join(uploadDirectory, filename), file.buffer, { flag: 'wx' });
  return `assets/uploads/${filename}`;
}

function parseProductForm(body) {
  const name = String(body.name || '').trim();
  const category = String(body.category || '').trim();
  const price = Number(body.price);
  const stock = Number(body.stock);
  if (!name || !category || !Number.isFinite(price) || price <= 0 || !Number.isInteger(stock) || stock < 0) {
    throw new Error('Provide a title, category, positive price, and non-negative stock count.');
  }
  return {
    name,
    category,
    price,
    stock,
    featured: body.featured ? 1 : 0,
    description: String(body.description || '').trim(),
    materials: String(body.materials || '').trim(),
  };
}

app.get(['/', '/index.php'], asyncRoute(async (req, res) => {
  const products = await prisma.product.findMany({ orderBy: [{ featured: 'desc' }, { id: 'asc' }] });
  await renderStorePage(req, res, 'Ayerko Beads • Handcrafted Ghanaian Luxury Beads | Madina, Accra', 'home', { products });
}));

app.get(['/product.php', '/product'], asyncRoute(async (req, res) => {
  const where = req.query.id
    ? (/^\d+$/.test(String(req.query.id)) ? { id: BigInt(String(req.query.id)) } : null)
    : req.query.slug
      ? { slug: String(req.query.slug) }
      : null;
  if (!where) return res.redirect('/index.php');
  const product = await prisma.product.findUnique({ where });
  if (!product) return res.redirect('/index.php');
  const related = await prisma.product.findMany({
    where: { category: product.category, id: { not: product.id } },
    take: 3,
  });
  return renderStorePage(req, res, `${product.name} | Ayerko Beads • Madina, Accra`, 'product', { product, related });
}));

app.get(['/track.php', '/track'], asyncRoute(async (req, res) => {
  const query = String(req.query.order || '').trim();
  let order = null;
  if (query) {
    const digits = query.replace(/\D/g, '');
    order = await prisma.order.findFirst({
      where: {
        OR: [
          { orderNumber: { equals: query, mode: 'insensitive' } },
          ...(digits ? [{ customerPhone: { contains: digits } }] : []),
        ],
      },
      orderBy: { id: 'desc' },
      include: { items: true },
    });
  }
  return renderStorePage(req, res, 'Track Royal Order • Ayerko Beads | Madina, Accra', 'track', {
    query,
    order,
    error: query && !order
      ? `No order record found for '${query}'. Please verify your Order Reference or phone number.`
      : '',
  });
}));

app.post('/api/order.php', asyncRoute(async (req, res) => {
  const data = req.body;
  const customerName = String(data.customer_name || '').trim();
  const customerPhone = String(data.customer_phone || '').trim();
  const shippingAddress = String(data.shipping_address || '').trim();
  const currency = String(data.currency || 'GHS');
  if (!customerName || !customerPhone || !shippingAddress || !Array.isArray(data.items) || !data.items.length) {
    return res.status(400).json({ success: false, error: 'Please provide client name, phone, address, and at least one item.' });
  }
  if (!allowedCurrencies.includes(currency)) {
    return res.status(400).json({ success: false, error: 'Unsupported currency.' });
  }

  const requestedItems = [...data.items.reduce((combined, item) => {
    const id = String(item.id || '');
    const quantity = Number(item.quantity);
    combined.set(id, (combined.get(id) || 0) + quantity);
    return combined;
  }, new Map())].map(([id, quantity]) => ({ id, quantity }));
  if (requestedItems.some((item) => !/^\d+$/.test(item.id) || !Number.isInteger(item.quantity) || item.quantity < 1 || item.quantity > 100)) {
    return res.status(400).json({ success: false, error: 'One or more order items are invalid.' });
  }

  const settings = await getSettings();
  const rate = Number(settings[`rate_${currency}`] || 1);
  const orderRef = orderNumber();
  const created = await prisma.$transaction(async (tx) => {
    let total = 0;
    const lines = [];
    for (const item of requestedItems) {
      const product = await tx.product.findUnique({ where: { id: BigInt(item.id) } });
      if (!product) {
        const error = new Error('A selected creation is no longer available. Refresh the page and try again.');
        error.status = 400;
        throw error;
      }
      const price = Number(product.price) * rate;
      const subtotal = price * item.quantity;
      total += subtotal;
      lines.push({ product, quantity: item.quantity, price, subtotal });
    }

    const order = await tx.order.create({
      data: {
        orderNumber: orderRef,
        customerName,
        customerEmail: String(data.customer_email || '').trim() || null,
        customerPhone,
        shippingAddress,
        city: String(data.city || 'Accra').trim(),
        country: String(data.country || 'Ghana').trim(),
        currency,
        totalAmount: Number(total.toFixed(2)),
        paymentMethod: String(data.payment_method || 'WhatsApp / MoMo Order').trim(),
        paymentStatus: 'Pending',
        orderStatus: 'New',
        courierName: 'Accra Courier Dispatch',
        notes: String(data.notes || '').trim() || null,
        items: {
          create: lines.map(({ product, quantity, price, subtotal }) => ({
            productId: product.id,
            productName: product.name,
            price,
            quantity,
            subtotal: Number(subtotal.toFixed(2)),
          })),
        },
      },
    });

    for (const { product, quantity } of lines) {
      if ((product.stock || 0) > 0) {
        await tx.product.update({
          where: { id: product.id },
          data: { stock: Math.max(0, (product.stock || 0) - quantity) },
        });
      }
    }
    return { order, lines, total };
  });

  const formattedItems = created.lines.map(({ product, quantity, subtotal }) =>
    `• ${product.name} x ${quantity} (${currencySymbol(currency)}${subtotal.toFixed(2)})`).join('\n');
  const message = [
    '👑 *AYERKO BEADS • NEW CLIENT ORDER*',
    '📍 *Atelier:* Madina, Accra, Ghana',
    '',
    `Order Tracking No: *${orderRef}*`,
    `Client Name: *${customerName}*`,
    `Client Mobile: ${customerPhone}`,
    '',
    '*Selected Pieces:*',
    formattedItems,
    '',
    `*Total Amount:* ${currencySymbol(currency)}${created.total.toFixed(2)}`,
    String(data.notes || '').trim() ? `Custom Waist/Neck Sizing Notes: ${String(data.notes).trim()}` : '',
    '',
    `Track your order anytime using your Order No: *${orderRef}*`,
    'Please confirm availability & delivery details. Medaase!',
  ].filter(Boolean).join('\n');

  return res.json({
    success: true,
    order_id: String(created.order.id),
    order_number: orderRef,
    customer_name: customerName,
    total_amount: Number(created.total.toFixed(2)),
    currency,
    whatsapp_url: `https://wa.me/${whatsappPhone(settings.whatsapp_number)}?text=${encodeURIComponent(message)}`,
    message: `Order registered with Ayerko Beads. Tracking reference: ${orderRef}`,
  });
}));

app.get(['/admin', '/admin/', '/admin/index.php'], requireAdmin, asyncRoute(async (req, res) => {
  const [totalOrders, totalProducts, lowStock, revenue, recentOrders, settings] = await Promise.all([
    prisma.order.count(),
    prisma.product.count(),
    prisma.product.count({ where: { stock: { lte: 3 } } }),
    prisma.order.aggregate({ where: { orderStatus: { not: 'Cancelled' } }, _sum: { totalAmount: true } }),
    prisma.order.findMany({ orderBy: { id: 'desc' }, take: 8 }),
    getSettings(),
  ]);
  return adminPage(req, res, 'dashboard', {
    stats: { totalOrders, totalProducts, lowStock, revenue: revenue._sum.totalAmount || 0 },
    recentOrders,
    settings,
  });
}));

app.get('/admin/login.php', asyncRoute(async (req, res) => {
  if (req.session.admin_logged_in) return res.redirect('/admin/');
  return res.render('login', { error: '' });
}));

app.post('/admin/login.php', asyncRoute(async (req, res) => {
  const row = await prisma.setting.findUnique({ where: { key: 'admin_password' } });
  const hash = String(row?.value || '').replace(/^\$2y\$/, '$2b$');
  if (hash && await bcrypt.compare(String(req.body.password || ''), hash)) {
    req.session.admin_logged_in = true;
    req.session.admin_user = 'Master Artisan Ayerko';
    return res.redirect('/admin/');
  }
  return res.status(401).render('login', { error: 'Invalid Master Password. Please check and try again.' });
}));

app.get('/admin/logout.php', (req, res) => {
  req.session = null;
  res.redirect('/admin/login.php');
});

app.get('/admin/orders.php', requireAdmin, asyncRoute(async (req, res) => {
  const status = String(req.query.status || 'all');
  const orders = await prisma.order.findMany({
    where: status === 'all' ? {} : { orderStatus: status },
    orderBy: { id: 'desc' },
    include: { items: true },
  });
  return adminPage(req, res, 'orders', {
    orders,
    status,
    message: req.query.updated ? 'Order updated successfully. Client tracking has been updated.' : '',
  });
}));

app.post('/admin/orders.php', requireAdmin, asyncRoute(async (req, res) => {
  const orderId = String(req.body.order_id || '');
  const status = String(req.body.order_status || 'New');
  if (!/^\d+$/.test(orderId) || !orderStatuses.includes(status)) {
    return res.status(400).send('Invalid order update.');
  }
  await prisma.order.update({
    where: { id: BigInt(orderId) },
    data: {
      orderStatus: status,
      courierName: String(req.body.courier_name || '').trim(),
      trackingNumber: String(req.body.tracking_number || '').trim() || null,
    },
  });
  return res.redirect(`/admin/orders.php?status=${encodeURIComponent(req.body.filter_status || 'all')}&updated=1`);
}));

app.get('/admin/products.php', requireAdmin, asyncRoute(async (req, res) => {
  if (req.query.action === 'delete' && /^\d+$/.test(String(req.query.id || ''))) {
    await prisma.product.delete({ where: { id: BigInt(String(req.query.id)) } });
    return res.redirect('/admin/products.php?deleted=1');
  }
  const products = await prisma.product.findMany({ orderBy: { id: 'desc' } });
  return adminPage(req, res, 'products', {
    products,
    message: req.query.created
      ? 'Bead creation added to the catalog.'
      : req.query.deleted ? 'Bead creation removed from the catalog.' : '',
  });
}));

app.get('/admin/products.php/delete/:id', requireAdmin, asyncRoute(async (req, res) => {
  await prisma.product.delete({ where: { id: BigInt(req.params.id) } });
  return res.redirect('/admin/products.php?deleted=1');
}));

app.get('/admin/product_add.php', requireAdmin, (_req, res) => adminPage(_req, res, 'product-form', {
  product: null,
  error: '',
  categories,
}));

app.post('/admin/product_add.php', requireAdmin, upload.single('image'), asyncRoute(async (req, res) => {
  try {
    const product = parseProductForm(req.body);
    if (!req.file) throw new Error('Please choose a photo of the bead creation to upload.');
    const image = await uploadProductImage(req.file);
    const baseSlug = slugify(product.name);
    const existing = await prisma.product.findUnique({ where: { slug: baseSlug } });
    await prisma.product.create({
      data: {
        ...product,
        image,
        slug: existing ? `${baseSlug}-${Date.now()}` : baseSlug,
        isCustomOrder: 1,
      },
    });
    return res.redirect('/admin/products.php?created=1');
  } catch (error) {
    return adminPage(req, res, 'product-form', { product: req.body, error: error.message, categories });
  }
}));

app.get('/admin/product_edit.php', requireAdmin, asyncRoute(async (req, res) => {
  if (!/^\d+$/.test(String(req.query.id || ''))) return res.redirect('/admin/products.php');
  const product = await prisma.product.findUnique({ where: { id: BigInt(String(req.query.id)) } });
  if (!product) return res.redirect('/admin/products.php');
  return adminPage(req, res, 'product-form', { product, error: '', categories });
}));

app.post('/admin/product_edit.php', requireAdmin, upload.single('image'), asyncRoute(async (req, res) => {
  const id = String(req.query.id || '');
  if (!/^\d+$/.test(id)) return res.redirect('/admin/products.php');
  const existing = await prisma.product.findUnique({ where: { id: BigInt(id) } });
  if (!existing) return res.redirect('/admin/products.php');
  try {
    const product = parseProductForm(req.body);
    const image = req.file ? await uploadProductImage(req.file) : existing.image;
    await prisma.product.update({ where: { id: existing.id }, data: { ...product, image } });
    return adminPage(req, res, 'product-form', {
      product: { ...existing, ...product, image },
      message: 'Bead creation successfully updated!',
      categories,
    });
  } catch (error) {
    return adminPage(req, res, 'product-form', { product: existing, error: error.message, categories });
  }
}));

app.get('/admin/settings.php', requireAdmin, asyncRoute(async (req, res) => adminPage(req, res, 'settings', {
  settings: await getSettings(),
  message: '',
  error: '',
})));

app.post('/admin/settings.php', requireAdmin, asyncRoute(async (req, res) => {
  const update = {
    store_name: req.body.store_name,
    store_tagline: req.body.store_tagline,
    whatsapp_number: req.body.whatsapp_number,
    contact_phone: req.body.contact_phone,
    contact_location: req.body.contact_location,
    social_handle: req.body.social_handle,
    contact_email: req.body.contact_email,
    bank_info: req.body.bank_info,
  };
  for (const currency of ['GHS', 'NGN', 'GBP', 'EUR']) {
    const value = Number(req.body[`rate_${currency}`]);
    if (!Number.isFinite(value) || value <= 0) {
      return adminPage(req, res, 'settings', { settings: { ...await getSettings(), ...req.body }, error: `Enter a valid ${currency} exchange rate.`, message: '' });
    }
    update[`rate_${currency}`] = String(value);
  }
  if (String(req.body.new_password || '').trim()) {
    update.admin_password = await bcrypt.hash(String(req.body.new_password), 12);
  }
  await prisma.$transaction(Object.entries(update).map(([key, value]) =>
    prisma.setting.upsert({
      where: { key },
      create: { key, value: String(value || '') },
      update: { value: String(value || '') },
    })));
  return adminPage(req, res, 'settings', { settings: await getSettings(), message: 'Ayerko Beads settings successfully updated!', error: '' });
}));

app.use((error, _req, res, _next) => {
  console.error('Application request failed:', error.message);
  if (res.headersSent) return;
  const status = error instanceof multer.MulterError || error.status === 400 || error instanceof SyntaxError ? 400 : 500;
  const message = error instanceof multer.MulterError && error.code === 'LIMIT_FILE_SIZE'
    ? 'Image file size cannot exceed 4MB.'
    : status === 400 ? error.message : 'The request could not be completed. Please try again.';
  if (_req.path.startsWith('/api/')) {
    return res.status(status).json({ success: false, error: message });
  }
  return res.status(status).send(message);
});

module.exports = app;

if (require.main === module) {
  const port = Number(process.env.PORT || 3000);
  app.listen(port, () => console.log(`Ayerko Beads is listening on port ${port}`));
}

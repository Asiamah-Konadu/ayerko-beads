const { after, before, test } = require('node:test');
const assert = require('node:assert/strict');

process.env.DATABASE_URL ||= 'postgresql://test:test@127.0.0.1:5432/test';

const bcrypt = require('bcryptjs');
const product = {
  id: 1n,
  name: 'Krobo Heritage Collar',
  slug: 'krobo-heritage-collar',
  category: 'Krobo Artisan Statement',
  price: 240,
  description: 'Handcrafted in Ghana.',
  materials: 'Powdered glass',
  image: 'assets/images/krobo_statement.jpg',
  stock: 8,
  featured: 1,
  createdAt: new Date(),
};
const order = {
  id: 1n,
  orderNumber: 'AYK-2610-AB12',
  customerName: 'Ama',
  customerPhone: '0243825825',
  customerEmail: null,
  shippingAddress: 'Madina',
  city: 'Accra',
  country: 'Ghana',
  currency: 'GHS',
  totalAmount: 100,
  paymentMethod: 'WhatsApp / MoMo Order',
  paymentStatus: 'Pending',
  orderStatus: 'New',
  courierName: 'Accra Express Courier',
  trackingNumber: null,
  notes: null,
  createdAt: new Date(),
  items: [],
};
const settings = {
  store_name: 'Ayerko Beads',
  store_tagline: 'Handcrafted Ghanaian beads',
  whatsapp_number: '+233243825825',
  contact_phone: '0243825825',
  contact_location: 'Madina, Accra, Ghana',
  rate_GHS: '15.8',
  rate_USD: '1',
  rate_NGN: '1550',
  rate_GBP: '0.78',
  rate_EUR: '0.92',
};
const adminHash = bcrypt.hashSync('test-password', 4);
const settingRows = Object.entries(settings).map(([key, value]) => ({ key, value }));
settingRows.push({ key: 'admin_password', value: adminHash });
const fakePrisma = {
  setting: {
    findMany: async () => settingRows,
    findUnique: async () => ({ value: adminHash }),
  },
  product: {
    findMany: async ({ where } = {}) => where ? [] : [product],
    findUnique: async () => product,
    count: async () => 1,
  },
  order: {
    findFirst: async () => order,
    findMany: async () => [order],
    count: async () => 1,
    aggregate: async () => ({ _sum: { totalAmount: 100 } }),
  },
  $transaction: async (callback) => callback({
    product: {
      findUnique: async () => product,
      update: async () => product,
    },
    order: {
      create: async ({ data }) => ({ ...order, ...data, id: 2n }),
    },
  }),
};
const databaseModule = require.resolve('../lib/db');
require.cache[databaseModule] = {
  id: databaseModule,
  filename: databaseModule,
  loaded: true,
  exports: fakePrisma,
};

const app = require('../server');

let server;
let baseUrl;

before(async () => {
  server = app.listen(0);
  await new Promise((resolve) => server.once('listening', resolve));
  baseUrl = `http://127.0.0.1:${server.address().port}`;
});

after(async () => {
  await new Promise((resolve, reject) => server.close((error) => error ? reject(error) : resolve()));
});

test('renders the admin login route without a database connection', async () => {
  const response = await fetch(`${baseUrl}/admin/login.php`);
  assert.equal(response.status, 200);
  assert.match(await response.text(), /Master Access Password/);
});

test('serves existing storefront assets', async () => {
  const response = await fetch(`${baseUrl}/assets/css/style.css`);
  assert.equal(response.status, 200);
  assert.match(response.headers.get('content-type'), /text\/css/);
});

test('renders the storefront, product details, and order tracker', async () => {
  const storefront = await fetch(`${baseUrl}/`);
  assert.equal(storefront.status, 200);
  assert.match(await storefront.text(), /Krobo Heritage Collar/);

  const productPage = await fetch(`${baseUrl}/product.php?id=1`);
  assert.equal(productPage.status, 200);
  assert.match(await productPage.text(), /Handcrafted in Ghana/);

  const tracker = await fetch(`${baseUrl}/track.php?order=AYK-2610-AB12`);
  assert.equal(tracker.status, 200);
  assert.match(await tracker.text(), /AYK-2610-AB12/);
});

test('rejects incomplete checkout requests with a JSON error', async () => {
  const response = await fetch(`${baseUrl}/api/order.php`, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({}),
  });
  assert.equal(response.status, 400);
  assert.deepEqual(await response.json(), {
    success: false,
    error: 'Please provide client name, phone, address, and at least one item.',
  });
});

test('creates checkout totals from database product prices', async () => {
  const response = await fetch(`${baseUrl}/api/order.php`, {
    method: 'POST',
    headers: { 'content-type': 'application/json' },
    body: JSON.stringify({
      customer_name: 'Ama',
      customer_phone: '0243825825',
      shipping_address: 'Madina',
      currency: 'GHS',
      items: [{ id: '1', name: 'Forged product name', price: 0.01, quantity: 2 }],
    }),
  });
  assert.equal(response.status, 200);
  const result = await response.json();
  assert.equal(result.success, true);
  assert.equal(result.total_amount, 7584);
  assert.equal(result.order_number.startsWith('AYK-'), true);
});

test('authenticates and renders the admin dashboard', async () => {
  const login = await fetch(`${baseUrl}/admin/login.php`, {
    method: 'POST',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ password: 'test-password' }),
    redirect: 'manual',
  });
  assert.equal(login.status, 302);
  const cookies = login.headers.getSetCookie().map((value) => value.split(';')[0]).join('; ');
  const dashboard = await fetch(`${baseUrl}/admin/`, { headers: { cookie: cookies } });
  assert.equal(dashboard.status, 200);
  assert.match(await dashboard.text(), /Ayerko Beads Atelier Dashboard/);

  for (const [route, expected] of [
    ['/admin/orders.php', 'Client Orders'],
    ['/admin/products.php', 'Bead Catalog'],
    ['/admin/product_add.php', 'Upload New Bead Creation'],
    ['/admin/settings.php', 'Store Settings'],
  ]) {
    const response = await fetch(`${baseUrl}${route}`, { headers: { cookie: cookies } });
    assert.equal(response.status, 200, `${route} should render`);
    assert.match(await response.text(), new RegExp(expected));
  }
});

test('redirects the legacy product route when no product is selected', async () => {
  const response = await fetch(`${baseUrl}/product.php`, { redirect: 'manual' });
  assert.equal(response.status, 302);
  assert.equal(response.headers.get('location'), '/index.php');
});

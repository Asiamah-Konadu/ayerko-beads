const prisma = require('./db');

const defaultSettings = {
  store_name: 'Ayerko Beads',
  store_tagline: 'Handcrafted Ghanaian Luxury Beads & Royal Regalia • Madina, Accra',
  artisan_name: 'Master Artisan Ayerko',
  whatsapp_number: '+233243825825',
  contact_phone: '0243825825',
  contact_location: 'Madina, Accra, Ghana',
  social_handle: '@ayerkobeads',
  social_fb: 'https://facebook.com/ayerkobeads',
  social_insta: 'https://instagram.com/ayerkobeads',
  social_tiktok: 'https://tiktok.com/@ayerkobeads',
  contact_email: 'contact@ayerkobeads.com',
  base_currency: 'GHS',
  rate_GHS: '15.8',
  rate_USD: '1.0',
  rate_NGN: '1550.0',
  rate_GBP: '0.78',
  rate_EUR: '0.92',
  bank_info: 'Mobile Money (MoMo) / Bank Transfer:\nNetwork: MTN MoMo / Telecel Cash: 0243825825\nName: Ayerko Beads\nBank: GCB Bank / Ecobank Ghana (Accra Branch)\nLocation: Madina, Accra, Ghana',
};

async function getSettings() {
  const rows = await prisma.setting.findMany();
  return {
    ...defaultSettings,
    ...Object.fromEntries(rows.map(({ key, value }) => [key, value || ''])),
  };
}

function currencySymbol(currency) {
  return ({ GHS: 'GH₵ ', NGN: '₦', GBP: '£', EUR: '€', USD: '$' })[currency] || '$';
}

function formatPrice(amount, currency, settings) {
  const rate = Number(settings[`rate_${currency}`] || 15.8);
  const converted = Number(amount) * rate;
  const decimals = currency === 'NGN' ? 0 : 2;
  return currencySymbol(currency) + converted.toLocaleString('en-US', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  });
}

function whatsappPhone(phone) {
  let clean = String(phone || '').replace(/\D/g, '');
  if (clean.startsWith('0') && clean.length === 10) clean = `233${clean.slice(1)}`;
  return clean;
}

function productWhatsAppUrl(product, currency, settings) {
  const message = [
    '👑 *AYERKO BEADS • LUXURY INQUIRY*',
    '📍 *Location:* Madina, Accra, Ghana',
    '',
    'Hello Ayerko Beads, I am inquiring about this handcrafted creation:',
    `• *Creation:* ${product.name}`,
    `• *Category:* ${product.category}`,
    `• *Price:* ${formatPrice(product.price, currency, settings)} (${currency})`,
    `• *Reference:* #${product.id}`,
    '',
    'I would like to inquire about waist/neck fitting and availability. Thank you!',
  ].join('\n');
  return `https://wa.me/${whatsappPhone(settings.whatsapp_number)}?text=${encodeURIComponent(message)}`;
}

function slugify(text) {
  return text.normalize('NFKD').replace(/[\u0300-\u036f]/g, '')
    .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || `item-${Date.now()}`;
}

function orderNumber() {
  const month = new Date().toISOString().slice(2, 7).replace('-', '');
  return `AYK-${month}-${crypto.randomBytes(2).toString('hex').toUpperCase()}`;
}

const crypto = require('node:crypto');

module.exports = {
  defaultSettings,
  formatPrice,
  getSettings,
  orderNumber,
  productWhatsAppUrl,
  slugify,
  whatsappPhone,
  currencySymbol,
};

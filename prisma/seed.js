const bcrypt = require('bcryptjs');
const prisma = require('../lib/db');
const { defaultSettings } = require('../lib/store');

const sampleProducts = [
  {
    name: 'Krobo Heritage Chevron Imperial Glass Collar',
    slug: 'krobo-heritage-chevron-imperial-collar',
    category: 'Krobo Artisan Statement',
    price: 240,
    description: 'A masterwork from the historic Krobo Hills of Ghana. Crafted using ancient powder-glass furnace techniques, each bead is hand-painted with sacred chevron and geometric motifs in cobalt blue, ochre yellow, and terracotta.',
    materials: 'Recycled Powdered Glass, Hand-Hammered Ashanti Brass Rings, Raw Organic Linen Twine',
    image: 'assets/images/krobo_statement.jpg',
    stock: 8,
  },
  {
    name: 'The Queen Idia Edo Royal Coral Bridal Tier',
    slug: 'queen-idia-edo-coral-bridal-tier',
    category: 'Royal Wedding Coral',
    price: 380,
    description: 'A breathtaking five-tier authentic royal bridal coral neckpiece with crimson coral, gold-filigree spacers, and an ornate imperial pendant.',
    materials: 'Natural Mediterranean Deep Sea Coral, 24k Gold-Gilded Brass Filigree, Reinforced Braided Cord',
    image: 'assets/images/edo_royal_coral.jpg',
    stock: 5,
  },
  {
    name: 'Sovereign Goddess Luxury Waist Beads Set (7-Strand)',
    slug: 'sovereign-goddess-luxury-waist-beads',
    category: 'Luxury Waist Beads',
    price: 165,
    description: 'An opulent set of seven harmonized luxury waist strands, customized to your exact waist measurements.',
    materials: 'Natural Carnelian, Faceted Citrine, 18k Gold Plated Hematite, Indigo Glass Seed Beads, Heavy Duty Cord',
    image: 'assets/images/luxury_waist_beads.jpg',
    stock: 14,
  },
  {
    name: 'The Okuku Crown & Sacred Bridal Earring Suite',
    slug: 'okuku-royal-crown-bridal-suite',
    category: 'Royal Crowns & Headpieces',
    price: 520,
    description: 'A hand-beaded royal crown adorned with brass ornaments and accompanied by chandelier earrings.',
    materials: 'Authentic Polished Red Sea Coral, Hand-Cast Brass Royal Ornaments, Velvet Crown Lining',
    image: 'assets/images/royal_beaded_crown.jpg',
    stock: 3,
  },
  {
    name: 'Imperial Oba Ceremonial Beaded Collar & Wristlet',
    slug: 'imperial-oba-ceremonial-beaded-collar',
    category: 'Royal Wedding Coral',
    price: 310,
    description: 'A commanding royal neckpiece featuring cylindrical red corals with brass medallions and gold accents.',
    materials: 'Heavy Red Cylindrical Corals, Hand-Carved Gye Nyame Brass Symbols, 24k Gold Accents',
    image: 'assets/images/hero_african_beads.jpg',
    stock: 6,
  },
];

async function main() {
  const settings = {
    ...defaultSettings,
    admin_password: await bcrypt.hash(process.env.ADMIN_PASSWORD || 'admin123', 12),
  };

  for (const [key, value] of Object.entries(settings)) {
    await prisma.setting.upsert({
      where: { key },
      update: {},
      create: { key, value },
    });
  }

  if ((await prisma.product.count()) === 0) {
    await prisma.product.createMany({
      data: sampleProducts.map((product) => ({
        ...product,
        featured: 1,
        isCustomOrder: 1,
      })),
    });
  }
}

main()
  .catch((error) => {
    console.error('Prisma seed failed:', error.message);
    process.exitCode = 1;
  })
  .finally(async () => prisma.$disconnect());

require('dotenv').config();

const databaseUrl = process.env.DATABASE_POSTGRES_URL
  || process.env.DATABASE_PRISMA_DATABASE_URL
  || process.env.DATABASE_URL;

if (!databaseUrl) {
  throw new Error(
    'Set DATABASE_POSTGRES_URL, DATABASE_PRISMA_DATABASE_URL, or DATABASE_URL.',
  );
}

process.env.DATABASE_URL = databaseUrl;

const { PrismaClient } = require('@prisma/client');
const { PrismaPg } = require('@prisma/adapter-pg');

const adapter = new PrismaPg({ connectionString: databaseUrl });
const prisma = globalThis.__ayerkoPrisma || new PrismaClient({ adapter });
if (process.env.NODE_ENV !== 'production') {
  globalThis.__ayerkoPrisma = prisma;
}

module.exports = prisma;

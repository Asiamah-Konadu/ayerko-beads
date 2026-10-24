import 'dotenv/config';
import { defineConfig } from 'prisma/config';

const databaseUrl = process.env.DATABASE_POSTGRES_URL
  || process.env.DATABASE_PRISMA_DATABASE_URL
  || process.env.DATABASE_URL;

if (!databaseUrl) {
  throw new Error('Set DATABASE_POSTGRES_URL, DATABASE_PRISMA_DATABASE_URL, or DATABASE_URL.');
}

export default defineConfig({
  schema: 'prisma/schema.prisma',
  datasource: { url: databaseUrl },
});

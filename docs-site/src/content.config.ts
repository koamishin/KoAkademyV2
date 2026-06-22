import { docsSchema } from '@astrojs/starlight/schema';
import { glob } from 'astro/loaders';
import { defineCollection } from 'astro:content';

export const collections = {
    docs: defineCollection({
        loader: glob({ pattern: '**/*.{md,mdx}', base: './src/content/docs' }),
        schema: docsSchema(),
    }),
};

// @ts-check
import starlight from '@astrojs/starlight';
import { defineConfig } from 'astro/config';

// Base path strategy:
//   - Dev (`npm run dev`)  →  BASE_PATH is unset, site runs at `http://localhost:4321/`
//   - GitHub Pages build   →  CI sets BASE_PATH=/KoAkademyV2
//   - Custom domain build  →  set BASE_PATH=/ when deploying to a user/org domain
const base = process.env.BASE_PATH ?? '/';

// https://astro.build/config
export default defineConfig({
    site: 'https://koamishin.github.io',
    base,
    integrations: [
        starlight({
            title: 'KoAkademyV2',
            tagline: 'A modern Laravel + Vue 3 educational platform',
            description:
                'Documentation for KoAkademyV2 — a modular Laravel 13 + Inertia.js + Filament v5 platform for admissions, enrollment, classroom, and blog workflows.',
            logo: {
                src: './public/logo.svg',
                replacesTitle: false,
            },
            favicon: './public/favicon.svg',
            social: [
                {
                    icon: 'github',
                    label: 'GitHub',
                    href: 'https://github.com/koamishin/KoAkademyV2',
                },
            ],
            editLink: {
                baseUrl:
                    'https://github.com/koamishin/KoAkademyV2/edit/master/docs-site/',
            },
            sidebar: [
                {
                    label: 'Start Here',
                    items: [
                        { slug: 'index', label: 'Introduction' },
                        {
                            slug: 'getting-started/installation',
                            label: 'Installation',
                        },
                        {
                            slug: 'getting-started/configuration',
                            label: 'Configuration',
                        },
                        {
                            slug: 'getting-started/local-development',
                            label: 'Local Development',
                        },
                    ],
                },
                {
                    label: 'Architecture',
                    items: [
                        { slug: 'architecture/overview', label: 'Overview' },
                        {
                            slug: 'architecture/directory-structure',
                            label: 'Directory Structure',
                        },
                        {
                            slug: 'architecture/modules',
                            label: 'Module System',
                        },
                    ],
                },
                {
                    label: 'Guides',
                    items: [
                        {
                            slug: 'guides/core-concepts',
                            label: 'Core Concepts',
                        },
                        { slug: 'guides/workflows', label: 'Workflows' },
                        {
                            slug: 'guides/customization',
                            label: 'Branding & Customization',
                        },
                        {
                            slug: 'guides/extensibility',
                            label: 'Extensibility',
                        },
                        { slug: 'guides/security', label: 'Security' },
                        {
                            slug: 'guides/development',
                            label: 'Development Guide',
                        },
                        { slug: 'guides/ci-cd', label: 'CI/CD & Containers' },
                    ],
                },
                {
                    label: 'Modules',
                    items: [
                        { slug: 'modules/admissions', label: 'Admissions' },
                        { slug: 'modules/enrollment', label: 'Enrollment' },
                        { slug: 'modules/classroom', label: 'Classroom' },
                        { slug: 'modules/blog', label: 'Blog' },
                    ],
                },
                {
                    label: 'Reference',
                    items: [
                        { slug: 'reference/examples', label: 'Code Examples' },
                        { slug: 'reference/tech-stack', label: 'Tech Stack' },
                    ],
                },
            ],
            customCss: ['./src/styles/custom.css'],
        }),
    ],
});

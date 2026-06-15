# KoAkademyV2 Documentation

This directory contains the public documentation site for KoAkademyV2, built with
[Astro Starlight](https://starlight.astro.build) and deployed to **GitHub Pages**.

🌐 **Live site:** https://koamishin.github.io/KoAkademyV2/

## Stack

- [Astro 5](https://astro.build)
- [Starlight 0.36](https://starlight.astro.build)
- MDX content under `src/content/docs/`
- Deployed via the GitHub Actions workflow at `.github/workflows/deploy.yml`

## Local Development

```bash
cd docs-site
npm install
npm run dev          # http://localhost:4321
```

By default the dev server runs at the site root (`/`). No base path prefix is needed locally.

The deployed site lives under `https://<org>.github.io/<repo>/` because GitHub Pages serves project sites from a sub-path. The deploy workflow passes `BASE_PATH=/KoAkademyV2` to the build step automatically — you should not need to touch it.

## Build

```bash
npm run build        # produces dist/
npm run preview      # serves the built site locally
```

## Deployment

The site is deployed automatically on every push to `master` that touches files
under `docs-site/`. See `.github/workflows/deploy.yml` for details.

To deploy from a fork, enable GitHub Pages in your repo's settings:

1. **Settings → Pages → Build and deployment → Source:** GitHub Actions
2. Push to the default branch — the workflow will build and publish the site.

The site is configured with `base: '/KoAkademyV2'` (see `astro.config.mjs`).
If your repo is named something else, update that value.

## Project Structure

```text
docs-site/
├── astro.config.mjs           # Starlight + Vite config
├── package.json
├── tsconfig.json
├── .github/workflows/
│   └── deploy.yml             # GitHub Pages deployment
├── public/                    # Static assets copied as-is
└── src/
    ├── assets/                # Logo, favicon
    ├── content/docs/          # 📝 All documentation lives here
    │   ├── intro.mdx          # Landing page
    │   ├── getting-started/   # Install, configure, run locally
    │   ├── architecture/      # Overview, structure, modules
    │   ├── guides/            # How-to articles
    │   ├── modules/           # Per-module reference
    │   └── reference/         # Examples, tech stack
    └── styles/custom.css      # Brand color overrides
```

## Adding a Page

1. Create a new `.mdx` file under `src/content/docs/<section>/`.
2. Add the page to the sidebar in `astro.config.mjs` (or rely on auto-discovery if you set `autogenerate`).
3. Commit & push — the workflow will deploy the new content.

## Conventions

- File names: **kebab-case** (e.g. `local-development.mdx`).
- One H1 per file, defined by the frontmatter `title`.
- Use Starlight's built-in components (`Card`, `CardGrid`, `Tabs`, `Steps`, `LinkCard`, `Aside`, `Badge`) for richer layouts.
- Code fences must include a language: ` ```ts `, ` ```php `, ` ```bash `.
- Mermaid diagrams use ```` ```mermaid ```` blocks.

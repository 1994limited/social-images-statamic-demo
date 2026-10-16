# Dog-ear Books: a Social Images demo

A made-up independent bookshop for trying [Social Images](https://github.com/1994limited/social-images-statamic): twelve books, whose share images use a code template showing each cover, and a journal of posts with writers, dates and pictures, using the Editorial designer template.

The book covers and journal pictures are drawn by `demo:content` (with GD), so the demo needs no photos.

## Running it

Social Images is installed from [its GitHub repository](https://github.com/1994limited/social-images-statamic) until it's on Packagist.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
npm install
php artisan demo:users
php artisan demo:content
php artisan serve
```

Sign in at `/cp` as `admin@example.com` with the password `password`. `demo:users` refuses to run unless `APP_ENV=local`, since its password is known.

Images are rendered with Browsershot. Point it at your Chrome, Node and npm in `.env` (see `.env.example`), or use Cloudflare instead (see the addon's docs/rendering.md). Then make them with **Tools → Social Images → Images → Generate missing**, or:

```bash
php please social-images:generate
```

## Screenshots

`node scripts/screenshots.mjs` takes the screenshots in the addon's docs, from a running demo with its images made. It writes to `../social-images-statamic/docs/images`, so run it with the addon checked out beside the demo (or set `SCREENSHOTS`).

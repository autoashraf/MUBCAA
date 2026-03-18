# CI/CD Setup

This project includes GitHub Actions workflows for:

- `CI`: run Laravel tests
- `Deploy via cPanel Git`: reminder workflow for the hosting setup used by this project

## Workflows

- `/.github/workflows/ci.yml`
- `/.github/workflows/deploy-ssh.yml`

## Recommended cPanel Target

Best option:

- point the domain/subdomain document root to Laravel `public/`

If you cannot do that and you are serving Laravel from project root with `.htaccess`, deploy the full project to the target directory and keep the root rewrite setup in place.

## Deployment Flow

### CI

Runs on:

- push to `main`, `master`, `develop`
- all pull requests

It does:

1. install Composer dependencies
2. generate Laravel app key for CI
3. run `php artisan test`

### CD

Runs on:

- manual trigger from GitHub Actions

It does:

1. reminds contributors that this project deploys through cPanel Git Version Control
2. avoids broken SSH deploy attempts on hosting accounts without shell access

## Important Notes

- the server must already have:
  - the project directory created
  - the `.env` file in place
  - correct writable permissions for `storage` and `bootstrap/cache`
- this project’s current Composer lockfile requires PHP `8.2+`
  - the GitHub Actions workflows now use PHP `8.2`
  - your cPanel runtime should also be changed from `ea-php81` to `ea-php82`
- this workflow assumes the project is running without a required Node/Vite production build step
- this hosting account does not allow shell access, so server-side deployment must happen through cPanel features instead of SSH automation

## cPanel Git Deployment

Use cPanel `Git Version Control` for the live site at:

- `/home/mubcaa/site.mubcaa.com`

Recommended setup:

1. In cPanel, open `Git Version Control`
2. Create or manage a repository in `/home/mubcaa/site.mubcaa.com`
3. Connect it to your GitHub repository
4. Set the production branch to `main`
5. Use cPanel's `Pull or Deploy` action after each push

Important:

- the live code updates from the repository only after cPanel performs the pull/deploy
- GitHub Actions `CI` will still test the code before or alongside that process
- keep `.env` only on the server
- keep writable folders correct for `storage` and `bootstrap/cache`
- if the domain is not pointed at `public/`, keep the root `.htaccess` / root `index.php` workaround in place

## Optional Next Step

If you want, the next improvement is:

- add a cPanel deployment guide for post-pull Laravel cache refresh
- add a simple webhook or manual checklist for production updates

# CI/CD Setup

This project includes GitHub Actions workflows for:

- `CI`: run Laravel tests and build frontend assets
- `Deploy via SSH`: build production assets, upload them over SSH, and run Laravel deploy commands on the server

## Workflows

- `/.github/workflows/ci.yml`
- `/.github/workflows/deploy-ssh.yml`

## GitHub Secrets Required For SSH Deploy

Add these repository secrets in GitHub:

- `SSH_PRIVATE_KEY`
  Your private SSH key content

This project is already configured for:

- SSH host: `161.248.201.7`
- SSH username: `mubcaa`
- app directory: `/home/mubcaa/site.mubcaa.com`

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
4. install npm dependencies
5. build Vite assets

### CD

Runs on:

- push to `main`
- manual trigger from GitHub Actions

It does:

1. install production Composer dependencies
2. install npm dependencies
3. build Vite assets
4. upload the app to the server using SCP over SSH
5. run Laravel deploy commands on the server

## Important Notes

- the server must already have:
  - the project directory created
  - the `.env` file in place
  - correct writable permissions for `storage` and `bootstrap/cache`
- the workflow currently connects on SSH port `22`
  - if your server uses a different SSH port, update [deploy-ssh.yml](/var/www/MUBCAA/.github/workflows/deploy-ssh.yml)
- `vendor` is uploaded as part of the built release
- the workflow currently runs:
  - `php artisan optimize:clear`
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan view:cache`
  - `php artisan migrate --force`

## Optional Next Step

If you want, the next improvement is:

- zero-downtime release folders with symlink switching
- separate `staging` and `production` SSH workflows

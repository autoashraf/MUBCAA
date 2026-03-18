# CI/CD Setup

This project includes GitHub Actions workflows for:

- `CI`: run Laravel tests
- `Deploy via SSH`: upload the Laravel app over SSH and run deploy commands on the server

## Workflows

- `/.github/workflows/ci.yml`
- `/.github/workflows/deploy-ssh.yml`

## GitHub Secrets Required For SSH Deploy

Add these repository secrets in GitHub:

- `SSH_PRIVATE_KEY`
  Your private SSH key content
- `SSH_PASSPHRASE`
  The passphrase used by that private key, if the key is protected

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

### CD

Runs on:

- push to `main`
- manual trigger from GitHub Actions

It does:

1. install production Composer dependencies
2. prepare the SSH key in the GitHub runner
3. create a release archive
4. upload the release to the server using native `scp`
5. connect over native `ssh`, extract the release, and run Laravel deploy commands

## Important Notes

- the server must already have:
  - the project directory created
  - the `.env` file in place
  - correct writable permissions for `storage` and `bootstrap/cache`
- this project’s current Composer lockfile requires PHP `8.2+`
  - the GitHub Actions workflows now use PHP `8.2`
  - your cPanel runtime should also be changed from `ea-php81` to `ea-php82`
- this workflow assumes the project is running without a required Node/Vite production build step
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

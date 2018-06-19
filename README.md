# iota

## Installation

```sh
composer install
yarn install
```

## Building assets

For development:

```sh
yarn run encore dev --watch
```

For production:

```sh
yarn run encore production
```

## Deploying

Create and edit `hosts.yaml` (cf. https://deployer.org/docs/hosts).

### Production

```sh
./vendor/bin/dep deploy production
```

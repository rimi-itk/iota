# iota

## Installation

```sh
composer install
yarn install
```

Building assets:

```sh
yarn run encore production
```

## Deploying

Create and edit `hosts.yaml` (cf. https://deployer.org/docs/hosts).

### Production

```sh
./vendor/bin/dep deploy production
```

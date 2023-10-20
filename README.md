# Magento 2 Single Sign-On

This Magento 2 extension allows store administrators to log in to their Magento backend account using a SAML compatible Single Sign-on identity provider, such as Auth0, OneLogin or Amazon SSO.

## Installation

The recommended way to install this extension is via composer:

First you will need to make sure this GIT repo is in the composer list of repositories.  Edit the `composer.json` and add:
```json
        "d-hansen_magento2-sso": {
            "type": "vcs"
            "url": "https://github.com/d-hansen/magento2-sso",
        }
```
to the "repositories" property.

Then you can do:
```shell
composer require d-hansen/magento2-sso:v1.2.0
```

---

### Alternate manual installation

Setup a copy of this repository under `app/code/Space48/SSO`.  You may want to consider using a git submodule, but not required.
You will then also need to edit the composer.json to add the following to the "autoload"->"psr-4" property:
```json
    "autoload": {
        "psr-4": {
	    ...,
            "Space48\\SSO\\": "app/code/Space48/SSO/src"
        },
```
as well as then add the dependent package for OneLogin\Saml2:
```shell
composer require onelogin/php-saml:^4.0
```

## Configuration

The extension functionality is disabled by default.

To enable it and configure your Identity Provider, visit the `Stores > Configuration > Advanced > Admin > Single Sign-on` section in the Magento backend.

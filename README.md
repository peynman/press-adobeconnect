# Larapress ECommerce AdobeConnect API Integration
This package adds AdobeConnect as a new product type to [Larapress ECommerce](../../../press-crud).

## Dependencies
* [Larapress ECommerce](../../../press-ecommerce)
* [Larapress LMS](../../../press-lms)

## Install
1. ```composer require peynman/larapress-adobeconnect```

## Config
1. Publish config ```php artisan vendor:publish --tag=larapress-adobeconnect```
1. Create/Update AdobeConnect product type ```php artisan lp:ac:create-pc```

## Usage

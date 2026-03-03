# mTLS Connection Helper for WordPress

A WordPress helper library for establishing secure mTLS (mutual TLS) connections in WordPress applications.

## Description

This plugin provides a helper for implementing mutual TLS (mTLS) authentication in WordPress HTTP requests. mTLS adds an extra layer of security by requiring both the client and server to authenticate each other using SSL/TLS certificates.

It a wrapper for the [`wp_remote_request()` function](https://developer.wordpress.org/reference/functions/wp_remote_request/), so check the documentation to see default arguments.

## Installation

### Standard

Download this repo and upload to your plugins or mu-plugins directory, and activate or include the plugin file respectively.

### Via Composer

```bash
composer require altis/wp-http-mtls
```

## Usage

Ensure you have the 3 required certificate files. These can be provided either as committed files, or via environment secrets.

### With cert files

In your `.config` directory add the following:

- `.config/certs/mtls.crt` - certificate file.
- `.config/certs/mtls.key` - certificate signing key file.
- `.config/certs/mtls.pem` - certificate authority information file.

### With env vars (requires PHP >= 8.2)

If setting via the Altis dashboard use the following names:

- `MTLS_CLIENT_CERT`
- `MTLS_CLIENT_KEY`
- `MTLS_CLIENT_CAINFO`

If setting by some other means prefix with `USER_`:

- `USER_MTLS_CLIENT_CERT`
- `USER_MTLS_CLIENT_KEY`
- `USER_MTLS_CLIENT_CAINFO`

### Making requests

```php
// Equivalent to `wp_remote_request()`.
$response = Altis\mTLS\request( $url, [
  'method' => 'POST',
] );
```

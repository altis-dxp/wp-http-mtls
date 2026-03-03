<?php
/**
 * Plugin Name: Altis Mutual TLS API Connection Helper
 * Description: Provides helper functions for making API requests with mTLS
 * Author:      Human Made Limited
 * License:     GPL-3.0
 * Version:     1.0.0
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt -- Intentional to add settings not available via WP_HTTP
 */

namespace Kaplan\MTLS;

use Altis;
use WP_Error;

/**
 * Fetch the mTLS certificates from env or local filesystem.
 *
 * @return array|null
 */
function get_certs() : ?array {
	static $certs = null;

	if ( $certs ) {
		return $certs;
	}

	// Check application secrets store.
	if ( getenv( 'USER_MTLS_CLIENT_CERT' ) ) {
		$certs = [
			'cert' => getenv( 'USER_MTLS_CLIENT_CERT' ),
			'key' => getenv( 'USER_MTLS_CLIENT_KEY' ),
			'cainfo' => getenv( 'USER_MTLS_CLIENT_CAINFO' ),
		];
	}

	// Check local file system.
	if ( empty( $certs ) && is_readable( Altis\ROOT_DIR . '/.config/certs/mtls.crt' ) ) {
		$certs = [
			'cert' => Altis\ROOT_DIR . '/.config/certs/mtls.crt',
			'key' => Altis\ROOT_DIR . '/.config/certs/mtls.key',
			'cainfo' => Altis\ROOT_DIR . '/.config/certs/mtls.pem',
		];
	}

	if ( empty( $certs ) ) {
		if ( Altis\get_environment_type() === 'local' ) {
			trigger_error( 'Could not find mTLS certificates. Please ensure the mtls.crt, mtls.key and mtls.pem files have been added to .config/certs.', E_USER_WARNING );
		} else {
			trigger_error( 'Could not find mTLS certificates. Please ensure the MTLS_CLIENT_CERT, MTLS_CLIENT_KEY and MTLS_CLIENT_CAINFO secrets are set in the Altis dashboard and a redeploy has been done.', E_USER_WARNING );
		}
	}

	return $certs;
}

/**
 * Adds the mTLS certificates to the WP_HTTP curl request.
 *
 * @param \CurlHandle $handle The curl handle.
 * @param array $args The wp_remote_request args array.
 * @return void
 */
function add_certs( &$handle, $args ) {
	if ( ! isset( $args['_add_mtls_certs'] ) ) {
		return;
	}

	$certs = get_certs();
	curl_setopt( $handle, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2 );
	curl_setopt( $handle, is_readable( $certs['cert'] ) ? CURLOPT_SSLCERT : CURLOPT_SSLCERT_BLOB, $certs['cert'] );
	curl_setopt( $handle, is_readable( $certs['key'] ) ? CURLOPT_SSLKEY : CURLOPT_SSLKEY_BLOB, $certs['key'] );
	curl_setopt( $handle, is_readable( $certs['cainfo'] ) ? CURLOPT_CAINFO : CURLOPT_CAINFO_BLOB, $certs['cainfo'] );
}

/**
 * Make a request with mTLS certificates.
 *
 * @param string $url The URL to request.
 * @param array $args Args for `wp_remote_request()`.
 * @return \WP_HTTP_Response|WP_Error
 */
function request( string $url, array $args = [] ) {

	$args = wp_parse_args( $args, [
		'_add_mtls_certs' => true,
	] );

	add_action( 'http_api_curl', __NAMESPACE__ . '\\add_certs', 10, 2 );

	$response = wp_remote_request( $url, $args );

	remove_action( 'http_api_curl', __NAMESPACE__ . '\\add_certs', 10, 2 );

	if ( is_wp_error( $response ) ) {
		error_log( 'mTLS request failed: ' . $response->get_error_message() );
		return new WP_Error( 'mtls-request-failed', $response->get_error_message() );
	}

	return $response;
}

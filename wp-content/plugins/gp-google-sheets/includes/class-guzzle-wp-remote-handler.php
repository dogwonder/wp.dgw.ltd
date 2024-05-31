<?php
/**
 * Credit: Yoast SEO, https://github.com/Yoast/wordpress-seo/blob/trunk/src/wrappers/wp-remote-handler.php
 */

namespace GP_Google_Sheets;

use Exception;
use GP_Google_Sheets\Dependencies\GuzzleHttp\Promise\FulfilledPromise;
use GP_Google_Sheets\Dependencies\GuzzleHttp\Promise\PromiseInterface;
use GP_Google_Sheets\Dependencies\GuzzleHttp\Promise\RejectedPromise;
use GP_Google_Sheets\Dependencies\GuzzleHttp\Psr7\Response;
use GP_Google_Sheets\Dependencies\Psr\Http\Message\RequestInterface;

/**
 * Wraps wp_remote_get in an interface compatible with Guzzle.
 */
class Guzzle_WP_Remote_Handler {

	/**
	 * Calls the handler.
	 * Cookies are currently not supported as they are not used by OAuth.
	 * Writing responses to files is also not supported for the same reason.
	 *
	 * @param RequestInterface $request The request.
	 * @param array            $options The request options.
	 *
	 * @return PromiseInterface The promise interface.
	 *
	 * @phpstan-ignore-next-line
	 * @throws Exception If the request fails.
	 */
	public function __invoke( RequestInterface $request, array $options ) {
		$headers = array();
		foreach ( $request->getHeaders() as $name => $values ) {
			$headers[ $name ] = \implode( ',', $values );
		}

		$args = array(
			'method'      => $request->getMethod(),
			'headers'     => $headers,
			'body'        => (string) $request->getBody(),
			'httpVersion' => $request->getProtocolVersion(),
		);

		if ( isset( $options['verify'] ) && $options['verify'] === false ) {
			$args['sslverify'] = false;
		}

		if ( isset( $options['timeout'] ) ) {
			$args['timeout'] = $options['timeout'];
		}

		$raw_response = \wp_remote_request( (string) $request->getUri(), $args );
		if ( \is_wp_error( $raw_response ) ) {
			$exception = new Exception( $raw_response->get_error_message() );
			return new RejectedPromise( $exception );
		}

		$response = new Response(
			$raw_response['response']['code'],
			$raw_response['headers']->getAll(),
			$raw_response['body'],
			$args['httpVersion'],
			$raw_response['response']['message']
		);

		return new FulfilledPromise( $response );
	}
}

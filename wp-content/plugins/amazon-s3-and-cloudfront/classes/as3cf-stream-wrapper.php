<?php

class AS3CF_Stream_Wrapper extends Aws\S3\StreamWrapper {

	/**
	 * Register the 's3://' stream wrapper
	 *
	 * @param Aws\S3\S3Client $client
	 * @param string          $protocol
	 */
	public static function register( Aws\S3\S3Client $client, $protocol = 's3' ) {
		if ( in_array( $protocol, stream_get_wrappers() ) ) {
			stream_wrapper_unregister( $protocol );
		}

		stream_wrapper_register( $protocol, __CLASS__, STREAM_IS_URL );
		static::$client = $client;
	}

	/**
	 * Override the getting the bucket and key from the passed path (e.g. s3://bucket/key)
	 * allowing for other protocols.
	 *
	 * @param string $path Path passed to the stream wrapper
	 *
	 * @return array Hash of 'Bucket', 'Key', and custom params
	 */
	protected function getParams( $path ) {
		// Strip the protocol, don't assume it's 5 characters
		$path  = substr( $path, strpos( $path, '://' ) + 3 );
		$parts = explode( '/', $path, 2 );

		$params = $this->getOptions();
		unset( $params['seekable'] );

		return array(
			       'Bucket' => $parts[0],
			       'Key'    => isset( $parts[1] ) ? $parts[1] : null,
		       ) + $params;
	}

	/**
	 * Overrides so we don't check for stat on directories
	 *
	 * @param string $path
	 * @param int    $flags
	 *
	 * @return array
	 */
	public function url_stat( $path, $flags ) {
		$extension = pathinfo( $path, PATHINFO_EXTENSION );
		// If the path is a directory then return it as always existing.
		if ( ! $extension ) {
			return array(
				0         => 0,
				'dev'     => 0,
				1         => 0,
				'ino'     => 0,
				2         => 16895,
				'mode'    => 16895,
				3         => 0,
				'nlink'   => 0,
				4         => 0,
				'uid'     => 0,
				5         => 0,
				'gid'     => 0,
				6         => -1,
				'rdev'    => -1,
				7         => 0,
				'size'    => 0,
				8         => 0,
				'atime'   => 0,
				9         => 0,
				'mtime'   => 0,
				10        => 0,
				'ctime'   => 0,
				11        => -1,
				'blksize' => -1,
				12        => -1,
				'blocks'  => -1,
			);
		}

		return parent::url_stat( $path, $flags );
	}

	/**
	 * Override the S3 Put Object arguments
	 *
	 * @return bool
	 */
	public function stream_flush() {
		// Set the ACL as public by default
		$this->params['ACL'] = Amazon_S3_And_CloudFront::DEFAULT_ACL;

		$this->params = apply_filters( 'wpos3_stream_flush_params', $this->params );

		return parent::stream_flush();
	}
}
<?php

namespace SMW\Elastic\Indexer\Attachment;

use File;
use FileBackend;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\FileRepoFinder;
use Title;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class FileHandler {

	use LoggerAwareTrait;

	/**
	 * Transform the content to base64
	 */
	const FORMAT_BASE64 = 'format/base64';

	/**
	 * @var FileRepoFinder
	 */
	private $fileRepoFinder;

	/**
	 * @var callable
	 */
	private $readCallback;

	/**
	 * @param FileRepoFinder $fileRepoFinder
	 * @since 3.2
	 *
	 */
	public function __construct( FileRepoFinder $fileRepoFinder ) {
		$this->fileRepoFinder = $fileRepoFinder;
	}

	/**
	 * @param callable $readCallback
	 * @since 3.2
	 *
	 */
	public function setReadCallback( callable $readCallback ) {
		$this->readCallback = $readCallback;
	}

	/**
	 * @param Title $title
	 *
	 * @return string
	 * @since 3.2
	 *
	 */
	public function findFileByTitle( Title $title ) {
		return $this->fileRepoFinder->findFile( $title );
	}

	/**
	 * @param File $file
	 *
	 * @return string
	 * @since 5.0.3
	 *
	 */
	public function fetchContentFromFile( File $file ): string
	{
		$be = $file->getRepo()->getBackend();

		$content = '';

		if ( $be instanceof FileBackend ) {
			$content = $be->getFileContents( [ 'src' => $file->getPath() ] ) ?: '';
		}

		return $content;
	}

	/**
	 * @param string $url
	 *
	 * @return string
	 * @since 3.2
	 *
	 */
	public function fetchContentFromURL( string $url ): string
	{
		// PHP 7.1+
		$readCallback = $this->readCallback;

		if ( $this->readCallback !== null ) {
			return $readCallback( $url );
		}

		$contents = '';

		// Avoid a "failed to open stream: HTTP request failed! HTTP/1.1 404 Not Found"
		$file_headers = @get_headers( $url );

		if (
			$file_headers !== false &&
			$file_headers[0] !== 'HTTP/1.1 404 Not Found' &&
			$file_headers[0] !== 'HTTP/1.0 404 Not Found' ) {
			return file_get_contents( $url );
		}

		$this->logger->info(
			[ 'File indexer', 'HTTP/1.1 404 Not Found', '{url}' ],
			[ 'method' => __METHOD__, 'role' => 'production', 'url' => $url ]
		);

		return $contents;
	}

	/**
	 * @param string $contents
	 * @param string $type
	 *
	 * @return string
	 * @since 3.2
	 *
	 */
	public function format( string $contents, string $type = '' ): string {
		if ( $type === self::FORMAT_BASE64 ) {
			return base64_encode( $contents );
		}

		return $contents;
	}

}

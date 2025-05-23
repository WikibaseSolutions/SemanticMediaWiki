<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\MissingRedirectLookup;

/**
 * @covers \SMW\SQLStore\Lookup\MissingRedirectLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class MissingRedirectLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			MissingRedirectLookup::class,
			new MissingRedirectLookup( $this->store )
		);
	}

	public function testFindMissingRedirects() {
		$tables = [
			'page',
			'smw_fpt_redi'
		];

		$fields = [
			'page_id',
			'page_title',
			'page_namespace'
		];

		$conditions = [
			// Required for the LEFT JOIN to find all rows that don't exist
			// in the redirect table
			's_title IS NULL',
			'page_is_redirect' => 1,

			// @see difference to `NamespaceMatrix`
			'page_namespace' => [ NS_MAIN, SMW_NS_PROPERTY ]
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$tables,
				$fields,
				$conditions );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new MissingRedirectLookup(
			$this->store
		);

		$instance->setNamespaceMatrix(
			[
				NS_MAIN => true,
				NS_HELP => false,
				SMW_NS_PROPERTY => true
			]
		);

		$instance->findMissingRedirects();
	}

}

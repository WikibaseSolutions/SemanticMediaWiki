<?php

namespace SMW\Tests\SQLStore\EntityStore;

use Onoi\Cache\FixedInMemoryLruCache;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\CacheWarmer;

/**
 * @covers \SMW\SQLStore\EntityStore\CacheWarmer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class CacheWarmerTest extends \PHPUnit\Framework\TestCase {

	private $idCacheManager;
	private $store;
	private $cache;

	protected function setUp(): void {
		$this->idCacheManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\IdCacheManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = new FixedInMemoryLruCache();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			CacheWarmer::class,
			new CacheWarmer( $this->store, $this->idCacheManager )
		);
	}

	public function testPrepareCache_Page() {
		$list = [
			new DIWikiPage( 'Bar', NS_MAIN )
		];

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'Foo',
			'smw_sort' => '',
		];

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				[ 'smw_hash' => [ '7b6b944694382bfab461675f40a2bda7e71e68e3' ] ] )
			->willReturn( [ (object)$row ] );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->prepareCache( $list );
	}

	public function testPrepareCache_DisplayTitleFinder() {
		$displayTitleFinder = $this->getMockBuilder( '\SMW\DisplayTitleFinder' )
			->disableOriginalConstructor()
			->getMock();

		$displayTitleFinder->expects( $this->once() )
			->method( 'prefetchFromList' );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setDisplayTitleFinder( $displayTitleFinder );
		$instance->setThresholdLimit( 1 );

		$instance->prepareCache( [] );
	}

	public function testPrepareCache_Property() {
		$list = [
			// Both represent the same object hence only cache once
			new DIProperty( 'Foo' ),
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY )
		];

		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sortkey' => 'Foo',
			'smw_sort' => '',
		];

		$this->idCacheManager->expects( $this->once() )
			->method( 'setCache' );

		$this->idCacheManager->expects( $this->any() )
			->method( 'get' )
			->willReturn( $this->cache );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'select' )
			->with(
				$this->anything(),
				$this->anything(),
				[ 'smw_hash' => [ '909d8ab26ea49adb7e1b106bc47602050d07d19f' ] ] )
			->willReturn( [ (object)$row ] );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->prepareCache( $list );
	}

	public function testPrepareCache_UnknownPredefinedProperty() {
		$list = [
			new DIWikiPage( '_Foo', SMW_NS_PROPERTY )
		];

		$this->idCacheManager->expects( $this->never() )
			->method( 'setCache' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new CacheWarmer(
			$this->store,
			$this->idCacheManager
		);

		$instance->setThresholdLimit( 1 );
		$instance->prepareCache( $list );
	}

}

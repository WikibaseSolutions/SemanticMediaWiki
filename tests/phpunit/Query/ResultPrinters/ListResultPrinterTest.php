<?php
namespace SMW\Tests\Query\ResultPrinters;

use SMW\Localizer\Message;
use SMW\Query\ResultPrinters\ListResultPrinter;

/**
 * @covers \SMW\Query\ResultPrinters\ListResultPrinter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author Máté Szabó
 * @author Stephan Gambke
 */
class ListResultPrinterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider allFormatsProvider
	 * @param string $format
	 */
	public function testCanConstruct( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$this->assertInstanceOf( '\SMW\Query\ResultPrinters\ListResultPrinter', $listResultPrinter );
	}

	/**
	 * @dataProvider allFormatsProvider
	 * @param string $format
	 */
	public function testSupportsRecursiveAnnotation( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$this->assertTrue( $listResultPrinter->supportsRecursiveAnnotation() );
	}

	/**
	 * @dataProvider allFormatsProvider
	 * @param string $format
	 */
	public function testIsDeferrable( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$this->assertTrue( $listResultPrinter->isDeferrable() );
	}

	/**
	 * @dataProvider allFormatsProvider
	 * @param string $format
	 */
	public function testGetName( $format ) {
		$listResultPrinter = new ListResultPrinter( $format );

		$this->assertEquals( Message::get( 'smw_printername_' . $format ), $listResultPrinter->getName() );
	}

	public function allFormatsProvider() {
		yield [ 'ul' ];
		yield [ 'ol' ];
		yield [ 'template' ];
		yield [ 'list' ];
		yield [ 'plainlist' ];
	}
}

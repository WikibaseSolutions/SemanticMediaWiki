<?php

namespace SMW\Tests\DataValues\ValueFormatters;

use SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DispatchingDataValueFormatterTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter',
			new DispatchingDataValueFormatter()
		);
	}

	public function testGetDataValueFormatterForMatchableDataValue() {
		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( true );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testGetDefaultDispatchingDataValueFormatterForMatchableDataValue() {
		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( true );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDefaultDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testPrioritizeDispatchableDataValueFormatter() {
		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( true );

		$defaultDataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$defaultDataValueFormatter->expects( $this->never() )
			->method( 'isFormatterFor' );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDefaultDataValueFormatter( $defaultDataValueFormatter );
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testTryToGetDataValueFormatterForNonDispatchableDataValueThrowsException() {
		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->willReturn( false );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDataValueFormatter();
		$instance->addDataValueFormatter( $dataValueFormatter );

		$this->expectException( 'RuntimeException' );
		$instance->getDataValueFormatterFor( $dataValue );
	}

}

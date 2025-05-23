<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\PropertyValue;

/**
 * @covers \SMW\DataValues\PropertyValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyValueTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyValue::class,
			new PropertyValue()
		);
	}

	/**
	 * @dataProvider featuresProvider
	 */
	public function testOptions( $options, $expected ) {
		$instance = new PropertyValue();
		$instance->setOption( 'smwgDVFeatures', $options );

		$this->assertEquals(
			$expected,
			$instance->getOption( 'smwgDVFeatures' )
		);
	}

	public function featuresProvider() {
		$provider[] = [
			SMW_DV_PROV_REDI,
			true
		];

		$provider[] = [
			SMW_DV_NONE | SMW_DV_PROV_REDI,
			true
		];

		$provider[] = [
			SMW_DV_NONE,
			false
		];

		$provider[] = [
			false,
			false
		];

		return $provider;
	}

}

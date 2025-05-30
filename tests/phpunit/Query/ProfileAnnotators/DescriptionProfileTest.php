<?php

namespace SMW\Tests\Query\ProfileAnnotators;

use SMW\DataModel\ContainerSemanticData;
use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator;
use SMW\Query\ProfileAnnotators\NullProfileAnnotator;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIContainer as DIContainer;

/**
 * @covers \SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DescriptionProfileTest extends \PHPUnit\Framework\TestCase {

	private $semanticDataValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {
		$profileAnnotator = $this->getMockBuilder( '\SMW\Query\ProfileAnnotator' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator',
			new DescriptionProfileAnnotator( $profileAnnotator, $description )
		);
	}

	public function testCreateProfile() {
		$subject = new DIWikiPage( __METHOD__, NS_MAIN, '', 'foo' );

		$container = new DIContainer(
			new ContainerSemanticData( $subject	)
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getQueryString' )
			->willReturn( 'Foo' );

		$description->expects( $this->once() )
			->method( 'getSize' )
			->willReturn( 2 );

		$description->expects( $this->once() )
			->method( 'getDepth' )
			->willReturn( 42 );

		$instance = new DescriptionProfileAnnotator(
			new NullProfileAnnotator( $container ),
			$description
		);

		$instance->addAnnotation();

		$expected = [
			'propertyCount'  => 3,
			'propertyKeys'   => [ '_ASKST', '_ASKSI', '_ASKDE' ],
			'propertyValues' => [ 'Foo', 2, 42 ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getContainer()->getSemanticData()
		);
	}

}

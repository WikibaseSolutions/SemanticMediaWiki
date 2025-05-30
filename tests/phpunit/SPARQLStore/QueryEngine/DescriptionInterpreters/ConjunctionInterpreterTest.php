<?php

namespace SMW\Tests\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreterFactory;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter;
use SMW\Tests\Utils\UtilityFactory;
use SMWDIBlob as DIBlob;
use SMWExporter;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ConjunctionInterpreterTest extends \PHPUnit\Framework\TestCase {

	private $descriptionInterpreterFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->descriptionInterpreterFactory = new DescriptionInterpreterFactory();
	}

	public function testCanConstruct() {
		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\DescriptionInterpreters\ConjunctionInterpreter',
			new ConjunctionInterpreter( $conditionBuilder )
		);
	}

	public function testCanBuildConditionFor() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Conjunction' )
			->disableOriginalConstructor()
			->getMock();

		$conditionBuilder = $this->getMockBuilder( '\SMW\SPARQLStore\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConjunctionInterpreter( $conditionBuilder );

		$this->assertTrue(
			$instance->canInterpretDescription( $description )
		);
	}

	/**
	 * @dataProvider descriptionProvider
	 */
	public function testConjunctionCondition( $description, $orderByProperty, $sortkeys, $expectedConditionType, $expectedConditionString ) {
		$resultVariable = 'result';

		$conditionBuilder = new ConditionBuilder( $this->descriptionInterpreterFactory );
		$conditionBuilder->setResultVariable( $resultVariable );
		$conditionBuilder->setSortKeys( $sortkeys );
		$conditionBuilder->setJoinVariable( $resultVariable );
		$conditionBuilder->setOrderByProperty( $orderByProperty );

		$instance = new ConjunctionInterpreter( $conditionBuilder );

		$condition = $instance->interpretDescription( $description );

		$this->assertInstanceOf(
			$expectedConditionType,
			$condition
		);

		$this->assertEquals(
			$expectedConditionString,
			$conditionBuilder->convertConditionToString( $condition )
		);
	}

	public function descriptionProvider() {
		$stringBuilder = UtilityFactory::getInstance()->newStringBuilder();

		# 0
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = new Conjunction();

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 1
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FalseCondition';

		$description = new Conjunction( [
			new ValueDescription( new DIWikiPage( 'Bar', NS_MAIN ) )
		] );

		$description = new Conjunction( [
			new ValueDescription( new DIWikiPage( 'Foo', NS_MAIN ) ),
			$description
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '<http://www.example.org> <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2002/07/owl#nothing> .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 2
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\TrueCondition';

		$description = new Conjunction( [ new ThingDescription() ] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 3
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ThingDescription()
		);

		$description = new Conjunction( [
			$description,
			new NamespaceDescription( NS_HELP )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 4
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new Conjunction( [
			new NamespaceDescription( NS_MAIN ),
			new ValueDescription( new DIWikiPage( 'SomePageValue', NS_MAIN ) )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '{ wiki:SomePageValue swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 5
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyBlobValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			$description
		);

		$description = new Conjunction( [
			$description,
			new ValueDescription( new DIBlob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LESS ),
			new ValueDescription( new DIBlob( 'YetAnotherPropertyBlobValue' ), null, SMW_CMP_GRTR ),
			new NamespaceDescription( NS_MAIN )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '{ ?result swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( 'FILTER( ?result < "SomeOtherPropertyBlobValue" && ?result > "YetAnotherPropertyBlobValue" )' )
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 6
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition';

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyBlobValue' ),
			new DIProperty( 'Foo' ),
			SMW_CMP_LESS
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			$description
		);

		$description = new Conjunction( [
			$description,
			new ValueDescription( new DIBlob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LIKE ),
			new ValueDescription( new DIWikiPage( 'SomePropertyPageValue', NS_MAIN ) ),
			new NamespaceDescription( NS_MAIN )
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( 'wiki:SomePropertyPageValue property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 < "SomePropertyBlobValue" )' )->addNewLine()
			->addString( '{ wiki:SomePropertyPageValue swivt:wikiNamespace "0"^^xsd:integer . }' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomeOtherPropertyBlobValue$", "s") )' )
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 7
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\FilterCondition';

		$description = new Conjunction( [
			new ValueDescription( new DIBlob( 'SomeOtherPropertyBlobValue' ), null, SMW_CMP_LIKE ),
			new ValueDescription( new DIBlob( 'YetAnotherPropertyBlobValue' ), new DIProperty( 'Foo' ), SMW_CMP_NLKE ),
			new ThingDescription()
		] );

		$orderByProperty = null;
		$sortkeys = [];

		$expected = $stringBuilder
			->addString( '?result swivt:page ?url .' )->addNewLine()
			->addString( 'FILTER( regex( ?result, "^SomeOtherPropertyBlobValue$", "s") && ' )
			->addString( '!regex( ?result, "^YetAnotherPropertyBlobValue$", "s") )' )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		# 8
		$conditionType = '\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition';

		$propertyValue = new DIWikiPage( 'SomePropertyPageValue', NS_HELP );

		$propertyValueName = TurtleSerializer::getTurtleNameForExpElement(
			SMWExporter::getInstance()->getResourceElementForWikiPage( $propertyValue )
		);

		$description = new SomeProperty(
			new DIProperty( 'Foo' ),
			new ValueDescription( $propertyValue )
		);

		$category = new DIWikiPage( 'Bar', NS_CATEGORY );

		$categoryName = TurtleSerializer::getTurtleNameForExpElement(
			SMWExporter::getInstance()->getResourceElementForWikiPage( $category )
		);

		$description = new Conjunction( [
			$description,
			new ClassDescription( $category )
		] );

		$orderByProperty = new DIProperty( 'Foo' );
		$sortkeys = [ 'Foo' => 'ASC' ];

		$expected = $stringBuilder
			->addString( '?result swivt:wikiPageSortKey ?resultsk .' )->addNewLine()
			->addString( "?result property:Foo $propertyValueName ." )->addNewLine()
			->addString( '{ ?v1 swivt:wikiPageSortKey ?v1sk .' )->addNewLine()
			->addString( '}' )->addNewLine()
			->addString( "{ ?result rdf:type $categoryName . }" )->addNewLine()
			->getString();

		$provider[] = [
			$description,
			$orderByProperty,
			$sortkeys,
			$conditionType,
			$expected
		];

		return $provider;
	}

}

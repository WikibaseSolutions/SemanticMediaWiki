<?php

namespace SMW\Tests\Integration\MediaWiki\Hooks;

use SMW\DIWikiPage;
use SMW\Localizer\Localizer;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-integration
 * @group Database
 * @group mediawiki-database
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since   2.1
 *
 * @author mwjames
 */
class FileUploadIntegrationTest extends SMWIntegrationTestCase {

	private $mwHooksHandler;
	private $fixturesFileProvider;
	private $semanticDataValidator;
	private $pageEditor;

	protected function setUp(): void {
		parent::setUp();

		$utilityFactory = $this->testEnvironment->getUtilityFactory();

		$this->fixturesFileProvider = $utilityFactory->newFixturesFactory()->newFixturesFileProvider();
		$this->semanticDataValidator = $utilityFactory->newValidatorFactory()->newSemanticDataValidator();
		$this->pageEditor = $utilityFactory->newPageEditor();

		$this->mwHooksHandler = $utilityFactory->newMwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->testEnvironment->withConfiguration( [
			'smwgPageSpecialProperties' => [ '_MEDIA', '_MIME' ],
			'smwgNamespacesWithSemanticLinks' => [ NS_MAIN => true, NS_FILE => true ],
			'smwgMainCacheType' => 'hash',
		] );

		$this->testEnvironment->withConfiguration( [
			'wgEnableUploads' => true,
			'wgFileExtensions' => [ 'txt' ],
			'wgVerifyMimeType' => true
		] );

		$this->mwHooksHandler->register(
			'FileUpload',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'FileUpload' )
		);

		$this->mwHooksHandler->register(
			'InternalParseBeforeLinks',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'InternalParseBeforeLinks' )
		);

		$this->mwHooksHandler->register(
			'LinksUpdateComplete',
			$this->mwHooksHandler->getHookRegistry()->getHandlerFor( 'LinksUpdateComplete' )
		);

		$this->getStore()->setup( false );
	}

	protected function tearDown(): void {
		$this->mwHooksHandler->restoreListedHooks();
		$this->testEnvironment->tearDown();

		parent::tearDown();
	}

	public function testFileUploadForDummyTextFile() {
		Localizer::getInstance()->clear();

		$subject = new DIWikiPage( 'Foo.txt', NS_FILE );
		$fileNS = Localizer::getInstance()->getNsText( NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Foo.txt' );

		$this->assertTrue(
			$dummyTextFile->doUpload( '[[HasFile::File:Foo.txt]]' )
		);

		$this->testEnvironment->executePendingDeferredUpdates();

		$expected = [
			'propertyCount'  => 4,
			'propertyKeys'   => [ 'HasFile', '_MEDIA', '_MIME', '_SKEY' ],
			'propertyValues' => [ "$fileNS:Foo.txt", 'TEXT', 'text/plain', 'Foo.txt' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

	/**
	 * @depends testFileUploadForDummyTextFile
	 */
	public function testReUploadDummyTextFileToEditFilePage() {
		$subject = new DIWikiPage( 'Foo.txt', NS_FILE );

		$dummyTextFile = $this->fixturesFileProvider->newUploadForDummyTextFile( 'Foo.txt' );
		$dummyTextFile->doUpload();

		$this->testEnvironment->executePendingDeferredUpdates();

		$this->pageEditor
			->editPage( $subject->getTitle() )
			->doEdit( '[[Ichi::Maru|Kyū]]' );

		// File page content is kept from the initial upload
		$expected = [
			'propertyCount'  => 4,
			'propertyKeys'   => [ '_MEDIA', '_MIME', '_SKEY', 'Ichi' ],
			'propertyValues' => [ 'TEXT', 'text/plain', 'Foo.txt', 'Maru' ]
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$this->getStore()->getSemanticData( $subject )
		);
	}

}

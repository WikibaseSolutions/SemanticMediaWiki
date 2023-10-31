<?php

namespace SMW\MediaWiki\Content;

use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use JsonContentHandler;
use MediaWiki\Content\ValidationParams;
use SMW\Exception\JSONParseException;
use SMW\Localizer\Message;
use SMW\ParserData;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\Schema;
use SMW\Schema\SchemaFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Title;
use ParserOutput;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaContentHandler extends JsonContentHandler {

    private SchemaFactory $schemaFactory;
    private ?SchemaContentFormatter $contentFormatter;

    public function __construct() {
		parent::__construct( CONTENT_MODEL_SMW_SCHEMA );
	}

	/**
	 * Returns true, because wikitext supports caching using the
	 * ParserCache mechanism.
	 *
	 * @since 1.21
	 *
	 * @return bool Always true.
	 *
	 * @see ContentHandler::isParserCacheSupported
	 */
	public function isParserCacheSupported() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	protected function getContentClass() {
		return SchemaContent::class;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsSections() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsCategories() {
		return false;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function supportsRedirects() {
		return false;
	}

	/**
	 *
	 * {@inheritDoc}
	 */
	public function preSaveTransform( Content $content, PreSaveTransformParams $pstParams ): Content {
        // FIXME: WikiPage::doEditContent invokes PST before validation. As such, native data
        //  may be invalid (though PST result is discarded later in that case).
        if ( !$content->isValid() ) {
            return $content;
        }

        /** @var SchemaContent $content */

        if ( !$content->isYaml() ) {
            $text = SchemaContent::normalizeLineEndings(
                json_encode(
                    $content->getData(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                )
            );
        } else {
            $text = SchemaContent::normalizeLineEndings( $content->getText() );
        }

        return new ($this->getContentClass())( $text );
	}

    /**
     * @since 4.2
     *
     * {@inheritDoc}
     */
    public function validateSave( Content $content, ValidationParams $validationParams ) {

        $this->initServices();
        $title = $page->getTitle();

        $this->setTitlePrefix( $title );

        $errors = [];
        $schema = null;

        try {
            $schema = $this->schemaFactory->newSchema(
                $title->getDBKey(),
                $this->toJson()
            );
        } catch ( SchemaTypeNotFoundException $e ) {
            if ( !$this->isValid && $this->errorMsg !== '' ) {
                $errors[] = [ 'smw-schema-error-json', $this->errorMsg ];
            } elseif ( $e->getType() === '' || $e->getType() === null ) {
                $errors[] = [ 'smw-schema-error-type-missing' ];
            } else {
                $errors[] = [ 'smw-schema-error-type-unknown', $e->getType() ];
            }
        }

        if ( $schema !== null ) {
            $errors = $this->schemaFactory->newSchemaValidator()->validate(
                $schema
            );

            $schema_link = pathinfo(
                $schema->info( Schema::SCHEMA_VALIDATION_FILE ),
                PATHINFO_FILENAME
            );
        }

        $status = Status::newGood();

        if ( $errors !== [] && $schema === null ) {
            array_unshift( $errors, [ 'smw-schema-error-input' ] );
        } elseif ( $errors !== [] ) {
            array_unshift( $errors, [ 'smw-schema-error-input-schema', $schema_link ] );
        }

        foreach ( $errors as $error ) {

            if ( isset( $error['property'] ) && $error['property'] === 'title_prefix' ) {

                if ( isset( $error['enum'] ) ) {
                    $group = end( $error['enum'] );
                } elseif ( isset( $error['const'] ) ) {
                    $group = $error['const'];
                } else {
                    continue;
                }

                $error = [ 'smw-schema-error-title-prefix', "$group:" ];
            }

            if ( isset( $error['message'] ) ) {
                $status->fatal( 'smw-schema-error-violation', $error['property'], $error['message'] );
            } elseif ( is_string( $error ) ) {
                $status->fatal( $error );
            } else {
                $status->fatal( ...$error );
            }
        }

        return $status;
    }

    /**
     * @since 4.2
     *
     * @param SchemaFactory $schemaFactory
     * @param SchemaContentFormatter|null $contentFormatter
     */
    public function setServices( SchemaFactory $schemaFactory, SchemaContentFormatter $contentFormatter = null ) {
        $this->schemaFactory = $schemaFactory;
        $this->contentFormatter = $contentFormatter;
    }

	/**
	 *
	 * {@inheritDoc}
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	) {
        /** @var SchemaContent $content */

		$title = Title::castFromPageReference( $cpoParams->getPage() );

        if ( !$cpoParams->getGenerateHtml() || !$content->isValid() ) {
            return;
        }

        $this->initServices();

        $parserOutput->addModuleStyles(
            $this->contentFormatter->getModuleStyles()
        );

        $parserOutput->addModules(
            $this->contentFormatter->getModules()
        );

        $parserData = new ParserData( $title, $parserOutput );
        $schema = null;

        $this->contentFormatter->isYaml(
            $content->isYaml()
        );

        $content->setTitlePrefix( $title );

        try {
            $schema = $this->schemaFactory->newSchema(
                $title->getDBKey(),
                $content->toJson()
            );
        } catch ( SchemaTypeNotFoundException $e ) {

            $this->contentFormatter->setUnknownType(
                $e->getType()
            );

            $parserOutput->setText(
                $this->contentFormatter->getText( $content->getText() )
            );

            $parserData->addError(
                [ [ 'smw-schema-error-type-unknown', $e->getType() ] ]
            );

            $parserData->copyToParserOutput();
        }

        if ( $schema === null ) {
            return;
        }

        $parserOutput->setIndicator(
            'mw-helplink',
            $this->contentFormatter->getHelpLink( $schema )
        );

        $errors = $this->schemaFactory->newSchemaValidator()->validate(
            $schema
        );

        foreach ( $errors as $error ) {
            if ( isset( $error['property'] ) && isset( $error['message'] ) ) {

                if ( $error['property'] === 'title_prefix' ) {
                    if ( isset( $error['enum'] ) ) {
                        $group = end( $error['enum'] );
                    } elseif ( isset( $error['const'] ) ) {
                        $group = $error['const'];
                    } else {
                        continue;
                    }

                    $error['message'] = Message::get( [ 'smw-schema-error-title-prefix', $group ] );
                }

                $parserData->addError(
                    [ [ 'smw-schema-error-violation', $error['property'], $error['message'] ] ]
                );
            } else {
                $parserData->addError( (array)$error );
            }
        }

        $this->contentFormatter->setType(
            $this->schemaFactory->getType( $schema->get( 'type' ) )
        );

        $parserOutput->setText(
            $this->contentFormatter->getText( $content->getText(), $schema, $errors )
        );

        $parserData->copyToParserOutput();
	}

    private function initServices() {

        if ( $this->schemaFactory === null ) {
            $this->schemaFactory = new SchemaFactory();
        }

        if ( $this->contentFormatter === null ) {
            $this->contentFormatter = new SchemaContentFormatter(
                ApplicationFactory::getInstance()->getStore()
            );
        }
    }
}

<?php

namespace SMW;

use Html;
use Title;

/**
 * Query class that provides content for the Special:WantedProperties page
 *
 * @ingroup QueryPage
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class WantedPropertiesQueryPage extends QueryPage {

	/** @var Store */
	protected $store;

	/** @var Settings */
	protected $settings;

	/**
	 * @var ListLookup
	 */
	private $listLookup;

	private Title $title;

	/**
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param Settings $settings
	 */
	public function __construct( Store $store, Settings $settings ) {
		$this->store = $store;
		$this->settings = $settings;
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	public function setTitle( $title ) {
		$this->title = $title;
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getName() {
		return "WantedProperties";
	}

	/**
	 * @codeCoverageIgnore
	 * @return bool
	 */
	function isExpensive() {
		return false; /// disables caching for now
	}

	/**
	 * @codeCoverageIgnore
	 * @return bool
	 */
	function isSyndicated() {
		return false; ///TODO: why not?
	}

	/**
	 * @codeCoverageIgnore
	 * Returns available cache information (takes into account user preferences)
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getCacheInfo() {
		if ( $this->listLookup->isFromCache() ) {
			return $this->msg( 'smw-sp-properties-cache-info', $this->getLanguage()->userTimeAndDate( $this->listLookup->getTimestamp(), $this->getUser() ) )->parse();
		}

		return '';
	}

	/**
	 * @codeCoverageIgnore
	 * @return string
	 */
	function getPageHeader() {
		$filer = $this->getRequest()->getVal( 'filter', '' );

		if ( $filer !== 'unapprove' ) {
			$label = $this->msg( 'smw-special-wantedproperties-filter-unapproved' )->text();
			$title = $this->msg( 'smw-special-wantedproperties-filter-unapproved-desc' )->text();
		} else {
			$label = $this->msg( 'smw-special-wantedproperties-filter-none' )->text();
			$title = '';
		}

		$filter = Html::rawElement(
			'div',
			[
				'class' => 'smw-special-filter'
			],
			$this->msg( 'smw-special-wantedproperties-filter-label' )->text() .
			'&nbsp;' .
			Html::rawElement(
				'span',
				[
					'class' => 'smw-special-filter-button',
					'title' => $title
				],
				Html::element(
					'a',
					[
						'href'  => $this->title->getLocalURL( [ 'filter' => $filer !== '' ? '' : 'unapprove' ] ),
						'rel'   => 'nofollow'
					],
					$label
				)
			)
		);

		return Html::rawElement(
			'p',
			[ 'class' => 'smw-wantedproperties-docu plainlinks' ],
			$this->msg( 'smw-special-wantedproperties-docu' )->parse()
		) . $this->getSearchForm( $this->getRequest()->getVal( 'property', '' ), $this->getCacheInfo(), false, $filter ) .
		Html::element(
			'h2',
			[],
			$this->msg( 'smw-sp-properties-header-label' )->text()
		);
	}

	/**
	 * @param $skin
	 * @param array $result First item is DIProperty, second item is int
	 *
	 * @return string
	 */
	function formatResult( $skin, $result ) {
		// Only display user-defined properties because it can happen that
		// custom predefined (fixed) properties are mixed within the result
		// (did not use their own fixedProperty table and therefore were
		// selected as well e.g _SF_PDF etc.)
		if ( !$result[0] instanceof DIProperty || !$result[0]->isUserDefined() ) {
			return '';
		}

		$title = $result[0]->getDiWikiPage()->getTitle();

		if ( !$title instanceof \Title ) {
			return '';
		}

		$proplink = $this->getLinker()->link(
			$title,
			htmlspecialchars( $result[0]->getLabel() ),
			[],
			[ 'action' => 'view' ]
		);

		return $this->msg( 'smw-special-wantedproperties-template' )
			->rawParams( $proplink )
			->params( $result[1] )
			->escaped();
	}

	/**
	 * Get the list of results.
	 *
	 * @param RequestOptions $requestOptions
	 * @return array of DIProperty|SMWDIError
	 */
	function getResults( $requestOptions ) {
		$this->listLookup = $this->store->getWantedPropertiesSpecial( $requestOptions );
		return $this->listLookup->fetchList();
	}
}

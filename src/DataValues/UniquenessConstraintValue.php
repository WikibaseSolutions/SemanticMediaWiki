<?php

namespace SMW\DataValues;

/**
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValue extends BooleanValue {

	/**
	 * @since 2.4
	 *
	 * @param string $typeid
	 */
	public function __construct( $typeid = '' ) {
		parent::__construct( '__pvuc' );
	}

	/**
	 * @see DataValue::parseUserValue
	 *
	 * @param string $userValue
	 */
	protected function parseUserValue( $userValue ) {
		if ( !$this->isEnabledFeature( SMW_DV_PVUC ) ) {
			$this->addErrorMsg(
				[
					'smw-datavalue-feature-not-supported',
					'SMW_DV_PVUC'
				]
			);
		}

		parent::parseUserValue( $userValue );
	}

}

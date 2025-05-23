<?php

namespace SMW\MediaWiki\Connection;

use Exception;
use RuntimeException;
use SMW\Connection\ConnRef;
use UnexpectedValueException;
use Wikimedia\Rdbms\Database as MWDatabase;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;
use Wikimedia\Rdbms\Platform\SQLPlatform;
use Wikimedia\Rdbms\ResultWrapper;
use Wikimedia\ScopedCallback;

/**
 * This adapter class covers MW DB specific operations. Changes to the
 * interface are likely therefore this class should not be used other than by
 * SMW itself.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class Database {

	/**
	 * Identifies a request to be executed using an auto commit state
	 *
	 * @note (#1605 "... creating temporary tables in a transaction is not
	 * replication-safe and causes errors in MySQL 5.6. ...")
	 */
	const AUTO_COMMIT = 'auto.commit';

	/**
	 * @see IDatabase::TRIGGER_ROLLBACK
	 */
	const TRIGGER_ROLLBACK = IDatabase::TRIGGER_ROLLBACK;

	/** @var ISQLPlatform::LIST_COMMA (Combine list with comma delimeters) */
	const LIST_COMMA = ISQLPlatform::LIST_COMMA;

	/**
	 * @var ConnRef
	 */
	private $connRef;

	/**
	 * @var TransactionHandler
	 */
	private $transactionHandler;

	/**
	 * @var int
	 */
	private $flags = 0;

	/**
	 * @var int
	 */
	private $insertId = null;

	/**
	 * @var string
	 */
	private $type = '';

	/**
	 * @since 1.9
	 *
	 * @param ConnRef $connRef
	 * @param TransactionHandler $transactionHandler
	 */
	public function __construct( ConnRef $connRef, TransactionHandler $transactionHandler ) {
		$this->connRef = $connRef;
		$this->transactionHandler = $transactionHandler;
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function releaseConnection() {
		$this->connRef->releaseConnections();
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function ping() {
		return true;
	}

	/**
	 * @since 3.0
	 *
	 * @return Query
	 */
	public function newQuery() {
		return new Query( $this );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function isType( $type ) {
		if ( $this->type === '' ) {
			$this->type = $this->connRef->getConnection( 'read' )->getType();
		}

		return $this->type === $type;
	}

	/**
	 * @see IDatabase::getServerInfo
	 *
	 * @since 3.0
	 *
	 * @return array
	 */
	public function getInfo() {
		return [
			$this->getType() => $this->connRef->getConnection( 'read' )->getServerInfo()
		];
	}

	/**
	 * @see IDatabase::getType
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getType() {
		if ( $this->type === '' ) {
			$this->type = $this->connRef->getConnection( 'read' )->getType();
		}

		return $this->type;
	}

	/**
	 * @see IDatabase::tableName
	 *
	 * @since 1.9
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	public function tableName( $tableName ) {
		return $this->connRef->getConnection( 'read' )->tableName( $tableName );
	}

	/**
	 * @see IDatabase::timestamp
	 *
	 * @since 3.0
	 *
	 * @param int $ts
	 *
	 * @return string
	 */
	public function timestamp( $ts = 0 ) {
		return $this->connRef->getConnection( 'read' )->timestamp( $ts );
	}

	/**
	 * @see IDatabase::tablePrefix
	 *
	 * @since 3.0
	 *
	 * @param string|null $prefix
	 *
	 * @return string
	 */
	public function tablePrefix( $prefix = null ) {
		$connection = $this->connRef->getConnection( 'read' );

		// https://github.com/wikimedia/mediawiki/commit/6ab57b9c2424d9cc01b29908658b273a6ce75489
		// Avoid "DBUnexpectedError ... DBConnRef.php: Database selection is
		// disallowed to enable reuse ..."
		if ( $connection instanceof \Wikimedia\Rdbms\DBConnRef ) {
			return $connection->__call( __FUNCTION__, [ $prefix ] );
		}

		return $connection->tablePrefix( $prefix );
	}

	/**
	 * @see IDatabase::addQuotes
	 *
	 * @since 1.9
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function addQuotes( $value ) {
		return $this->connRef->getConnection( 'read' )->addQuotes( $value );
	}

	/**
	 * @see IDatabase::select
	 *
	 * @since 1.9
	 *
	 * @param string $tableName
	 * @param $fields
	 * @param array|string $conditions
	 * @param string $fname
	 * @param array $options
	 * @param array $joinConditions
	 *
	 * @return ResultWrapper
	 * @throws UnexpectedValueException
	 */
	public function select( $tableName, $fields, $conditions, $fname, array $options = [], $joinConditions = [] ) {
		$tablePrefix = null;
		$connection = $this->connRef->getConnection( 'read' );

		if ( $this->isType( 'sqlite' ) ) {

			// MW's SQLite implementation adds an auto prefix to the tableName but
			// not to the conditions and since ::tableName will handle prefixing
			// consistently ensure that the select doesn't add an extra prefix
			$tablePrefix = $this->tablePrefix( '' );

			if ( isset( $options['ORDER BY'] ) ) {
				$options['ORDER BY'] = str_replace( 'RAND', 'RANDOM', $options['ORDER BY'] );
			}
		}

		try {
			$results = $connection->select(
				$tableName,
				$fields,
				$conditions,
				$fname,
				$options,
				$joinConditions
			);
		} catch ( DBError $e ) {
			throw new RuntimeException( $e->getMessage() . "\n" . $e->getTraceAsString() );
		}

		if ( $tablePrefix !== null ) {
			$this->tablePrefix( $tablePrefix );
		}

		if ( $results instanceof ResultWrapper ) {
			return $results;
		}

		throw new UnexpectedValueException(
			'Expected a ResultWrapper for ' . "\n" .
			$tableName . "\n" .
			$fields . "\n" .
			$conditions
		);
	}

	/**
	 * Execute a given SQL query on the primary DB.
	 *
	 * @see IDatabase::query
	 *
	 * @since 1.9
	 *
	 * @param Query|string $sql
	 * @param string $fname
	 * @param int $flags
	 *
	 * @return ResultWrapper
	 * @throws RuntimeException
	 */
	public function query( $sql, $fname = __METHOD__, $flags = 0 ) {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		$results = $this->executeQuery(
			$this->connRef->getConnection( 'write' ),
			$sql,
			$fname,
			$flags
		);

		ScopedCallback::consume( $scope );

		return $results;
	}

	/**
	 * Execute a given SQL query on a read-only replica DB.
	 *
	 * @see IDatabase::query()
	 * @since 4.0.0
	 *
	 * @param Query|string $sql
	 * @param string $fname
	 * @param int $flags
	 * @return bool|\Wikimedia\Rdbms\IResultWrapper
	 * @throws Exception
	 */
	public function readQuery( $sql, $fname = __METHOD__, $flags = 0 ) {
		return $this->executeQuery(
			$this->connRef->getConnection( 'read' ),
			$sql,
			$fname,
			$flags | ISQLPlatform::QUERY_CHANGE_NONE
		);
	}

	/**
	 * Execute a SQL query using the given DB connection handle.
	 *
	 * @see IDatabase::query()
	 *
	 * @param IDatabase $connection
	 * @param Query|string $sql
	 * @param $fname
	 * @param int $flags
	 * @return bool|\Wikimedia\Rdbms\IResultWrapper
	 * @throws Exception
	 */
	private function executeQuery( IDatabase $connection, $sql, $fname, $flags ) {
		if ( $sql instanceof Query ) {
			$sql = $sql->build();
		}

		if ( !$this->isType( 'postgres' ) ) {
			$sql = str_replace( '@INT', '', $sql );
		}

		if ( $this->isType( 'postgres' ) ) {

			// Requires postgres 9.5+
			// https://www.postgresql.org/docs/9.5/sql-insert.html
			if ( strpos( $sql, "INSERT IGNORE INTO" ) !== false ) {
				$sql = ( str_replace( 'IGNORE ', '', $sql ) ) . " ON CONFLICT DO NOTHING";
			}

			$sql = str_replace( '@INT', '::integer', $sql );
			$sql = str_replace( 'IGNORE', '', $sql );
			$sql = str_replace( 'DROP TEMPORARY TABLE', 'DROP TABLE IF EXISTS', $sql );
			$sql = str_replace( 'RAND()', ( strpos( $sql, 'DISTINCT' ) !== false ? '' : 'RANDOM()' ), $sql );
		}

		if ( $this->isType( 'sqlite' ) ) {
			$sql = str_replace( 'IGNORE', '', $sql );
			$sql = str_replace( 'TEMPORARY', 'TEMP', $sql );
			$sql = str_replace( 'ENGINE=MEMORY', '', $sql );
			$sql = str_replace( 'DROP TEMP', 'DROP', $sql );
			$sql = str_replace( 'TRUNCATE TABLE', 'DELETE FROM', $sql );
			$sql = str_replace( 'RAND', 'RANDOM', $sql );
		}

		// https://github.com/wikimedia/mediawiki/blob/42d5e6f43a00eb8bedc3532876125f74e3188343/includes/deferred/AutoCommitUpdate.php
		// https://github.com/wikimedia/mediawiki/blob/f7dad57c64db3eb1296894c2d3ae97b9f7f27c4c/includes/installer/DatabaseInstaller.php#L157
		if ( $this->flags === self::AUTO_COMMIT ) {
			$autoTrx = $connection->getFlag( DBO_TRX );
			$connection->clearFlag( DBO_TRX );

			if ( $autoTrx && $connection->trxLevel() ) {
				$connection->commit( __METHOD__ );
			}
		}

		try {
			$exception = null;
			$results = $connection->query(
				$sql,
				$fname,
				$flags
			);
		} catch ( Exception $exception ) {
		}

		if ( $this->flags === self::AUTO_COMMIT && $autoTrx ) {
			$connection->setFlag( DBO_TRX );
		}

		// State is only valid for a single transaction
		$this->flags = false;

		if ( $exception ) {
			throw $exception;
		}

		return $results;
	}

	/**
	 * @see IDatabase::selectRow
	 *
	 * @since 1.9
	 */
	public function selectRow( $table, $vars, $conds, $fname = __METHOD__, $options = [], $joinConditions = [] ) {
		return $this->connRef->getConnection( 'read' )->selectRow(
			$table,
			$vars,
			$conds,
			$fname,
			$options,
			$joinConditions
		);
	}

	/**
	 * @see IDatabase::conditional
	 *
	 * @since 5.0
	 */
	public function conditional( $cond, $caseTrueExpression, $caseFalseExpression ) {
		return $this->connRef->getConnection( 'read' )->conditional( $cond, $caseTrueExpression, $caseFalseExpression );
	}

	/**
	 * @see IDatabase::expr
	 *
	 * @since 5.0
	 */
	public function expr( string $field, string $op, $value ) {
		return $this->connRef->getConnection( 'read' )->expr( $field, $op, $value );
	}

	/**
	 * @see IDatabase::affectedRows
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	function affectedRows() {
		return $this->connRef->getConnection( 'read' )->affectedRows();
	}

	/**
	 * @note Method was made protected in 1.28, hence the need
	 * for the DatabaseHelper that copies the functionality.
	 *
	 * @see SQLPlatform::makeSelectOptions
	 *
	 * @since 1.9
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function makeSelectOptions( $options ) {
		return OptionsBuilder::makeSelectOptions( $this, $options );
	}

	/**
	 * @see removed method IDatabase::nextSequenceValue
	 *
	 * @since 1.9
	 *
	 * @param string $seqName
	 *
	 * @return int|null
	 */
	public function nextSequenceValue( $seqName ) {
		$this->insertId = null;

		if ( !$this->isType( 'postgres' ) ) {
			return null;
		}

		// #3101, #2903
		// MW 1.31+
		// https://github.com/wikimedia/mediawiki/commit/0a9c55bfd39e22828f2d152ab71789cef3b0897c#diff-278465351b7c14bbcadac82036080e9f
		$safeseq = str_replace( "'", "''", $seqName );
		$res = $this->connRef->getConnection( 'write' )->query( "SELECT nextval('$safeseq')", ISQLPlatform::QUERY_CHANGE_NONE );
		$row = $res->fetchRow();

		return $this->insertId = $row[0] === null ? null : (int)$row[0];
	}

	/**
	 * @see IDatabase::insertId
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	function insertId() {
		if ( $this->insertId !== null ) {
			return $this->insertId;
		}

		return (int)$this->connRef->getConnection( 'write' )->insertId();
	}

	/**
	 * @see MWDatabase::clearFlag
	 *
	 * @since 2.4
	 */
	function clearFlag( $flag ) {
		$this->connRef->getConnection( 'write' )->clearFlag( $flag );
	}

	/**
	 * @see MWDatabase::getFlag
	 *
	 * @since 2.4
	 */
	function getFlag( $flag ) {
		return $this->connRef->getConnection( 'write' )->getFlag( $flag );
	}

	/**
	 * @see MWDatabase::setFlag
	 *
	 * @since 2.4
	 */
	function setFlag( $flag ) {
		if ( $flag === self::AUTO_COMMIT ) {
			return $this->flags = self::AUTO_COMMIT;
		}

		$this->connRef->getConnection( 'write' )->setFlag( $flag );
	}

	/**
	 * @see IDatabase::insert
	 *
	 * @since 1.9
	 */
	public function insert( $table, $rows, $fname = __METHOD__, $options = [] ) {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		$res = $this->connRef->getConnection( 'write' )->insert( $table, $rows, $fname, $options );

		ScopedCallback::consume( $scope );

		return $res;
	}

	/**
	 * @see IDatabase::update
	 *
	 * @since 1.9
	 */
	function update( $table, $values, $conds, $fname = __METHOD__, $options = [] ) {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		$res = $this->connRef->getConnection( 'write' )->update( $table, $values, $conds, $fname, $options );

		ScopedCallback::consume( $scope );

		return $res;
	}

	/**
	 * @see IDatabase::upsert
	 *
	 * @since 3.1
	 */
	public function upsert( $table, array $rows, $uniqueIndexes, array $set, $fname = __METHOD__ ) {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		$res = $this->connRef->getConnection( 'write' )->upsert( $table, $rows, $uniqueIndexes, $set, $fname );

		ScopedCallback::consume( $scope );

		return $res;
	}

	/**
	 * @see IDatabase::delete
	 *
	 * @since 1.9
	 */
	public function delete( $table, $conds, $fname = __METHOD__ ) {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		$res = $this->connRef->getConnection( 'write' )->delete( $table, $conds, $fname );

		ScopedCallback::consume( $scope );

		return $res;
	}

	/**
	 * @see IDatabase::replace
	 *
	 * @since 2.5
	 */
	public function replace( $table, $uniqueIndexes, $rows, $fname = __METHOD__ ) {
		$scope = $this->transactionHandler->muteTransactionProfiler();

		$res = $this->connRef->getConnection( 'write' )->replace( $table, $uniqueIndexes, $rows, $fname );

		ScopedCallback::consume( $scope );

		return $res;
	}

	/**
	 * @see IDatabase::makeList
	 *
	 * @since 1.9
	 */
	public function makeList( $data, $mode = self::LIST_COMMA ) {
		return $this->connRef->getConnection( 'read' )->makeList( $data, $mode );
	}

	/**
	 * @see IDatabase::tableExists
	 *
	 * @since 1.9
	 *
	 * @param string $table
	 * @param string $fname
	 *
	 * @return bool
	 */
	public function tableExists( $table, $fname = __METHOD__ ) {
		return $this->connRef->getConnection( 'read' )->tableExists( $table, $fname );
	}

	/**
	 * @see IDatabase::listTables
	 *
	 * @since 3.1
	 *
	 * @param string|null $prefix
	 * @param string $fname
	 *
	 * @return
	 */
	public function listTables( $prefix = null, $fname = __METHOD__ ) {
		return $this->connRef->getConnection( 'read' )->listTables( $prefix, $fname );
	}

	/**
	 * @see IDatabase::selectField
	 *
	 * @since 1.9.2
	 */
	public function selectField( $table, $fieldName, $conditions = '', $fname = __METHOD__, $options = [] ) {
		return $this->connRef->getConnection( 'read' )->selectField( $table, $fieldName, $conditions, $fname, $options );
	}

	/**
	 * @see IDatabase::estimateRowCount
	 *
	 * @since 2.1
	 */
	public function estimateRowCount( $table, $vars = '*', $conditions = '', $fname = __METHOD__, $options = [] ) {
		return $this->connRef->getConnection( 'read' )->estimateRowCount( $table, $vars, $conditions, $fname, $options );
	}

	/**
	 * @note Only supported with 1.28+
	 * @since 3.0
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 *
	 * @return mixed A value to pass to commitAndWaitForReplication
	 */
	public function getEmptyTransactionTicket( $fname = __METHOD__ ) {
		return $this->transactionHandler->getEmptyTransactionTicket( $fname );
	}

	/**
	 * Convenience method for safely running commitMasterChanges/waitForReplication
	 * where it will allow to commit and wait for whena TransactionTicket is
	 * available.
	 *
	 * @note Only supported with 1.28+
	 *
	 * @since 3.0
	 *
	 * @param string $fname Caller name (e.g. __METHOD__)
	 * @param mixed $ticket Result of Database::getEmptyTransactionTicket
	 * @param array $opts Options to waitForReplication
	 */
	public function commitAndWaitForReplication( $fname, $ticket, array $opts = [] ) {
		return $this->transactionHandler->commitAndWaitForReplication( $fname, $ticket, $opts );
	}

	/**
	 * @TransactionHandler::beginSectionTransaction
	 *
	 * @since 3.1
	 *
	 * @param string $fname
	 *
	 * @throws RuntimeException
	 */
	public function beginSectionTransaction( $fname = __METHOD__ ) {
		$this->transactionHandler->markSectionTransaction(
			$fname
		);

		$this->connRef->getConnection( 'write' )->startAtomic( $fname );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $fname
	 */
	public function endSectionTransaction( $fname = __METHOD__ ) {
		$this->transactionHandler->detachSectionTransaction(
			$fname
		);

		$this->connRef->getConnection( 'write' )->endAtomic( $fname );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $fname
	 *
	 * @return bool
	 */
	public function inSectionTransaction( $fname = __METHOD__ ) {
		return $this->transactionHandler->inSectionTransaction( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 */
	public function beginAtomicTransaction( $fname = __METHOD__ ) {
		// Disable all individual atomic transactions as long as a section
		// transaction is registered.
		if ( $this->transactionHandler->hasActiveSectionTransaction() ) {
			return;
		}

		$this->connRef->getConnection( 'write' )->startAtomic( $fname );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $fname
	 *
	 * @return void
	 */
	public function endAtomicTransaction( $fname = __METHOD__ ) {
		// Disable all individual atomic transactions as long as a section
		// transaction is registered.
		if ( $this->transactionHandler->hasActiveSectionTransaction() ) {
			return;
		}

		$this->connRef->getConnection( 'write' )->endAtomic( $fname );
	}

	/**
	 * @since 3.0
	 *
	 * @param callable $callback
	 */
	public function onTransactionResolution( callable $callback, $fname = __METHOD__ ) {
		$connection = $this->connRef->getConnection( 'write' );

		if ( $connection->trxLevel() ) {
			$connection->onTransactionResolution( $callback, $fname );
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param callable $callback
	 */
	public function onTransactionCommitOrIdle( callable $callback ) {
		$connection = $this->connRef->getConnection( 'write' );
		$connection->onTransactionCommitOrIdle( $callback );
	}

	/**
	 * @since 3.1
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function escape_bytea( $text ) {
		if ( $this->isType( 'postgres' ) ) {
			$text = pg_escape_bytea( $text );
		}

		return $text;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function unescape_bytea( $text ) {
		if ( $this->isType( 'postgres' ) ) {
			$text = pg_unescape_bytea( $text );
		}

		return $text;
	}
}

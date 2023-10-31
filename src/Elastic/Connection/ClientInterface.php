<?php

namespace SMW\Elastic\Connection;

/**
 * Reduced interface to the client class.
 *
 * @license GNU GPL v2+
 * @since 4.2
 *
 * @author Marijn van Wezel
 */
interface ClientInterface {
    // Distribution name for ElasticSearch
    public const ELASTICSEARCH_DIST = "elasticsearch";

    // Distribution name for OpenSearch
    public const OPENSEARCH_DIST = "opensearch";

    /**
     * Get the version of the distribution.
     *
     * @return string
     *
     * @since 4.2
     */
    public function getVersion(): string;

    /**
     * Get the distribution:
     *
     * - "elasticsearch" for ElasticSearch
     * - "opensearch" for OpenSearch
     *
     * @return string
     *
     * @since 4.2
     */
    public function getDistribution(): string;

    /**
     * Get the software info for use in Special:Version.
     *
     * @return array{'component': string, 'version': string}
     *
     * @since 4.2
     */
    public function getSoftwareInfo(): array;

    /**
     * Get the name of the index for the given type.
     *
     * @param string $type
     * @return string
     *
     * @since 4.2
     */
    public function getIndexName( string $type ): string;

    /**
     * Get the index definition for the given type as a JSON string.
     *
     * @param string $type
     * @return string
     *
     * @since 4.2
     */
    public function getIndexDefinition( string $type ): string;

    /**
     * Check whether an index exists for the given type.
     *
     * @param string $type
     * @return bool
     *
     * @since 4.2
     */
    public function hasIndex( string $type ): bool;

    /**
     * Create an index for the given type.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
     * @link https://opensearch.org/docs/latest/api-reference/index-apis/create-index/
     *
     * @param string $type
     * @return void
     *
     * @since 4.2
     */
    public function createIndex( string $type ): void;

    /**
     * Delete the index with the given name.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-delete-index.html
     * @link https://opensearch.org/docs/latest/api-reference/index-apis/delete-index/
     *
     * @param string $index The name of the index to delete
     * @return void
     *
     * @since 4.2
     */
    public function deleteIndex( string $index ): void;

    /**
     * Get the statistics for one or more indices.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-stats.html
     * @link https://opensearch.org/docs/latest/api-reference/index-apis/stats
     *
     * @param string $index A comma-separated list of index names; use "_all" or the empty string to perform the
     *                      operation on all indices
     * @return array
     *
     * @since 4.2
     */
    public function indexStats( string $index ): array;

    /**
     * Get the statistics for cluster nodes.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-stats.html
     * @link https://opensearch.org/docs/latest/api-reference/nodes-apis/nodes-stats/
     *
     * @return array
     *
     * @since 4.2
     */
    public function nodesStats(): array;

    /**
     * Change a dynamic index setting in real time.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
     * @link https://opensearch.org/docs/latest/api-reference/index-apis/update-settings
     *
     * @param array $body The index settings to be updated
     * @param string|null $index A comma-separated list of index names; use "_all", the empty string or null to perform
     *                           the operation on all indices
     *
     * @return void
     */
    public function putSettings( array $body, ?string $index = null ): void;

    /**
     * Get setting information for one or more indices.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-get-settings.html
     * @link https://opensearch.org/docs/latest/api-reference/index-apis/get-settings/
     *
     * @param string|null $index A comma-separated list of index names; use "_all", the empty string or null to get the
     *                           settings of all indices
     * @param string|null $name The name of the settings that should be included
     *
     * @return array
     */
    public function getSettings( ?string $index = null, ?string $name = null ): array;

    /**
     * Add mappings and fields to an index.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-put-mapping.html
     * @link https://opensearch.org/docs/latest/api-reference/index-apis/put-mapping/
     *
     * @param array $body The mapping definition
     * @param string|null $index A comma-separated list of index names the mapping should be added to (supports
     *                           wildcards); use "_all", the empty string or null to add the mapping on all indices
     *
     * @return void
     */
    public function putMapping( array $body, ?string $index = null ): void;

    /**
     * Retrieve mappings for one or more indices.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/master/indices-get-mapping.html
     * @link https://opensearch.org/docs/latest/field-types/index/#get-a-mapping
     *
     * @param string|null $index A comma-separated list of index names; use null to get the mappings for all indices
     *
     * @return array
     */
    public function getMapping( ?string $index = null ): array;
}
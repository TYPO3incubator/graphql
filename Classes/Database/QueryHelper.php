<?php
declare(strict_types = 1);

namespace TYPO3\CMS\GraphQL\Database;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Doctrine\DBAL\Platforms\SQLServerPlatform;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * @internal
 */
class QueryHelper
{
    /**
     * @var int
     */
    const QUERY_PART_FROM = 1;

    /**
     * @var int
     */
    const QUERY_PART_JOIN = 2;

    /**
     * @var int
     */
    const QUERY_PART_ALL = 3;

    /**
     * Returns all tables used in FROM or JOIN query parts from the query builder.
     *
     * @return string[]
     */
    public static function getQueriedTables(QueryBuilder $builder, int $part = self::QUERY_PART_ALL): array
    {
        $queriedTables = [];

        if ($part & self::QUERY_PART_FROM) {
            foreach ($builder->getQueryPart('from') as $from) {
                $tableName = self::unquoteSingleIdentifier($builder, $from['table']);
                $tableAlias = isset($from['alias'])
                    ? self::unquoteSingleIdentifier($builder, $from['alias']) : $tableName;
                $queriedTables[$tableAlias] = $tableName;
            }
        }

        if ($part & self::QUERY_PART_JOIN) {
            foreach ($builder->getQueryPart('join') as $joins) {
                foreach ($joins as $join) {
                    $tableName = self::unquoteSingleIdentifier($builder, $join['joinTable']);
                    $tableAlias = isset($join['joinAlias'])
                        ? self::unquoteSingleIdentifier($builder, $join['joinAlias']) : $tableName;
                    $queriedTables[$tableAlias] = $tableName;
                }
            }
        }

        return $queriedTables;
    }

    /**
     * Unquotes a single identifier.
     *
     * @param string $identifier Identifier
     * @return string Unquoted identifier
     */
    public static function unquoteSingleIdentifier(QueryBuilder $builder, string $identifier): string
    {
        $identifier = trim($identifier);
        $platform = $builder->getConnection()->getDatabasePlatform();

        if ($platform instanceof SQLServerPlatform) {
            $identifier = ltrim($identifier, '[');
            $identifier = rtrim($identifier, ']');
        } else {
            $quoteChar = $platform->getIdentifierQuoteCharacter();
            $identifier = trim($identifier, $quoteChar);
            $identifier = str_replace($quoteChar . $quoteChar, $quoteChar, $identifier);
        }
 
        return $identifier;
    }

    public static function filterLanguageOverlayTables(array $tables)
    {
        $tables = array_filter($tables, function ($tableAlias) {
            return StringUtility::beginsWith($tableAlias, 'language_overlay');
        }, ARRAY_FILTER_USE_KEY);

        ksort($tables);

        return $tables;
    }
}
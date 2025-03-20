<?php

namespace Alva\ArrayEloquentDriver\Helpers;

class SqlParser
{
    // Public
    public $query = '';

    /**
     * Constructor
     */
    public function __construct($query = '') {
        $this->setQuery($query);
    }

    /**
     * Get SQL Query string
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * Set SQL Query string
     */
    public function setQuery($query) {
        $this->query = $query;
        return $this;
    }

    /**
     * Get fields of WHERE clause.
     * @return array<int, array{
     *     key: string,
     *     operation: string,
     *     value: string
     * }>
     */
    public function getWhereFields() {
        $query = $this->getQuery();
        $whereFields = [];

        if (preg_match_all('#WHERE[\s]+(.*?)(?:(?<=\w)[\s]+(AND|OR)[\s]+|(?=ORDER\s+BY|LIMIT|OFFSET|GROUP\s+BY|$))#i', $query, $matches)) {
            if (str_contains(strtolower($matches[1][0] ?? $matches[1]), ' and ')) {
                $matches[1] = explode(' and ', strtolower($matches[1][0] ?? $matches[1]));
            }

            foreach ($matches[1] as $condition) {
                if (
                    preg_match('/["\']?([\w]+)["\']?\s*(=|<>|!=|<|>|<=|>=|LIKE|IN)\s*(\(\s*\?(?:\s*,\s*\?)*\s*\)|\?|\d+)/i', $condition, $fieldMatches)
                    || preg_match('#["\']?([\w.]+)["\']?[\s]*(=|<>|!=|<|>|<=|>=|LIKE|IN)[\s]*([\w\'"]+)#i', $condition, $fieldMatches)
                ) {
                    $whereFields[] = [
                        'key' => $fieldMatches[1],
                        'operation' => $fieldMatches[2],
                        'value' => $fieldMatches[3],
                    ];
                }
            }
        }

        return $whereFields;
    }

    /**
     * Get offset and limit.
     * @return array{
     *     offset: string,
     *     limit: string
     * }
     */
    public function getLimitAndOffset() {
        $query = $this->getQuery();
        $offset = null;
        $limit = null;

        if (preg_match('#LIMIT[\s]+(\d+)(?:[\s]+OFFSET[\s]+(\d+))?#i', $query, $matches)) {
            $limit = isset($matches[1]) ? (int)$matches[1] : null;
            $offset = isset($matches[2]) ? (int)$matches[2] : null;
        } elseif (preg_match('#OFFSET[\s]+(\d+)[\s]+LIMIT[\s]+(\d+)#i', $query, $matches)) {
            $offset = isset($matches[1]) ? (int)$matches[1] : null;
            $limit = isset($matches[2]) ? (int)$matches[2] : null;
        }

        return ['offset' => $offset, 'limit' => $limit];
    }
}
<?php

namespace Alva\ArrayEloquentDriver\Database\Array;

use Illuminate\Database\Connection as ConnectionBase;
use RuntimeException;
use Alva\ArrayEloquentDriver\Helpers\SqlParser;

class Connection extends ConnectionBase
{
    public function select($query, $bindings = [], $useReadPdo = true, array $fetchUsing = [])
    {
        // Check query.
        if (!$query) {
            return [];
        }

        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $params = $this->parseQuery($query, $bindings);

            if (!isset($params['resolverClassName']) || !isset($params['resolverHandler'])) {
                throw new RuntimeException('Invalid query');
            }

            $resolverClass = app($params['resolverClassName']);
            $resolverHandler = $params['resolverHandler'];

            unset($params['resolverClassName']);
            unset($params['resolverHandler']);

            $dependencies = [];

            foreach ((new \ReflectionMethod($resolverClass, $resolverHandler))->getParameters() as $parameter) {
                $dependencies[$parameter->getName()] = $params[$parameter->getName()] ?? null;
            }

            $rows = $resolverClass->{$resolverHandler}(...$dependencies);

            if ($aggregate = $this->parseAggregate($query)) {
                $value = $this->computeAggregate($aggregate, $rows);

                foreach ($rows as &$row) {
                    $row['aggregate'] = $value;
                }

                unset($row);
            }

            return $rows;
        });
    }

    protected function parseSelectQuery(string $query): array
    {
        if (!preg_match('/\bselect\s+(?:distinct\s+)?(.*?)\s+from\b/is', $query, $select)) {
            return [];
        }

        return array_map('trim', explode(',', $select[1]));
    }

    /**
     * Detect an aggregate column such as `count(*) as aggregate`.
     *
     * Laravel 13 wraps the alias (`count(*) as "aggregate"`), so the alias and
     * the aggregated column may be quoted with any of " ' ` [].
     */
    protected function parseAggregate(string $query): ?array
    {
        foreach ($this->parseSelectQuery($query) as $column) {
            if (preg_match('/^(count|sum|avg|min|max)\s*\(\s*(distinct\s+)?(.+?)\s*\)\s+as\s+["\'`\[]?aggregate["\'`\]]?$/is', $column, $matches)) {
                return [
                    'function' => strtolower($matches[1]),
                    'distinct' => $matches[2] !== '',
                    'column' => trim($matches[3], '"\'`[]'),
                ];
            }
        }

        return null;
    }

    /**
     * Compute an aggregate value over the rows returned by the resolver.
     */
    protected function computeAggregate(array $aggregate, array $rows): int|float|null
    {
        if ($aggregate['column'] === '*') {
            $values = array_fill(0, count($rows), 1);
        } else {
            $values = array_filter(
                array_column($rows, $aggregate['column']),
                static fn ($value): bool => $value !== null
            );
        }

        if ($aggregate['distinct']) {
            $values = array_unique($values, SORT_REGULAR);
        }

        return match ($aggregate['function']) {
            'count' => count($values),
            'sum' => array_sum($values),
            'avg' => $values === [] ? null : array_sum($values) / count($values),
            'min' => $values === [] ? null : min($values),
            'max' => $values === [] ? null : max($values),
        };
    }

    protected function parseQuery(string $query, array $bindings): array
    {
        $parser = new SqlParser($query);

        $wheres = $parser->getWhereFields();
        $limitAndOffset = $parser->getLimitAndOffset();

        $final = [];
        $bindingIndex = 0;

        foreach ($wheres as $where) {
            $varName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $where['key']))));
            if (str_contains(strtolower($where['operation']), 'in')) {
                $final[$varName] = array_slice($bindings, $bindingIndex, substr_count($where['value'], '?'));
                $bindingIndex += count($final[$varName]);
            } else {
                $final[$varName] = $bindings[$bindingIndex];
                $bindingIndex++;
            }
        }

        return [
            ...$final,
            ...$limitAndOffset
        ];
    }
}

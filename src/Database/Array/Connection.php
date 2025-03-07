<?php

namespace Alva\ArrayEloquentDriver\Database\Array;

use Illuminate\Database\Connection as ConnectionBase;
use RuntimeException;

class Connection extends ConnectionBase
{
    public function select($query, $bindings = [], $useReadPdo = true)
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

            $rows = $resolverClass->{$resolverHandler}(...$params);

            $select = $this->parseSelectQuery($query);


            if (in_array('count(*) as aggregate', $select)) {
                foreach ($rows as &$row) {
                    $row['aggregate'] = count($rows);
                }
            }

            return $rows;
        });
    }

    protected function parseSelectQuery(string $query): array
    {
        preg_match('/select\s+(.*?)\s+from/is', $query, $select);
        return is_array($select[1]) ? $select[1] : explode(',', $select[1]);
    }

    protected function parseQuery(string $query, array $bindings): array
    {
        preg_match_all('/["\']?([\w]+)["\']?\s+(=|IN)\s*(\(\s*\?(?:\s*,\s*\?)*\s*\)|\?)/i', $query, $matches);

        $final = [];
        $bindingIndex = 0;

        foreach ($matches[1] as $colIndex => $column) {
            $varName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $column))));
            if (str_contains(strtolower($matches[2][$colIndex]), 'in')) {
                $final[$varName] = array_slice($bindings, $bindingIndex, substr_count($matches[3][$bindingIndex], '?'));
                $bindingIndex += count($final[$varName]);
            } else {
                $final[$varName] = $bindings[$bindingIndex];
                $bindingIndex++;
            }
        }
        return $final;
    }
}

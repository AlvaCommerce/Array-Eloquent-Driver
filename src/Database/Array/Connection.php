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

            $params = array_combine($this->parseQuery($query), $this->prepareBindings($bindings));

            if (!isset($params['resolverClassName']) || !isset($params['resolverHandler'])) {
                throw new RuntimeException('Invalid query');
            }

            $resolverClass = app($params['resolverClassName']);
            $resolverHandler = $params['resolverHandler'];

            unset($params['resolverClassName']);
            unset($params['resolverHandler']);

            $rows = $resolverClass->{$resolverHandler}(...$params);

            return $rows;
        });
    }

    protected function parseQuery(string $query): array
    {
        preg_match_all('/"([^"]+)"\s*=\s*\?/', $query, $matches);
        $queryKeys = array_map(fn($key) => lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))), $matches[1]);

        return $queryKeys;
    }
}

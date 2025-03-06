<?php

namespace Alva\ArrayEloquentDriver\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class ArrayModel extends Model
{
    protected $connection = 'array_eloquent_driver';

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('resolver', fn (Builder $builder): Builder =>  $builder
            ->where('resolver_class_name', static::getResolverClassName())
            ->where('resolver_handler', static::getResolverHandler())
        );
    }

    abstract public static function getResolverClassName(): string;

    abstract public static function getResolverHandler(): string;
}

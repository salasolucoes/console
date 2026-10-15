<?php

declare(strict_types=1);

namespace Sala\Console\Console\Tinker;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * Casters do Symfony VarDumper para modelos e coleções Eloquent, no estilo do
 * Laravel Tinker: os atributos visíveis são apresentados sem marcador, os
 * ocultos com `#`, e as relações carregadas são incluídas.
 *
 * São registados na configuração do PsySH com as chaves da classe-base
 * (`Model` e `Collection`), pelo que se aplicam automaticamente a todas as
 * subclasses — o Symfony VarDumper faz matching por `class_parents()` e
 * `class_implements()`.
 */
final class EloquentCaster
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function castModel(Model $model, array $attributes, Stub $stub, bool $isNested, int $filter = 0): array
    {
        $attributes = array_merge(
            $model->getAttributes(),
            $model->getRelations(),
        );

        $visible = array_flip(
            $model->getVisible() ?: array_diff(array_keys($attributes), $model->getHidden()),
        );

        $hidden = array_flip($model->getHidden());

        foreach ($model->getAppends() as $appended) {
            $attributes[$appended] = $model->getAttribute($appended);
        }

        $results = [];

        foreach ($attributes as $key => $value) {
            $prefix = '';

            if (isset($visible[$key])) {
                $prefix = Caster::PREFIX_VIRTUAL;
            }

            if (isset($hidden[$key])) {
                $prefix = Caster::PREFIX_PROTECTED;
            }

            $results[$prefix.$key] = $value;
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function castCollection(Collection $collection, array $attributes, Stub $stub, bool $isNested, int $filter = 0): array
    {
        return [
            Caster::PREFIX_VIRTUAL.'all' => $collection->all(),
        ];
    }
}

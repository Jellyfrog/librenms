<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Request sent by the x-data-table component in server mode.
 *
 * GET query string: page, per_page (-1 = every row), search, sort[column]=asc|desc (in priority order), filter[field][op]=value
 * Respond with a resource collection of the paginator: return RowResource::collection($request->paginate($query));
 * Endpoints add their own parameters with $request->validate([...]).
 */
class DataTableRequest extends FormRequest
{
    public const int DEFAULT_PER_PAGE = 50;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:-1', 'not_in:0'],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'array'],
            'sort.*' => ['in:asc,desc'],
            'filter' => ['nullable', 'array'],
        ];
    }

    public function search(): ?string
    {
        $search = trim((string) $this->validated('search'));

        return $search === '' ? null : $search;
    }

    /**
     * x-filter filters, pass them to a Filterable model: $query->applyFilters($request->filters())
     *
     * @return array<string, array<string, mixed>>
     */
    public function filters(): array
    {
        return $this->array('filter');
    }

    /**
     * Requested sort in priority order, $default when none was requested
     *
     * @param  array<string, 'asc'|'desc'>  $default
     * @return array<string, 'asc'|'desc'>
     */
    public function sorts(array $default = []): array
    {
        return $this->validated('sort') ?: $default;
    }

    /**
     * Order the query by the requested sort, unknown sort keys are ignored.
     *
     * @param  Builder<*>|Relation<*, *, *>  $query
     * @param  array<int|string, string|Expression|Closure(Builder<*>|Relation<*, *, *>, 'asc'|'desc'): void>  $columns  sortable keys: a column name,
     *                                                                                                   or key => column, expression or callback
     * @param  array<string, 'asc'|'desc'>  $default  used when no sort was requested
     * @return Builder<*>|Relation<*, *, *>
     */
    public function sortQuery(Builder|Relation $query, array $columns, array $default = []): Builder|Relation
    {
        foreach ($this->sorts($default) as $key => $direction) {
            $column = $columns[$key] ?? (in_array($key, $columns, true) ? $key : null);

            if ($column instanceof Closure) {
                $column($query, $direction);
            } elseif ($column !== null) {
                $query->orderBy($column, $direction);
            }
        }

        return $query;
    }

    /**
     * Paginate the query with the requested page and page size.
     * per_page -1 returns every row on one page, a page past the end (rows were removed) returns the last page.
     *
     * @param  Builder<*>|Relation<*, *, *>  $query
     */
    public function paginate(Builder|Relation $query, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $total = $query->toBase()->getCountForPagination();
        $perPage = (int) ($this->validated('per_page') ?? $perPage);
        if ($perPage < 0) {
            $perPage = max($total, 1);
        }
        $lastPage = max((int) ceil($total / $perPage), 1);
        $page = min((int) ($this->validated('page') ?? 1), $lastPage);

        return $query->paginate($perPage, ['*'], 'page', $page, $total);
    }
}

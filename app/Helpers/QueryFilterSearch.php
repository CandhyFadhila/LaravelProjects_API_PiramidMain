<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueryFilterSearch
{
	public static function applyFilters(Builder $query, array $filters, array $rules): Builder
	{
		foreach ($filters as $key => $value) {
			if (isset($rules[$key]) && is_callable($rules[$key])) {
				$query = $rules[$key]($query, $value) ?: $query;
			}
		}

		return $query;
	}

	public static function applySearch($query, $search, array $columns)
	{
		return $query->where(function ($q) use ($search, $columns) {
			foreach ($columns as $column) {
				if (str_contains($column, '.')) {
					[$relation, $relColumn] = explode('.', $column);
					$q->orWhereHas($relation, function ($relQ) use ($relColumn, $search) {
						$relQ->where($relColumn, 'LIKE', "%{$search}%");
					});
				} else {
					$q->orWhere($column, 'LIKE', "%{$search}%");
				}
			}
		});
	}

	public static function applyPagination(Builder $query, Request $request)
	{
		$limit = $request->input('limit', 10);
		return $query->paginate($limit);
	}

	public static function formatPaginationCollection($result, $resourceClass)
	{
		$response = $resourceClass::collection($result)->response()->getData(true);

		return [
			'data' => $response['data'],
			'pagination' => [
				'links' => [
					'first' => $result->url(1),
					'last'  => $result->url($result->lastPage()),
					'prev'  => $result->previousPageUrl(),
					'next'  => $result->nextPageUrl(),
				],
				'meta' => [
					'current_page' => $result->currentPage(),
					'last_page'    => $result->lastPage(),
					'per_page'     => $result->perPage(),
					'total'        => $result->total(),
				]
			]
		];
	}
}

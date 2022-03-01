<?php

declare(strict_types=1);

namespace Pagination
{
    /**
     * Returns pagination data based on the total number of items
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Pagination\paginate(100);
     * Pagination\paginate(100, 2);
     * Pagination\paginate(100, Request\input('page'), 25);
     * ```
     */
    function paginate(int $total, int $page = 1, int $limit = 10): array
    {
        global $mikro;

        return $mikro[DATA] = [
            'page' => $page = $page < 1 ? 1 : $page,
            'max' => $max = \ceil($total / $limit) * $limit,
            'limit' => $limit,
            'offset' => ($offset = ($page - 1) * $limit) > $max ? $max : $offset,
            'total_page' => $totalPage = \intval($max / $limit),
            'current_page' => $currentPage = $page > $totalPage ? $totalPage : $page,
            'next_page' => $currentPage + 1 > $totalPage ? $totalPage : $currentPage + 1,
            'previous_page' => $currentPage - 1 ?: 1,
        ];
    }

    /**
     * Returns last paginated data
     *
     * {@inheritDoc} **Example:**
     * ```php
     * Pagination\paginate(100); // array
     * Pagination\data(); // array
     * ```
     */
    function data(): ?array
    {
        global $mikro;

        return $mikro[DATA] ?? null;
    }

    /**
     * Pagination data constant
     *
     * @internal
     */
    const DATA = 'Pagination\DATA';
};

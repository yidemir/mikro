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

        $page = $page < 1 ? 1 : $page;
        $max = \ceil($total / $limit) * $limit;
        $offset = ($page - 1) * $limit;
        $offset = $offset > $max ? $max : $offset;
        $total_page = \intval($max / $limit);
        $current_page = $page > $total_page ? $total_page : $page;
        $next_page = $current_page + 1 > $total_page ? $total_page : $current_page + 1;
        $previous_page = $current_page - 1 ?: 1;

        return $mikro[DATA] = compact('offset', 'limit', 'current_page', 'next_page', 'previous_page', 'total_page');
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

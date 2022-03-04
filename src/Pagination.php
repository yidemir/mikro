<?php

declare(strict_types=1);

namespace Pagination
{
    use Html;

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

    function links(array $paginationData, array $options = []): string
    {
        if (! $paginationData) {
            return '';
        }

        $styles = $options['styles'] ?? [];
        $containerTag = $options['container-tag'] ?? 'nav';
        $itemTag = $options['item-tag'] ?? 'span';
        $linkTag = $options['link-tag'] ?? 'a';

        $link = fn($item) => Html\tag($linkTag, (string) $item, [
            ...! empty($styles['link'] ?? []) ? ['style' => [
                ...$item === $paginationData['current_page'] ?
                    $styles['link-current'] ?? [] : [],
                ...$styles['link'] ?? [],
            ]] : []
        ])->href(($options['url'] ?? '') . '?page=' . $item);

        $item = fn($item) => Html\tag($itemTag, $link($item), [
            ...! empty($styles['item'] ?? []) ? ['style' => [
                ...$item === $paginationData['current_page'] ?
                    $styles['item-current'] ?? [] : [],
                ...$styles['item'] ?? [],
            ]] : []
        ]);

        $links = Html\tag(
            $containerTag,
            \array_map($item, \range(1, $paginationData['total_page']))
        );

        if (isset($styles['container']) && \is_array($styles['container'])) {
            $links->style([...$styles['container'] ?? []]);
        }

        return (string) $links;
    }

    /**
     * Pagination data constant
     *
     * @internal
     */
    const DATA = 'Pagination\DATA';
};

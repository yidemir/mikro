<?php
declare(strict_types=1);

namespace pagination;

use request;

function paginate(array $options)
{
    if (!key_exists('totalItems', $options)) {
        throw new \InvalidArgumentException('Toplam sayfa sayısı belirlenmelidir');
    }

    $totalItems = $options['totalItems'];
    $currentPage = $options['currentPage'] ?? request\input('page', 1);
    $currentPage = \intval($currentPage);
    $currentPage = $currentPage < 0 ? 0 : $currentPage;
    $perPage = $options['perPage'] ?? 10;
    $totalPages = $perPage == 0 ? 0 : \ceil($totalItems / $perPage);
    $totalPages = \intval($totalPages);
    $maxPages = $options['maxPages'] ?? 7;
    $pattern = $options['pattern'] ?? '?page=:number';

    $pages = [];

    if ($totalPages <= 1) {
        return [];
    }

    if ($maxPages < 3) {
        $maxPages = 3;
    }

    $create = function(?int $number = null, bool $current = false) use ($pattern)  {
        $url = $number !== null ? \str_replace(':number', $number, $pattern) : null;
        return (object) \compact('number', 'url', 'current');
    };

    if ($totalPages <= $maxPages) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $pages[] = $create($i, $i === $currentPage);
        }
    } else {
        $numAdjacents = (int) floor(($maxPages - 3) / 2);
        if ($currentPage + $numAdjacents > $totalPages) {
            $slidingStart = $totalPages - $maxPages + 2;
        } else {
            $slidingStart = $currentPage - $numAdjacents;
        }

        if ($slidingStart < 2) {
            $slidingStart = 2;
        }

        $slidingEnd = $slidingStart + $maxPages - 3;
        if ($slidingEnd >= $totalPages) {
            $slidingEnd = $totalPages - 1;
        }

        $pages[] = $create(1, $currentPage === 1);

        if ($slidingStart > 2) {
            $pages[] = $create();
        }

        for ($i = $slidingStart; $i <= $slidingEnd; $i++) {
            $pages[] = $create($i, $i === $currentPage);
        }

        if ($slidingEnd < ($totalPages - 1)) {
            $pages[] = $create();
        }
        
        $pages[] = $create($totalPages, $currentPage === $totalPages);
    }

    $start = ($currentPage * $perPage) - $perPage;
    $start = $start < 0 ? 0 : $start;
    $limit = "$start, $perPage";

    $result = (object) \compact(
        'currentPage', 'totalPages', 'perPage', 'start', 'limit', 'pages'
    );

    function data($data = null)
    {
        static $pages;

        if ($pages === null) {
            $pages = $data;
        }

        return $pages;
    }

    data($result);

    return $result;
}

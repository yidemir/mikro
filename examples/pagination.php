<?php

pagination\paginate([
    'totalItems' => 100, // required

    // optionals:
    'currentPage' => request\input('page', 1),
    'perPage' => 10,
    'maxPages' => 7,
    'pattern' => '/?page=:number'
]);

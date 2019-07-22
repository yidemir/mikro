<?php

$categories = [
    '' => 'Please Select',
    1 => 'Category 1',
    2 => 'Category 2'
];

$attributes['selectedOption'] = 1;
$attributes['optionAttributes'] = [
    2 => ['data-foo' => 'bar']
];

echo html\tag('link')
    ->rel('stylesheet')
    ->href('https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css');

echo html\div('Please fill out the form completely.', [
    'class' => 'alert alert-warning container',
    'role' => 'alert',
    'style' => ['border-radius' => 0]
]);

echo html\tag('form', [
    html\div([
        html\label('Title (Form Input)'),
        html\input('text', 'title')->class('form-control')
    ])->class('form-group'),

    html\div([
        html\label('Category (Form Select)', 'category_id'),
        html\select($categories, 'category_id', $attributes)->class('form-control')
    ])->class('form-group'),

    html\div([
        html\label('Description (Form Textarea)', 'description'),
        html\textarea('', 'description')->class('form-control')->rows(5)->id(false)
    ])->class('form-group'),

    html\tag('hr'),

    html\div([
        html\input('checkbox', 'is_pushlibed')->class('form-check-input'),
        html\label('Published (Form Checkbox)', 'is_pushlibed')->class('form-check-label')
    ])->class('form-group form-check'),

    html\tag('hr')->style(['margin-bottom' => '15px']),

    html\div([
        html\label('Form Multiple Select'),
        html\select(['Foo', 'Bar', 'Baz', 'Qux'])->class('form-control')->multiple()
    ]),

    html\div(
        html\button('Save')->type('submit')->class('btn btn-success')
    )->class('form-group pt-3')
])->class('container mt-5')->method('post')->action('/path/to/save');

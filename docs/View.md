# View
Simple view handler

## Rendering View File
```php
View\render('view-file', ['data' => 'value']); // returns string
```

## View Blocks
**Set view block:**
```php
View\start('content');
<p>Content</p>
View\stop();

// or

View\set('content', 'String Content');
```

**Call view block:**
```php
echo View\get('content');
```

## Example

**index.php**
```php
$mikro[View\PATH] = __DIR__ . '/views';

View\render('index', ['message' => 'Hello world!']);
```

**views/layout.php**
```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View\get('title', 'Default Title') ?></title>
</head>
<body>
    <?= View\get('content') ?>
</body>
</html>
```

**views/index.php**
```php
<?php View\set('title', 'A Big Title!') ?>

<?php View\start('content') ?>
<p>Message: <?= View\e($message) ?>
<?php View\stop() ?>

<?= View\render('layout') ?>
```

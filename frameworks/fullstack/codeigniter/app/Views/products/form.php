<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title><?= $product ? 'Edit product' : 'New product' ?></title></head>
<body>
<h1><?= $product ? 'Edit product' : 'New product' ?></h1>

<?php if (session('errors')): ?>
    <ul>
        <?php foreach (session('errors') as $error): ?>
            <li><?= esc($error) ?></li>
        <?php endforeach ?>
    </ul>
<?php endif ?>

<?= form_open($product ? 'products/update/' . $product['id'] : 'products/create') ?>
    <label>Name <input name="name" value="<?= esc(old('name', $product['name'] ?? '')) ?>"></label>
    <label>Price <input name="price" value="<?= esc(old('price', $product['price'] ?? '')) ?>"></label>
    <button type="submit">Save</button>
<?= form_close() ?>

<p><a href="<?= site_url('products') ?>">Back to the list</a></p>
</body>
</html>

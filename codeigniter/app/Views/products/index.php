<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Products</title></head>
<body>
<p><a href="<?= url_to('logout') ?>">Logout</a></p>

<h1>Products</h1>

<p><a href="<?= site_url('products/new') ?>">New product</a></p>

<table>
    <thead>
    <tr><th>Name</th><th>Price</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($products as $product): ?>
        <tr>
            <td><?= esc($product['name']) ?></td>
            <td><?= number_format((float) $product['price'], 2) ?></td>
            <td>
                <a href="<?= site_url('products/edit/' . $product['id']) ?>">Edit</a>
                <?= form_open('products/delete/' . $product['id']) ?>
                    <button type="submit">Delete</button>
                <?= form_close() ?>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>

<?= $pager->links() ?>
</body>
</html>

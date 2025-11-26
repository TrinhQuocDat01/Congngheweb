<?php
$dataFile = __DIR__ . '/data/flowers.json';
$flowers = [];

if (file_exists($dataFile)) {
    $flowers = json_decode(file_get_contents($dataFile), true) ?: [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Danh sách hoa</title>
    <style>
        body { font-family: Arial; }
        .flower { display: flex; gap: 15px; margin-bottom: 20px; }
        .flower img { width: 200px; height: 150px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>

<h1>Danh sách các loài hoa</h1>

<?php foreach ($flowers as $f): ?>
<div class="flower">
    <img src="images/<?= htmlspecialchars($f['image']) ?>" alt="<?= htmlspecialchars($f['name']) ?>">

    <div>
        <h2><?= htmlspecialchars($f['name']) ?></h2>
        <p><?= htmlspecialchars($f['description']) ?></p>
    </div>
</div>
<?php endforeach; ?>

</body>
</html>

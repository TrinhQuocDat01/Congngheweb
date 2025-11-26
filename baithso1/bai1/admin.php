<?php
// admin.php

$dataFile = __DIR__ . '/data/flowers.json';

// Đọc dữ liệu hoa
$flowers = [];
if (file_exists($dataFile)) {
    $flowers = json_decode(file_get_contents($dataFile), true) ?: [];
}

// ====== XỬ LÝ THÊM HOA ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);

    // Xử lý ảnh upload
    $imgName = "";
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imgName = uniqid('flower_') . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__.'/images/'.$imgName);
    }

    // Thêm vào danh sách
    $flowers[] = [
        'id' => time(),
        'name' => $name,
        'description' => $desc,
        'image' => $imgName
    ];

    // Lưu lại vào file JSON
    file_put_contents($dataFile, json_encode($flowers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header("Location: admin.php");
    exit;
}

// ====== XỬ LÝ XÓA HOA ======
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    foreach ($flowers as $k => $fl) {
        if ($fl['id'] == $id) {

            // Xóa ảnh
            if (!empty($fl['image']) && file_exists(__DIR__.'/images/'.$fl['image'])) {
                unlink(__DIR__.'/images/'.$fl['image']);
            }

            unset($flowers[$k]);
        }
    }

    file_put_contents($dataFile, json_encode(array_values($flowers), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    header("Location: admin.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Flowers</title>
</head>
<body>
<h1>Quản trị danh sách hoa</h1>

<h2>Thêm loài hoa</h2>
<form method="post" enctype="multipart/form-data">
    Tên hoa: <br>
    <input type="text" name="name" required><br><br>

    Mô tả: <br>
    <textarea name="description" required></textarea><br><br>

    Ảnh: <br>
    <input type="file" name="image" accept="image/*"><br><br>

    <button type="submit" name="add">Thêm hoa</button>
</form>

<hr>

<h2>Danh sách hoa hiện có</h2>
<table border="1" cellspacing="0" cellpadding="8">
    <tr>
        <th>ID</th>
        <th>Ảnh</th>
        <th>Tên</th>
        <th>Mô tả</th>
        <th>Xóa</th>
    </tr>

    <?php foreach ($flowers as $f): ?>
    <tr>
        <td><?= $f['id'] ?></td>

        <td>
            <?php if ($f['image']): ?>
                <img src="images/<?= htmlspecialchars($f['image']) ?>" width="120">
            <?php endif; ?>
        </td>

        <td><?= htmlspecialchars($f['name']) ?></td>
        <td><?= htmlspecialchars($f['description']) ?></td>

        <td>
            <a href="admin.php?delete=<?= $f['id'] ?>" onclick="return confirm('Xóa hoa này?');">Xóa</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

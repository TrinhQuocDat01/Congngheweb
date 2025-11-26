<?php
// Đường dẫn tới file CSV
$csvFile = "65HTTT_Danh_sach_diem_danh.csv";

// Đọc toàn bộ dữ liệu trong CSV
$rows = array_map("str_getcsv", file($csvFile));

// Dòng đầu là tên cột
$header = array_shift($rows);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Bài 3 - Đọc tệp CSV</title>
    <style>
        table {
            width: 60%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px;
        }
        th {
            background: #eee;
        }
    </style>
</head>
<body>

<h2>Danh sách tài khoản từ file CSV</h2>

<table>
    <tr>
        <?php foreach ($header as $col): ?>
            <th><?= htmlspecialchars($col) ?></th>
        <?php endforeach; ?>
    </tr>

    <?php foreach ($rows as $row): ?>
        <tr>
            <?php foreach ($row as $cell): ?>
                <td><?= htmlspecialchars($cell) ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>

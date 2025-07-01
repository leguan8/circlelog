<?php
$uploadDir = 'uploads/';
$dataFile = 'data/artworks.json';

if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
if (!file_exists('data')) mkdir('data', 0777, true);
if (!file_exists($dataFile)) file_put_contents($dataFile, '[]');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titleInput = trim($_POST['title'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;
    $date = $_POST['date'] ?? date('Y-m-d');
    $weatherType = $_POST['weather'] ?? '未知';
    $temperature = $_POST['temperature'] ?? '';
    $weather = $weatherType;
    if ($temperature !== '') {
        $weather .= '，' . $temperature . '°C';
    }

    // 自动编号
    $artworks = json_decode(file_get_contents($dataFile), true);
    $maxNumber = 0;
    foreach ($artworks as $item) {
        if (preg_match('/圈记\s+No\.(\d+)/u', $item['title'], $matches)) {
            $num = intval($matches[1]);
            if ($num > $maxNumber) $maxNumber = $num;
        }
    }
    $nextNumber = $maxNumber + 1;
    $title = "圈记 No.$nextNumber " . $titleInput;

    if ($lat == 0 && $lng == 0) {
        die("❌ 请通过地图点击选择有效的经纬度坐标！");
    }

    if (!empty($_FILES['image']['tmp_name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('art_', true) . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            die("❌ 图片上传失败！");
        }
        $imageUrl = $targetPath;
    } else {
        die("❌ 未上传图片！");
    }

    $newWork = [
        'title' => $title,
        'coordinates' => [$lat, $lng],
        'image' => $imageUrl,
        'time' => $date,
        'notes' => $notes,
        'weather' => $weather
    ];

    $artworks[] = $newWork;
    file_put_contents($dataFile, json_encode($artworks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    echo "<p>✅ 上传成功！<a href='index.html'>返回地图</a></p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
  <meta charset="UTF-8" />
  <title>上传圈记作品</title>
  <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  />
  <style>
    #map {
      height: 300px;
      margin: 10px 0;
    }
  </style>
</head>
<body>
  <h2>上传圈记作品</h2>
  <form method="post" enctype="multipart/form-data">
    <label>标题（不含“圈记 No.”）:<br />
      <input type="text" name="title" required />
    </label><br /><br />

    <label>时间:<br />
      <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required />
    </label><br /><br />

    <label>说明:<br />
      <textarea name="notes" rows="3" cols="40"></textarea>
    </label><br /><br />

    <label>天气类型:<br />
      <select name="weather">
        <option value="多云">多云</option>
        <option value="阴">阴</option>
        <option value="晴">晴</option>
        <option value="雨">雨</option>
        <option value="雪">雪</option>
        <option value="风">风</option>
        <option value="夜">夜</option>
      </select>
    </label><br /><br />

    <label>温度 (°C):<br />
      <select name="temperature">
        <option value="">-- 请选择 --</option>
        <?php for ($i = -20; $i <= 40; $i += 5): ?>
          <option value="<?php echo $i; ?>"><?php echo $i; ?>°C</option>
        <?php endfor; ?>
      </select>
    </label><br /><br />

    <label>纬度 (点击地图选择):<br />
      <input type="text" id="lat_display" readonly />
      <input type="hidden" name="lat" id="lat" required />
    </label><br /><br />

    <label>经度 (点击地图选择):<br />
      <input type="text" id="lng_display" readonly />
      <input type="hidden" name="lng" id="lng" required />
    </label><br /><br />

    <div id="map"></div>

    <label>上传图片:<br />
      <input type="file" name="image" accept="image/*" required />
    </label><br /><br />

    <button type="submit">提交</button>
  </form>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    const map = L.map('map').setView([39.92, 116.38], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap'
    }).addTo(map);

    let marker;
    map.on('click', function(e) {
      const { lat, lng } = e.latlng;
      document.getElementById('lat_display').value = lat.toFixed(6);
      document.getElementById('lng_display').value = lng.toFixed(6);
      document.getElementById('lat').value = lat.toFixed(6);
      document.getElementById('lng').value = lng.toFixed(6);

      if (marker) {
        marker.setLatLng(e.latlng);
      } else {
        marker = L.marker(e.latlng).addTo(map);
      }
    });
  </script>
</body>
</html>

<?php
header('Content-Type: application/json');
$f='data/artworks.json';
if(!file_exists($f)){ echo '[]'; exit; }
$json=file_get_contents($f);
$out=json_decode($json,true);
echo json_encode($out,JSON_UNESCAPED_UNICODE);

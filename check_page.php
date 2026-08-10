<?php
$ch = curl_init('http://127.0.0.1:8000/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$content = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Status: " . $info['http_code'] . "\n";
echo "Content-Type: " . ($info['content_type'] ?? 'none') . "\n";
echo "Content Length: " . strlen($content) . "\n";
echo "\n--- First 500 chars ---\n";
echo substr($content, 0, 500) . "\n";
echo "\n--- Contains 'app.css'? " . (strpos($content, 'app.css') !== false ? 'YES' : 'NO') . "\n";
echo "--- Contains 'app.ts'? " . (strpos($content, 'app.ts') !== false ? 'YES' : 'NO') . "\n";
echo "--- Contains 'id="app"'? " . (strpos($content, 'id="app"') !== false ? 'YES' : 'NO') . "\n";
echo "--- Contains 'build/assets'? " . (strpos($content, 'build/assets') !== false ? 'YES' : 'NO') . "\n";

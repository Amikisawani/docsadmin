<?php
// Script temporaire : extraction du texte brut des PDF modeles
// pour comprendre leur mise en page.

function extractPdfText($filePath) {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return "ERREUR: impossible de lire le fichier\n";
    }

    $out = array();

    // Chercher les flux de texte
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches);
    foreach ($matches[1] as $streamData) {
        $decoded = @gzuncompress($streamData);
        if ($decoded === false) {
            $decoded = $streamData;
        }

        // Extraction des segments (text) Tj
        preg_match_all('/\((?:\\.|[^\\\\()])*\)\s*Tj/', $decoded, $tj);
        foreach ($tj[0] as $t) {
            $text = substr($t, 1, strrpos($t, ')') - 1);
            $text = str_replace(array('\\(', '\\)', '\\\\', '\n', '\r', '\t'), array('(', ')', '\\', "\n", "\r", "\t"), $text);
            if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $text)) continue;
            $out[] = $text;
        }

        // Extraction des arrays [(a) (b)] TJ
        preg_match_all('/\[(?:\((?:\\.|[^\\\\()])*\)\s*)+\]\s*TJ/', $decoded, $tja);
        foreach ($tja[0] as $t) {
            preg_match_all('/\(((?:\\.|[^\\\\()])*)\)/', $t, $inner);
            $joined = '';
            foreach ($inner[1] as $seg) {
                $seg = str_replace(array('\\(', '\\)', '\\\\', '\n', '\r', '\t'), array('(', ')', '\\', "\n", "\r", "\t"), $seg);
                if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $seg)) continue;
                $joined .= $seg;
            }
            if ($joined !== '') $out[] = $joined;
        }
    }

    if (empty($out)) {
        return "(Aucun texte extractible - PDF probablement scanné/vectoriel)\n";
    }

    $out = array_values(array_unique($out));
    return implode("\n", $out) . "\n";
}

$files = array(
    'fichierAttestation.pdf',
    'fichierArrete.pdf',
    'fichierNotification.pdf',
);

foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    echo "===================== $f =====================\n";
    if (!file_exists($path)) {
        echo "N'existe pas\n";
        continue;
    }
    echo extractPdfText($path);
    echo "\n\n";
}

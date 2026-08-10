<?php

namespace App\Application\MailMerge;

use ZipArchive;

/**
 * Parseur de fichiers de destinataires pour le publipostage.
 *
 * Formats acceptés :
 *  - TXT / CSV  : auto-détection du délimiteur (tabulation, point-virgule, virgule, pipe)
 *  - XLSX       : lecture native via ZipArchive + XML (sans PhpSpreadsheet)
 *  - XLS legacy : repli sur une lecture CSV/HTML best-effort
 *
 * La première ligne (en-têtes) est mappée automatiquement vers les clés
 * de variables de fusion (nom, postnom, prenom, matricule, grade, fonction,
 * service, direction, administration, ministere, ville, ...).
 */
final class RecipientFileParser
{
    /** Mapping automatique en-tête → clé de variable. */
    private const HEADER_MAP = [
        'nom' => 'nom',
        'name' => 'nom',
        'lastname' => 'nom',
        'last_name' => 'nom',
        'postnom' => 'postnom',
        'prenom' => 'prenom',
        'firstname' => 'prenom',
        'first_name' => 'prenom',
        'givenname' => 'prenom',
        'nom_complet' => 'nom_complet',
        'fullname' => 'nom_complet',
        'full_name' => 'nom_complet',
        'civilite' => 'civilite',
        'genre' => 'civilite',
        'sexe' => 'civilite',
        'gender' => 'civilite',
        'matricule' => 'matricule',
        'matricule_agent' => 'matricule',
        'grade' => 'grade',
        'fonction' => 'fonction',
        'poste' => 'fonction',
        'service' => 'service',
        'direction' => 'direction',
        'administration' => 'administration',
        'ministere' => 'ministere',
        'adresse_administration' => 'adresse_administration',
        'adresse' => 'adresse_administration',
        'ville' => 'ville',
        'email' => 'email',
        'telephone' => 'telephone',
        'tel' => 'telephone',
    ];

    /**
     * Parse un fichier et renvoie une liste de destinataires.
     *
     * @return array<int, array{name: string, variables: array<string, string>}>
     */
    public function parse(string $fullPath, string $originalName): array
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $rows = match ($extension) {
            'xlsx' => $this->parseXlsx($fullPath),
            'xls' => $this->parseXlsLegacy($fullPath),
            default => $this->parseDelimited($fullPath),
        };

        return $this->buildRecipients($rows);
    }

    /**
     * Aperçu du fichier : renvoie les en-têtes détectés + les premières lignes.
     *
     * @return array{headers: string[], rows: array<int, array<string, string>>, count: int}
     */
    public function preview(string $fullPath, string $originalName, int $limit = 5): array
    {
        $recipients = $this->parse($fullPath, $originalName);

        $headers = [];
        if (! empty($recipients)) {
            $headers = array_keys($recipients[0]['variables']);
        }

        $rows = array_slice($recipients, 0, $limit);

        return [
            'headers' => $headers,
            'rows' => array_map(fn ($r) => $r['variables'], $rows),
            'count' => count($recipients),
        ];
    }

    /** Parse un fichier texte délimité (CSV/TXT) avec auto-détection du séparateur. */
    private function parseDelimited(string $fullPath): array
    {
        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new \RuntimeException('Impossible de lire le fichier.');
        }

        // Normalisation BOM UTF-8
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;

        $delimiter = $this->detectDelimiter($content);
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));

        if (count($lines) < 2) {
            return [];
        }

        $headers = $this->splitLine($lines[0], $delimiter);
        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            $cells = $this->splitLine($line, $delimiter);
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $cells[$i] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /** Détecte le délimiteur le plus fréquent dans les lignes (hors en-tête). */
    private function detectDelimiter(string $content): string
    {
        $sample = substr($content, 0, 8192);
        $candidates = ["\t", ';', ',', '|'];

        $best = ',';
        $bestCount = 0;

        foreach ($candidates as $candidate) {
            $count = substr_count($sample, $candidate);
            if ($count > $bestCount) {
                $best = $candidate;
                $bestCount = $count;
            }
        }

        return $best;
    }

    /** Découpe une ligne en cellules en respectant les guillemets simples/doubles. */
    private function splitLine(string $line, string $delimiter): array
    {
        $cells = [];
        $current = '';
        $inQuotes = false;
        $quoteChar = '';

        $length = strlen($line);
        for ($i = 0; $i < $length; $i++) {
            $char = $line[$i];

            if ($inQuotes) {
                if ($char === $quoteChar) {
                    // Guillemet échappé (doublé)
                    if (($i + 1) < $length && $line[$i + 1] === $quoteChar) {
                        $current .= $quoteChar;
                        $i++;
                    } else {
                        $inQuotes = false;
                    }
                } else {
                    $current .= $char;
                }
            } else {
                if ($char === '"' || $char === "'") {
                    $inQuotes = true;
                    $quoteChar = $char;
                } elseif ($char === $delimiter) {
                    $cells[] = trim($current);
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }

        $cells[] = trim($current);

        return $cells;
    }

    /** Parse un fichier XLSX (feuille 1) via ZipArchive + XML. */
    private function parseXlsx(string $fullPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($fullPath) !== true) {
            throw new \RuntimeException('Fichier XLSX invalide.');
        }

        try {
            // Lecture de la feuille active (workbook.xml → premier sheet)
            $sheets = $this->readWorkbookSheets($zip);
            $sheetName = $sheets[0] ?? 'Sheet1';

            $sharedStrings = $this->readSharedStrings($zip);

            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                // Essayer par nom de feuille
                foreach ($zip->getNameIndex() as $i => $name) {
                    if (stripos($name, 'worksheets/') !== false && stripos($name, $sheetName) !== false) {
                        $sheetXml = $zip->getFromName($name);
                        break;
                    }
                }
            }
            if ($sheetXml === false) {
                throw new \RuntimeException('Feuille de calcul introuvable.');
            }

            $xml = simplexml_load_string($sheetXml);
            if ($xml === false) {
                throw new \RuntimeException('Feuille de calcul illisible.');
            }

            $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rows = [];

            foreach ($xml->xpath('//m:sheetData/m:row') as $row) {
                $cells = [];
                foreach ($row->c as $cell) {
                    $ref = (string) $cell['r']; // ex: "A1"
                    $col = preg_replace('/[0-9]/', '', $ref);
                    $type = (string) $cell['t'];
                    $value = '';

                    if (isset($cell->v)) {
                        $raw = (string) $cell->v;
                        if ($type === 's') {
                            $value = $sharedStrings[(int) $raw] ?? '';
                        } elseif ($type === 'inlineStr') {
                            $value = (string) ($cell->is->t ?? '');
                        } else {
                            $value = $raw;
                        }
                    } elseif (isset($cell->is)) {
                        $value = (string) ($cell->is->t ?? '');
                    }

                    $cells[$col] = trim($value);
                }

                // Transformer en ligne indexée
                $rowData = [];
                $colIndexes = $this->columnIndexes();
                foreach ($cells as $col => $value) {
                    $idx = $colIndexes[$col] ?? null;
                    if ($idx !== null) {
                        $rowData[$idx] = $value;
                    }
                }
                ksort($rowData);
                $rows[] = array_values($rowData);
            }

            return $this->rowsToAssociative($rows);
        } finally {
            $zip->close();
        }
    }

    /** Lit workbook.xml pour déterminer les noms de feuilles. */
    private function readWorkbookSheets(ZipArchive $zip): array
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        if ($wb === false) {
            return [];
        }

        $xml = @simplexml_load_string($wb);
        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sheets = [];
        foreach ($xml->xpath('//m:sheets/m:sheet') as $sheet) {
            $sheets[] = (string) $sheet['name'];
        }

        return $sheets;
    }

    /** Lit sharedStrings.xml. */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if ($ss === false) {
            return [];
        }

        $xml = @simplexml_load_string($ss);
        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $strings = [];
        foreach ($xml->xpath('//m:si') as $si) {
            $strings[] = trim(implode('', $si->xpath('.//m:t')));
        }

        return $strings;
    }

    /** Parse un fichier XLS legacy (repli : lire les données HTML ou binaire simplifié). */
    private function parseXlsLegacy(string $fullPath): array
    {
        $content = file_get_contents($fullPath);
        if ($content === false) {
            return [];
        }

        // Les fichiers .xls peuvent être en réalité du CSV/HTML avec extension trompeuse.
        if (strpos($content, '<html') !== false || strpos($content, '<table') !== false) {
            return $this->parseHtmlTable($content);
        }

        // Sinon, on tente un parsing délimité.
        $tmp = tempnam(sys_get_temp_dir(), 'xls');
        file_put_contents($tmp, $content);
        try {
            return $this->parseDelimited($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    /** Parse un tableau HTML (cas des .xls exportés depuis Excel). */
    private function parseHtmlTable(string $content): array
    {
        $doc = new \DOMDocument;
        @$doc->loadHTML($content);
        $tables = $doc->getElementsByTagName('table');
        if ($tables->length === 0) {
            return [];
        }

        $rows = [];
        foreach ($tables->item(0)->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->getElementsByTagName('td') as $td) {
                $cells[] = trim($td->textContent);
            }
            // En-têtes : th
            if (empty($cells)) {
                foreach ($tr->getElementsByTagName('th') as $th) {
                    $cells[] = trim($th->textContent);
                }
            }
            if (! empty($cells)) {
                $rows[] = $cells;
            }
        }

        return $this->rowsToAssociative($rows);
    }

    /** Convertit des lignes indexées en lignes associatives (première ligne = en-têtes). */
    private function rowsToAssociative(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $headers = array_shift($rows);
        $result = [];

        foreach ($rows as $row) {
            $assoc = [];
            foreach ($headers as $i => $header) {
                $assoc[$header] = $row[$i] ?? '';
            }
            $result[] = $assoc;
        }

        return $result;
    }

    /** Index colonne Excel → entier. */
    private function columnIndexes(): array
    {
        $map = [];
        for ($i = 0; $i < 256; $i++) {
            $col = '';
            $n = $i;
            do {
                $col = chr(65 + ($n % 26)).$col;
                $n = intdiv($n, 26) - 1;
            } while ($n >= 0);
            $map[$col] = $i;
        }

        return $map;
    }

    /** Construit les destinataires avec les variables normalisées + dérivées. */
    private function buildRecipients(array $rows): array
    {
        $recipients = [];

        foreach ($rows as $row) {
            $vars = [];
            foreach ($row as $header => $value) {
                $key = $this->normalizeHeader($header);
                if ($key !== null && $value !== '') {
                    $vars[$key] = (string) $value;
                }
            }

            if (empty($vars)) {
                continue;
            }

            // Dérivation des variables composites
            $vars = $this->deriveVariables($vars);

            $name = $vars['nom_complet']
                ?? trim(($vars['prenom'] ?? '').' '.($vars['nom'] ?? '').' '.($vars['postnom'] ?? ''));

            if (trim($name) === '') {
                $name = 'Destinataire';
            }

            $recipients[] = [
                'name' => $name,
                'destinataire' => $name,
                'variables' => $vars,
            ];
        }

        return $recipients;
    }

    /** Normalise un en-tête (accent, casse, espaces) puis le mappe vers une clé canonique. */
    private function normalizeHeader(string $header): ?string
    {
        $normalized = strtolower(trim($header));
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^a-z]+/', '_', $normalized);
        $normalized = trim($normalized, '_');

        // Recherche directe
        if (isset(self::HEADER_MAP[$normalized])) {
            return self::HEADER_MAP[$normalized];
        }

        // Recherche par sous-chaîne (ex: "nom de l'agent", "prénom(s)")
        foreach (self::HEADER_MAP as $pattern => $key) {
            if (strlen($pattern) > 3 && strpos($normalized, $pattern) !== false) {
                return $key;
            }
        }

        // Sinon, on garde la clé normalisée si elle est raisonnable
        if ($normalized !== '' && strlen($normalized) <= 40 && preg_match('/^[a-z_]+$/', $normalized)) {
            return $normalized;
        }

        return null;
    }

    /** Calcule les variables dérivées (nom_complet, civilite). */
    private function deriveVariables(array $vars): array
    {
        // nom_complet : prenom + nom + postnom (ordre congolais)
        if (empty($vars['nom_complet'])) {
            $parts = array_filter([
                $vars['prenom'] ?? '',
                $vars['nom'] ?? '',
                $vars['postnom'] ?? '',
            ]);
            if (! empty($parts)) {
                $vars['nom_complet'] = implode(' ', $parts);
            }
        }

        // civilite : à partir du sexe/genre si présent
        if (empty($vars['civilite'])) {
            $sexe = strtolower($vars['sexe'] ?? $vars['genre'] ?? '');
            if (in_array($sexe, ['m', 'masculin', 'male', 'homme', 'm.', 'm '], true)) {
                $vars['civilite'] = 'Monsieur';
            } elseif (in_array($sexe, ['f', 'feminin', 'female', 'femme', 'mme', 'mme '], true)) {
                $vars['civilite'] = 'Madame';
            } else {
                $vars['civilite'] = '';
            }
        }

        return $vars;
    }
}

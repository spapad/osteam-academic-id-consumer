<?php
// barebone app - autoload classes from src/ dir
spl_autoload_register(function ($class_name) {
    $class_name_parts = explode('\\', $class_name);
    $class_filename = __DIR__ . '/src/' . end($class_name_parts) . '.php';
    if (file_exists($class_filename)) {
        include $class_filename;
        if (class_exists($class_name)) {
            return true;
        }
    }
    return false;
});

use Gr\Gov\Minedu\Osteam\App\Client;

$settings = require(__DIR__ . '/settings.php');

/**
 * --input <file> το αρχείο εισόδου 
 * --amka <column#> η σειρά που εμφανίζεται στο csv η στήλη με των κωδικό ΑΜΚΑ
 */
$options = getopt('', ['input:', 'amka:', 'line:', 'maxcheck:']);

$input_file = isset($options['input']) ? $options['input'] : null;
$amka_column = isset($options['amka']) ? abs(intval($options['amka'])) : 0;
$line = isset($options['line']) ? abs(intval($options['line'])) : 1;
$maxcheck = isset($options['maxcheck']) ? abs(intval($options['maxcheck'])) : null;

$have_files = false;
if ($input_file !== null) {
    if (is_string($input_file)) {
        $input_file = [$input_file];
    }
    $valid_files = array_filter($input_file, function ($v) {
        return is_file($v) && is_readable($v);
    });
    $have_files = (count($valid_files) == 1);
}

/**
 * Έλεγχος παραμέτρων
 */
if (!$have_files || $amka_column < 0 || $line < 1) {
    echo "Χρήση: {$argv[0]} [--input <file>] [--amka <amka column #>]", PHP_EOL,
    "       input <file>: έλεγχος στοιχείων από το αρχείο <file> (ένα μόνο αρχείο)", PHP_EOL,
    "    amka <column #>: αριθμός στήλης που περιέχει το ΑΜΚΑ (πρώτη == 0)", PHP_EOL,
    "line <start line #>: αριθμός πρώτης γραμμής με δεδομένα (πρώτη == 1)", PHP_EOL,
    exit(0);
}

echo "Ζητήσατε να γίνει έλεγχος από τo αρχείo: ",
    implode(', ', $valid_files), PHP_EOL,
    'ένραξη στοιχείων από τη γραμμή: ', $line, PHP_EOL,
    'και αριθμό στήλης ΑΜΚΑ: ', $amka_column, PHP_EOL,
    'Έλεγχος ', ($maxcheck === null ? 'όλων των' : $maxcheck), ' εγγραφών', PHP_EOL;
$confirm = mb_strtoupper(readline("Να συνεχίσουμε; (y/N/ν/Ο) "));

if ($confirm !== 'Y' && $confirm !== 'Ν') {
    die("Τέλος"); 
}

/**
 * 
 */
$client = new Client([ 
    'base_uri' => $settings['base_uri'],
    'username' => $settings['username'],
    'password' => $settings['password']
]);

$file = $valid_files[0];
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
for ($i = 1; $i < $line; $i++) {
    array_shift($lines);
    echo "POP {$i} lines...", PHP_EOL;
}
$csv_data = array_map('str_getcsv', $lines);
if ($maxcheck !== null) {
    array_splice($csv_data, $maxcheck);
}
// echo var_export($csv_data, true);

$get = array_walk($csv_data, function (&$v, $k) use ($client, $amka_column, $settings) {
    if (!isset($v[$amka_column])) {
        echo "Δεν υπάρχει στήλη με στοιχεία ΑΜΚΑ", PHP_EOL;
        $v['__STATUS__'] = 'INVALID';
        return;
    }
    $amka = $v[$amka_column];
    if (preg_match('/^[0-9]{11}$/', $amka) !== 1) {
        echo "Το ΑΜΚΑ {$amka} δεν είναι 11-ψήφιος αριθμός", PHP_EOL;
        $v['__STATUS__'] = 'INVALID';
        return;
    }
    echo $amka;
    $response = '';
    try {
        $response = $client->check($amka, $settings['auth_header']);
        // $response = $client->rawcheck($amka);
        if ($response === false) {
            $response = 'false';
        }
        $first = mb_substr($response, 0, 1);
        switch ($first) {
            case '{':
                $v['__STATUS__'] = 'UNKNOWN';
                break;
            case 'f':
                $v['__STATUS__'] = 'ERROR';
                break;
            case 'Π':
            case 'Μ':
            case 'Δ':
                $v['__STATUS__'] = $first;
                break;
            default:
                $v['__STATUS__'] = 'UNKNOWN';
                break;
        }
    } catch (\Exception $e) {
        $v['__STATUS__'] = 'ERROR';
    }
    echo ' ... ', $v['__STATUS__'], PHP_EOL;

    if (isset($settings['timeout'])) {
        sleep($settings['timeout']);
    }
});

// echo var_export($csv_data, true);
echo "Results:", PHP_EOL;
$stats = [
    'TOTAL' => count($csv_data),
    'ERROR' => 0,
    'UNKNOWN' => 0,
    'Π' => 0,
    'Μ' => 0,
    'Δ' => 0
];
$stat = array_walk($csv_data, function ($v, $k) use (&$stats) {
    $stats[$v['__STATUS__']]++;    
});
foreach ($stats as $key => $val) {
    echo "- {$key}: {$val}", PHP_EOL;
}

echo "% results:", PHP_EOL,
    " Προπτυχιακοί: ", number_format(100 * $stats['Π'] / $stats['TOTAL'], 2), "% ({$stats['Π']})", PHP_EOL,
    "Μεταπτυχιακοί: ", number_format(100 * $stats['Μ'] / $stats['TOTAL'], 2), "% ({$stats['Μ']})", PHP_EOL,
    " Διδακτορικοί: ", number_format(100 * $stats['Δ'] / $stats['TOTAL'], 2), "% ({$stats['Δ']})", PHP_EOL,
    "         Άλλο: ", number_format(100 * ($stats['ERROR'] + $stats['UNKNOWN']) / $stats['TOTAL'], 2), "% (" . ($stats['ERROR'] + $stats['UNKNOWN']) . ")", PHP_EOL;

echo "Ολοκληρώθηκε.", PHP_EOL;
exit(0);

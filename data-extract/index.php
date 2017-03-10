<?php

$settings = require(__DIR__ . '/settings.php');

// barebone app - autoload classes from src/ dir
spl_autoload_register(function ($class_name) use ($settings) {
    $class_name_parts = explode('\\', $class_name);
    $class_filename = $settings['osteam_codebase'] . '/' . end($class_name_parts) . '.php';
    if (file_exists($class_filename)) {
        include $class_filename;
        if (class_exists($class_name)) {
            return true;
        }
    }
    // if not found, try to locate in src/ folder 
    $class_filename = __DIR__ . '/src/' . end($class_name_parts) . '.php';
    if (file_exists($class_filename)) {
        include $class_filename;
        if (class_exists($class_name)) {
            return true;
        }
    }
    return false;
});

use Gr\Gov\Minedu\Osteam\Slim\Client;
use Gr\Gov\Minedu\Osteam\App\Extract;

/**
 * --input <file> το αρχείο εισόδου 
 * --amka <column#> η σειρά που εμφανίζεται στο csv η στήλη με των κωδικό ΑΜΚΑ
 */
$options = getopt('', ['input:', 'output:', 'amka:', 'line:', 'maxcheck:']);

$input_file = isset($options['input']) ? $options['input'] : null;
$output_file = isset($options['output']) ? $options['output'] : 'out.csv';
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
    echo "Χρήση: {$argv[0]} [--input <file>] [--output <file>]", PHP_EOL, 
    "    [--amka <amka column #>] [--line <start line #>] [--maxcheck <max lines #>]", PHP_EOL,
    "          input <file>: έλεγχος στοιχείων από το αρχείο <file> (ένα μόνο αρχείο)", PHP_EOL,
    "         output <file>: τελικά αποτελέσματα (csv) στο <file> (ένα μόνο αρχείο)", PHP_EOL,
    "       amka <column #>: αριθμός στήλης που περιέχει το ΑΜΚΑ (πρώτη == 0)", PHP_EOL,
    "   line <start line #>: αριθμός πρώτης γραμμής με δεδομένα (πρώτη == 1)", PHP_EOL,
    "maxcheck <max lines #>: αριθμός εγγραφών για έλεγχο (κενό = όλες)", PHP_EOL,
    exit(0);
}

echo "Ζητήσατε να γίνει έλεγχος από τo αρχείo: ", implode(', ', $valid_files), PHP_EOL,
    '         Έναρξη στοιχείων από τη γραμμή: ', $line, PHP_EOL,
    '                    Αριθμός στήλης ΑΜΚΑ: ', $amka_column, PHP_EOL,
    '            Αποθήκευση στο αρχείο (csv): ', $output_file, PHP_EOL,
    'Έλεγχος ', ($maxcheck === null ? 'όλων των' : $maxcheck), ' εγγραφών', PHP_EOL;
$confirm = mb_strtoupper(readline("Να συνεχίσουμε; (y/N/ν/Ο) "));

if ($confirm !== 'Y' && $confirm !== 'Ν') {
    die("Τέλος"); 
}

/**
 * 
 */
function parse_csv_line($line) {
    return str_getcsv($line, ';');
}
$client = new Client($settings);

$app = new Extract($client, $settings); 

$file = $valid_files[0];
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

for ($i = 1; $i < $line; $i++) {
    array_shift($lines);
    echo "POP {$i} lines...", PHP_EOL;
}
$csv_data = array_map('parse_csv_line', $lines);
if ($maxcheck !== null) {
    array_splice($csv_data, $maxcheck);
}
// echo var_export($csv_data, true);

echo PHP_EOL,
    "=========================================", PHP_EOL,
    "Άντληση academic id από ΑΜΚΑ", PHP_EOL,
    PHP_EOL;
$get = array_walk($csv_data, function (&$v, $k) use ($app, $amka_column, $settings) {
    if (!isset($v[$amka_column])) {
        echo "Δεν υπάρχει στήλη με στοιχεία ΑΜΚΑ", PHP_EOL;
        $v['__ACADEMIC_ID__'] = '';
        $v['__STATUS__'] = 'INVALID';
        return;
    }
    $amka = $v[$amka_column];
    if (preg_match('/^[0-9]{11}$/', $amka) !== 1) {
        echo "Το ΑΜΚΑ {$amka} δεν είναι 11-ψήφιος αριθμός", PHP_EOL;
        $v['__ACADEMIC_ID__'] = '';
        $v['__STATUS__'] = 'INVALID';
        return;
    }
    echo $amka;
    $response = '';
    try {
        $response = $app->getAcademicID($amka, $settings['auth_header_student']);
        if (($response === false) || ($response === 'false')) {
            $v['__ACADEMIC_ID__'] = '';
            $v['__STATUS__'] = 'INVALID';
        } else {
            list ($beneficiary_name, $academic_id) = explode(',', $response);
            $first = mb_substr($beneficiary_name, 0, 1);
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
            $v['__ACADEMIC_ID__'] = $academic_id;
            // $v['__STATUS__'] = mb_substr($response, 0, 1);
        }
    } catch (\Exception $e) {
        $v['__ACADEMIC_ID__'] = '';
        $v['__STATUS__'] = 'ERROR';
    }
    echo ' ... ', $v['__STATUS__'], PHP_EOL;

    if (isset($settings['timeout']) && intval($settings['timeout']) > 0) {
        sleep($settings['timeout']);
    }
});

/**
 * Results on AMKA calls
 */
echo "Results:", PHP_EOL;
$stats = [
    'TOTAL' => count($csv_data),
    'ERROR' => 0,
    'UNKNOWN' => 0,
    'INVALID' => 0,
    'Π' => 0,
    'Μ' => 0,
    'Δ' => 0
];
$stat = array_walk($csv_data, function ($v, $k) use (&$stats) {
    $stats[$v['__STATUS__']]++;
});

echo "% results:", PHP_EOL,
    " Προπτυχιακοί: ", number_format(100 * $stats['Π'] / $stats['TOTAL'], 2), "% ({$stats['Π']})", PHP_EOL,
    "Μεταπτυχιακοί: ", number_format(100 * $stats['Μ'] / $stats['TOTAL'], 2), "% ({$stats['Μ']})", PHP_EOL,
    " Διδακτορικοί: ", number_format(100 * $stats['Δ'] / $stats['TOTAL'], 2), "% ({$stats['Δ']})", PHP_EOL,
    "         Άλλο: ", number_format(100 * ($stats['INVALID'] + $stats['ERROR'] + $stats['UNKNOWN']) / $stats['TOTAL'], 2), "% (" . ($stats['ERROR'] + $stats['UNKNOWN']) . ")", PHP_EOL;


/*************************************************
 * Get info based on academic id 
 */
echo PHP_EOL,
    "=========================================", PHP_EOL,
    "Άντληση στοιχείων από academic id", PHP_EOL,
    PHP_EOL;

$out_file_fp = fopen($output_file, 'a+');
fputcsv($out_file_fp, [
    'ΑΜΚΑ',
    'Σχολή Φοίτησης',
    'Υποχρεωτικά έτη φοίτησης',
    'Ακαδημαϊκό έτος εισαγωγής',
    'Προπτυχιακός ή Μεταπτυχιακός'
]);

$get = array_walk($csv_data, function (&$v, $k) use ($app, $amka_column, $settings, $out_file_fp) {
    if (!isset($v['__ACADEMIC_ID__'])) {
        echo "Δεν βρέθηκε academic id", PHP_EOL;
        $v['__STATUS_AID__'] = 'INVALID';
    }
    $academic_id = $v['__ACADEMIC_ID__'];
    if (preg_match('/^[0-9]{12}$/', $academic_id) !== 1) {
        echo "Το academic id {$academic_id} δεν είναι 12-ψήφιος αριθμός", PHP_EOL;
        $v['__STATUS_AID__'] = 'INVALID';
    }
    echo $academic_id;
    
    if (isset($v['__STATUS_AID__'])) {
        echo ' ... ', $v['__STATUS_AID__'], PHP_EOL;
        return;
    }
    $response = '';
    $min_years = 4;
    try {
        $response = $app->getStudentInformation($academic_id, $settings['auth_header_query']);
        // gets entryYear,studentshipType,departmentName,postGraduateProgram
        if (mb_substr($response, 0, 5) === 'ERROR') {
            $entry_year = -1;
            $studentship_type = '-';
            $department_name = '-';
            $min_years = -1;
            $v['__STATUS_AID__'] = 'ERROR';
        } else {
            $parts = explode(',', $response, 3);
            // var_export($response);
            // var_export($parts);
            if (count($parts) === 3) {
                $v['__STATUS_AID__'] = 'SUCCESS';
                $entry_year = $parts[0];
                $studentship_type = $parts[1];
                $first = mb_substr($studentship_type, 0, 1);
                $department_name = $parts[2];
                if (mb_substr($department_name, -1, 1, 'utf8') == ',') {
                    $department_name = mb_substr($department_name, 0, mb_strlen($department_name, 'utf8') - 1);
                }
                if ($first === 'Δ') { // Διδακτορικό
                    $min_years = 5;
                } elseif ($first === 'Μ') { // Μεταπτυχιακό
                    $min_years = 2;
                } elseif (((mb_strpos($department_name, 'ΙΑΤΡΙΚΗΣ') !== false) ||
                    (mb_strpos($department_name, 'ΜΗΧΑΝΙΚΩΝ') !== false))
                    && mb_strpos($department_name, 'ΠΑΝΕΠΙΣΤΗΜΙΟ') !== false) {
                    $min_years = 6;
                } elseif (mb_strpos($department_name, 'ΕΚΠΑΙΔΕΥΤΙΚΩΝ') !== false 
                    && mb_strpos($department_name, 'ΜΗΧΑΝΙΚΩΝ') !== false) {
                    $min_years = 5;
                } elseif (((mb_strpos($department_name, 'ΓΕΩΠΟΝΙΚΗ') !== false) ||
                        (mb_strpos($department_name, 'ΓΕΩΠΟΝΙΑΣ') !== false) ||
                        (mb_strpos($department_name, 'ΑΓΡΟΤΙΚΗΣ') !== false) ||
                        (mb_strpos($department_name, 'ΓΕΩΠΟΝΙΚΟ') !== false))
                    && mb_strpos($department_name, 'ΠΑΝΕΠΙΣΤΗΜΙΟ') !== false) {
                    $min_years = 5;
                } elseif (mb_strpos($department_name, 'ΠΟΛΥΤΕΧΝΕΙΟ') !== false
                    || mb_strpos($department_name, 'ΠΟΛΥΤΕΧΝΙΚΗ') !== false) {
                    $min_years = 5;
                } else {
                    $min_years = 4;
                }
            } else {
                $entry_year = -1;
                $studentship_type = '-';
                $department_name = '-';
                $min_years = -1;
                $v['__STATUS_AID__'] = 'ERROR';
            }
        }
        // echo var_export($response);
        fputcsv($out_file_fp, [
            $v[$amka_column],
            $department_name,
            $min_years,
            $entry_year,
            $studentship_type
        ]);
    } catch (\Exception $e) {
        $entry_year = -1;
        $studentship_type = '-';
        $department_name = '-';
        $v['__STATUS_AID__'] = 'ERROR';
        fputcsv($out_file_fp, [
            $v[$amka_column],
            department_name,
            $min_years,
            $entry_year,
            $studentship_type
        ]);
    }
    echo ' ... ', $v['__STATUS_AID__'], PHP_EOL;

    if (isset($settings['timeout']) && intval($settings['timeout']) > 0) {
        sleep($settings['timeout']);
    }
});

fclose($out_file_fp);



echo "Ολοκληρώθηκε.", PHP_EOL;
exit(0);

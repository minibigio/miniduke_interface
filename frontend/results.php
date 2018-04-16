<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 15/03/2018
 * Time: 17:04
 */

?>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

include 'commons/head.php';
include 'commons/header.php';
include '../vendor/autoload.php';

use Toml\Parser;
use Elasticsearch\ClientBuilder;

$must = [];
if (isset($_POST)) {
    $keys = array_keys($_POST);

    foreach ($keys as $k) {
        if ($_POST[$k] != '')
            $must[] = ['match' => [$k => $_POST[$k]]];
    }
}

$conf = Parser::fromFile('../config.toml');

$client = ClientBuilder::create();
$client->setHosts([$conf['elasticsearch']['host']]);
$client = $client->build();

$params = [
    'index' => $conf['elasticsearch']['index'],
    'type' => $conf['elasticsearch']['type'],
    'body' => [
        'query' => [
            'bool' => [
                'must' => [
                    $must
                ]
            ]
        ]
    ]
];

$response = $client->search($params);

function disp_list(array $array) {
    $echo = '<ul style="list-style: none; margin: 0; padding: 0;">';
    foreach ($array as $v) {
        $echo .= '<li>'.$v.'</li>';
    }
    $echo .= '</ul>';

    return $echo;
}

function disp_array(array $array, array $headers, array $excluded) {
    $echo = '<tr>';
    $vals = '';
    $source = $array['_source'];

    foreach ($headers as $h) {
        if (!in_array($h, $excluded)) {
            $vals .= '<td>';
            if (isset($source[$h]) && $source[$h] != null) {
                $v = $source[$h];

                if (sizeof($v) > 1) {
                    $vals .= disp_list($v);
                } else {
                    if (sizeof($v) > 0)
                        $vals .= $v[0];
                }
            }
            $vals .= '</td>';
        }
    }

    $echo .= $vals;
    $echo .= '<td style="text-align: center"><input type="checkbox" name="report[]" value="'.$array['_id'].'"></td>';
    $echo .= '</tr>';

    return $echo;
}

function getHeaders(array $hits) {
    $keys = [];
    foreach ($hits as $array) {
        foreach (array_keys($array['_source']) as $k) {
            if (!in_array($k, $keys))
                $keys[] = $k;
        }
    }

    return $keys;
}

function disp_headers(array $keys, array $excluded) {
    $echo = '<tr>';
    foreach ($keys as $key) {
        if (!in_array($key, $excluded))
            $echo .= '<th>'.$key.'</th>';
    }
    $echo .= '<th>Select</th>';
    $echo .= '</tr>';

    return $echo;
}
?>

<main role="main">
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Résultats</h3>
            <p></p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>
        </div>
    </div>

    <div class="container col-md-12">
        <form action="report.php" method="post">
            <?php
            $excluded_fields = ['@timestamp', 'thresholdMaybe', 'best_match', '@version'];
            if ($response != null) {
                ?>
                <p>Nombre de résultats: <?php echo $response['hits']['total']; ?></p>

                <?php
                if ($response['hits']['total'] > 0) {
                    ?>
                    <div class="table-responsive">
                        <table class="table results">
                            <?php
                            $hits = $response['hits']['hits'];
                            $headers = getHeaders($hits);
                            echo disp_headers($headers, $excluded_fields);
                            foreach ($hits as $hit) {
        //                        var_dump($hit);
                                echo disp_array($hit, $headers, $excluded_fields);
                            }
                            ?>
                        </table>
                    </div>
                    <?php
                }
            }
            ?>

            <button class="btn btn-info">Report</button>
        </form>
    </div>
</main>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

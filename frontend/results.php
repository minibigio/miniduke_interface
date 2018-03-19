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
    'index' => 'logstash_test',
    'type' => 'mdm',
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

function disp_array(array $array) {
    $echo = '<tr>';
    $vals = '';

    foreach ($array as $k => $v) {
        if ($k[0] != '@') {
            $vals .= '<td>';
            if (sizeof($v) > 1) {
                $vals .= disp_list($v);
            } else {
                $vals .= $v[0];
            }

            $vals .= '</td>';
        }
    }

    $echo .= $vals;
    $echo .= '</tr>';

    return $echo;
}

function disp_headers(array $array) {
    $keys = [];
    foreach (array_keys($array) as $k) {
        if ($k[0] != '@') {
            if (!in_array($k, $keys))
                $keys[] = $k;
        }
    }

    $echo = '<tr>';
    foreach ($keys as $key) {
        $echo .= '<th>'.$key.'</th>';
    }
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
        <?php
        if ($response != null) {
            ?>
            <p>Nombre de résultats: <?php echo $response['hits']['total']; ?></p>

            <?php
            if ($response['hits']['total'] > 0) {
                ?>
                <table class="table results">
                    <?php
                    $hits = $response['hits']['hits'];
                    echo disp_headers($hits[0]['_source']);
                    foreach ($hits as $hit) {
                        echo disp_array($hit['_source']);
                    }
                    ?>
                </table>
                <?php
            }
        }
        ?>
    </div>
</main>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

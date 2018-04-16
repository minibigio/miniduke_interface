<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 05/04/2018
 * Time: 18:30
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

$hits = [];
foreach ($_POST['report'] as $entry)
    $hits[] = $client->get(['index' => $conf['elasticsearch']['index'], 'type' => $conf['elasticsearch']['type'], 'id' => $entry]);

$form = json_decode(file_get_contents('form.json'), true);

$megaHit = [];
if ($hits > 0) {
    $megaHit = [];
    foreach ($hits as $hit)
        $megaHit = merge_array($megaHit, $hit['_source']);
}

/*function buildQuery(array $mapping, array $hits) {
    $must = [];
    $excludeFields = ['best_match', 'thresholdMaybe'];
    foreach ($mapping as $m) {
        if ($m != null) {

            foreach ($m['mappings'] as $k => $v) {
                $fields = array_keys($m['mappings'][$k]['properties']);

                foreach ($fields as $field) {
                    if ($field[0] != '@' && !in_array($field, $excludeFields) && isset($hits[$field]) && $hits[$field] != null) {

                        if (is_array($hits[$field])) {
                            echo 'ok';
                            $should = [];
                            foreach ($hits[$field] as $sub) {
                                $should[] = ['match' => [$field => $sub]];
                            }
                            $must[] = ['bool' => ['should' => $should]];
                        }
                        else
                            $must[] = ['match' => [$field => $hits[$field]]];
                    }
                }
            }
        }
    }

    return ['query' => ['bool' => ['must' => $must]]];
}*/

function get_data($client, array $unique, String $index, array $hits) {
    $source = [];

    foreach ($unique as $u) {
        $terms = $hits[$u];
        $query = [
                    'query' => [
                        'bool' => [
                            'must' => [
                                'match_all' => (object)[]
                            ],
                            'filter' => ['terms' => [$u => $terms]]
                        ]
                    ]
                ];
        $response = $client->search(['index' => $index, 'body' => $query]);

        if ($response['hits']['total'] > 0)
            foreach ($response['hits']['hits'] as $hit)
                $source = merge_array($source, $hit['_source']);
    }

    return $source;
}

$indices = $client->indices()->getMapping();
$response = [];
foreach ($indices as $index => $other) {
//    $query = buildQuery($client->indices()->getMapping(['index' => 'mairie']), $megaHit);
//    $response[] = [$index => $client->search(['index' => $index, 'body' => $query])];
    if ($conf['elasticsearch']['index'] != $index)
        $response[] = [$index => get_data($client, $conf['search']['unique'], $index, $megaHit)];
}


function getNiceLabel($ugly, $form) {
    foreach ($form as $item) {
        if ($item['name'] == $ugly)
            return $item['label'];
    }
    return $ugly;
}

function merge_array($mega, $small) {
    foreach ($small as $k => $a) {
        if (!isset($mega[$k])) {
            $mega[$k] = [];
        }


        if (is_array($a)) {
            foreach ($a as $v) {
                if (!in_array($v, $mega[$k]))
                    $mega[$k][] = $v;
            }
        }
        else {
            if (!in_array($a, $mega[$k]))
                $mega[$k][] = $a;
        }
    }

    return $mega;
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
        <div class="col-md-6 offset-md-3" id="report-container">
            <h4>Rapport complet</h4>

            <table class="table">
                <?php
                $excluded_fields = ['@timestamp', 'threshold_maybe', 'best_match', '@version'];
                foreach ($megaHit as $k => $subHit) {
                    if (!in_array($k, $excluded_fields)) {
                        echo '<tr><td>' . getNiceLabel($k, $form) . '</td><td>';

                        if (is_array($subHit)) {
                            foreach ($subHit as $h)
                                echo $h . '<br>';
                        } else
                            echo $subHit;

                        echo '</td></tr>';
                    }
                }
                ?>
            </table>

            <?php
            foreach ($response as $index) {
                echo '<div class="table-subreport">';
                foreach ($index as $name => $hits) {
                    echo '<h3>' . $name . '</h3>';



                    echo '<table class="table">';
                    if (sizeof($hits) > 0) {
                        foreach ($hits as $field => $values) {
                            if (!in_array($field, $excluded_fields)) {
                                echo '<tr><td>'.getNiceLabel($field, $form).'</td><td>';

                                // If it's a raw index, the values are strings and not arrays
                                if (!is_array($values)) {
                                    echo $values;
                                } else {

                                    foreach ($values as $value) {
                                        echo $value . '<br>';
                                    }
                                }

                                echo '</td></tr>';
                            }


                        }
                    }
                    else
                        echo 'Rien dans cet index';

                    echo '</table>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>
</main>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

}
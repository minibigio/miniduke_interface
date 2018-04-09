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

function getNiceLabel($ugly, $form) {
    foreach ($form as $item) {
        if ($item['name'] == $ugly)
            return $item['label'];
    }
    return $ugly;
}

function merge_array($mega, $small) {
    foreach ($small as $k => $a) {
        if (is_array($a)) {
            foreach ($a as $v) {
                if (!isset($mega[$k]))
                    $mega[$k] = $v;
                else
                    $mega[$k] .= '<br>'.$v;
            }
        }
        else
            $mega[$k] = $a;
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

        <?php
        if ($hits > 0) {
            $megaHit = [];

            foreach ($hits as $hit)
                $megaHit = merge_array($megaHit, $hit['_source']);

        }
        ?>

        <div class="col-md-6 offset-md-3">
            <h4>Rapport complet</h4>

            <table class="table">
                <?php
                foreach ($megaHit as $k => $subHit) {
                    echo '<tr><td>'.getNiceLabel($k, $form).'</td><td>';

                    if (is_array($subHit)) {
                        foreach ($subHit as $h)
                            echo $h . '<br>';
                    }
                    else
                        echo $subHit;

                    echo '</td></tr>';
                }
                ?>
            </table>
        </div>
    </div>
</main>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 05/03/2018
 * Time: 15:01
 */

?>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

include 'commons/head.php';
include 'commons/header.php';
include 'vendor/autoload.php';

use Toml\Parser;
?>

<main role="main">
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Configurations</h3>
            <p>Pour assurer le fonctionnement de tous les services, il faut configurer quelques paramètres.</p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>
        </div>
    </div>

    <div class="container">
        <?php
        $conf = Parser::fromFile('config.toml');

        var_dump($conf);

        ?>
    </div>
</main>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

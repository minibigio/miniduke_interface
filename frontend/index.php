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
include 'FormItem.php';

use Toml\Parser;
?>

<main role="main">
    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Accueil</h3>
            <p>Assurez vous que vous avez bien les droits pour obtenir les informations sur quelqu'un.</p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>
        </div>
    </div>

    <div class="container">
        <?php
        $conf = Parser::fromFile('../config.toml');

       /* $elasticHost = $conf['elasticsearch']['host'];
        $index = $conf['elasticsearch']['index'];
        $type = $conf['elasticsearch']['type'];
        $elasticJsonMapping = null;
        if ($fContent = file_get_contents('http://'.$elasticHost.'/'.$index.'/_mapping/'.$type)) {
            $elasticJsonContent = $fContent;
            $elasticJson = json_decode($elasticJsonContent);

            $elasticMapping = $elasticJson->$index->mappings->$type->properties;
            $elasticProperties = [];
            foreach ($elasticMapping as $key => $value) {
                if ($key[0] != '@')
                    $elasticProperties[] = $key;
            }
        }*/
        ?>

        <form action="results.php" method="post">
            <div class="block-form col-md-7">
                <?php
                $form = file_get_contents('form.json');
                $formProp = json_decode($form, true);
                foreach ($formProp as $prop) {
                    $formItem = new FormItem($prop);
                    ?>
                       <!-- <div class="input-group-prepend">
                            <label class="input-group-text" for="inputGroupSelect02"><?php /*echo $prop; */?></label>
                        </div>
                        -->
                        <?php
                        echo $formItem->jsonToHtml();
                        ?>
                    <?php
                }
                ?>
            </div>

            <button class="btn btn-info">Recherche</button>
        </form>
    </div>
</main>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

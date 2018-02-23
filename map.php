<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 06/02/2018
 * Time: 14:43
 */
?>

<?php
include 'vendor/autoload.php';
use Yosymfony\Toml\Toml;

$topics = [];

if ($handle = opendir('./topics_conf/')) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $topics[] = explode('.', $entry)[0]; // Get the name of the file
        }
    }
    closedir($handle);
}
?>


<?php

$json = [];

if (isset($_GET['topic'])) {
    $topic = $_GET['topic'];

    if (file_exists('./topics_data/' . $_GET['topic'] . '.json')) {
        $topic_data_file = fopen('./topics_data/' . $_GET['topic'] . '.json', 'r');

        $file_size = filesize('./topics_data/' . $_GET['topic'] . '.json');

        if ($file_size > 0) {
            // Open file and return array
            $json = json_decode(fread($topic_data_file, $file_size), true);
        }
    }
}
var_dump(isset($_GET['index']));
if (isset($_GET['index']) && in_array($_GET['index'], $topics)) {
    var_dump("ok");
    $configuration = Toml::ParseFile('config.toml');


    $pdo = new PDO($configuration["database"]["connection"]);

    $logstashInstancesSql= $pdo->prepare('select count(*) from logstash_instances where name=?');
    $logstashInstancesSql->execute([$_GET['index']]);
    $logstashInstancesCount = $logstashInstancesSql->fetch();



    if ($logstashInstancesCount[0] == 0) {
        if ($configuration["launcher"]["distant"]) {
            echo 'ssh '.$configuration["launcher"]["distant"]["user"].'@'.$configuration["launcher"]["distant"]["host"].' 
            "sh '.$configuration["launcher"]["distant"]["path"].'start_logstash.sh '.$_GET['index'].'"';
            exec('ssh '.$configuration["launcher"]["distant"]["user"].'@'.$configuration["launcher"]["distant"]["host"].' 
            "sh '.$configuration["launcher"]["distant"]["path"].'start_logstash.sh '.$_GET['index'].'"', $pid);
        }
        else {
            echo 'sh ' . $configuration["launcher"]["distant"]["path"] . 'start_logstash.sh ' . $_GET['index'];
            exec('sh ' . $configuration["launcher"]["distant"]["path"] . 'start_logstash.sh ' . $_GET['index'], $pid);

        }

        $logstashInstancesInsert = $pdo->prepare('insert into logstash_instances(name, pid) values (?, ?)');
        $logstashInstancesInsert->execute([$_GET['index'], $pid]);
    }
}



$string = file_get_contents("map_options.json");
$map_options = json_decode($string, true);

?>

<?php
include 'commons/head.php';
include 'commons/header.php';
?>

<?php


?>
<main role="main">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Mapping</h3>
            <p>Avant de lancer l'importation, vous devez mapper les attributs des vues de la file d'attente.</p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>
        </div>
    </div>

    <div class="container">
        <form action="fetch.php" method="post">

                <?php
                if (!empty($json)) {
                    $i = 1;
                    foreach (array_keys($json) as $key) {
                        ?>
                        <h3>Clé <em><?php echo $key; ?></em></h3>
                        <input type="hidden" name="key[]" value="<?php echo $key; ?>">
                        <div class="block-form" data-id="<?php echo $i; ?>">
                            <div class="form-row">
                                <div class="input-group mb-3 col-md-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="inputGroupSelect01">Préselection</label>
                                    </div>

                                    <select name="pre" class="custom-select" id="inputGroupSelect01">
                                        <option></option>
                                        <?php foreach ($map_options as $op) { ?>
                                            <option value="<?php echo $op["option"]; ?>"
                                                    data-low="<?php echo $op["weight"][0]; ?>"
                                                    data-high="<?php echo $op["weight"][1]; ?>"
                                                    data-comparator="<?php echo $op["comparator"]; ?>">
                                                <?php echo $op["option"]; ?>
                                            </option>
                                        <?php } ?>
                                        <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <div class="input-group mb-3 col-md-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="">Nouvelle clé</span>
                                    </div>
                                    <input name="new_key[]" class="form-control">
                                </div>
                            </div>

                            <div class="form-row tune" data-id="<?php echo $i; ?>">

                                <div class="input-group mb-3 col-md-3">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="inputGroupSelect02">Comparateur</label>
                                    </div>

                                    <select name="comparator[<?php echo $i; ?>]" class="custom-select"
                                            id="inputGroupSelect01">
                                        <?php
                                        $displayed = [];
                                        foreach ($map_options as $op) {
                                            if (!in_array($op['comparator'], $displayed))
                                                echo '<option value="' . $op['comparator'] . '">' . $op['comparator_name'] . '</option>';

                                            $displayed[] = $op['comparator'];
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="input-group mb-3 col-md-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="">Minimal</span>
                                    </div>
                                    <input type="number" class="form-control" name="low[<?php echo $i; ?>]" step="0.01"
                                           min="0" max="1">
                                </div>

                                <div class="input-group mb-3 col-md-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="">Maximum</span>
                                    </div>
                                    <input type="number" class="form-control" name="high[<?php echo $i; ?>]" step="0.01"
                                           min="0" max="1">
                                </div>

                            </div>
                        </div>

                        <hr>


                        <?php
                        $i++;
                    }
                    ?>
            <div class="form-group col-md-5">
                <label for="threshold">Seuil général</label>
                <input type="number" name="threshold" step="0.01" min="0" max="1" class="form-control" id="threshold">
                <small id="thresholdHelp" class="form-text text-muted">Les ressemblance au dessus du seuil général seront réconciliés.</small>
            </div>

            <input type="hidden" name="make_conf_file" value="1">
            <input type="hidden" name="topic" value="<?php echo $_GET['topic']; ?>">

            <button class="btn btn-primary btn-lg">Make conf file</button>
                <?php
                    $pdo = new PDO('sqlite:miniduke.db');
                    $logtsashInstanceSql = $pdo->prepare('select count(*) from logstash_instances where name=?');
                    $logtsashInstanceSql->execute([$_GET['topic']]);
                    $logstashInstance = $logtsashInstanceSql->fetch();

                    if ($logstashInstance[0] == 0) {
                        ?>
                        <a href="map.php?index=<?php echo $_GET['topic']; ?>">
                            <button type="button" class="btn btn-success btn-lg">Index data</button>
                        </a>
                        <?php
                    }
                        ?>
            <?php
            }
                else
                    echo '<div><p>Aucune clé n\'apparait ? N\'oubliez pas de pull le schéma</p>';
                ?>



        </form>



    </div> <!-- /container -->

</main>

<?php
include 'commons/foot.php';
include 'commons/footer.php';
?>


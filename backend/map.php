<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 06/02/2018
 * Time: 14:43
 */
?>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
include 'commons/head.php';
include '../vendor/autoload.php';
use Toml\Parser;
use Elasticsearch\ClientBuilder;

$topics = [];

if ($handle = opendir(ini_get('include_path').'/topics_conf/')) {
    while (false !== ($entry = readdir($handle))) {
        if ($entry != "." && $entry != "..") {
            $fileExplode = explode('.', $entry);

            if (!isset($fileExplode[2])) // If there isn't a 'json' at the end
                $topics[] = $fileExplode[0]; // Get the name of the file
        }
    }
    closedir($handle);
}

$configuration = Parser::fromFile(ini_get('include_path').'/config.toml');
$json = [];

if (isset($_GET['topic'])) {
    $topic = $_GET['topic'];

    if (file_exists(ini_get('include_path').'/topics_data/' . $_GET['topic'] . '.json')) {
        $topic_data_file = fopen(ini_get('include_path').'/topics_data/' . $_GET['topic'] . '.json', 'r');
        $file_size = filesize(ini_get('include_path').'/topics_data/' . $_GET['topic'] . '.json');

        if ($file_size > 0) {
            // Open file and return array
            $json = json_decode(fread($topic_data_file, $file_size), true);
        }
    }
}



if (isset($_GET['index']) && in_array($_GET['topic'], $topics)) {

    $pdo = new PDO($configuration["database"]["connection"]);

    if ($configuration["launcher"]["distant_launcher"]) {
        /*var_dump(ssh2_auth_pubkey_file($remote_conn, $configuration["launcher"]["distant"]["user"],
                $configuration["launcher"]["distant"]["pub_key_path"],
                $configuration["launcher"]["distant"]["private_key_path"],
                $configuration["launcher"]["distant"]["passphrase"]));*/
        $remote_conn = ssh2_connect($configuration["launcher"]["distant"]["host"], 22);
        $auth = ssh2_auth_password($remote_conn, $configuration["launcher"]["distant"]["user"], $configuration["launcher"]["distant"]["pwd"]);
    }

    if ($_GET['index'] == 'stopAll' || $_GET['index'] == 'stopMdm' || $_GET['index'] == 'stopRaw') {
        $stopAll = ($_GET['index'] == 'stopAll');
        $stopMdm = ($_GET['index'] == 'stopMdm');
        $stopRaw = ($_GET['index'] == 'stopRaw');

        $logstashInstancesSql= $pdo->prepare('select * from logstash_instances where name=?');
        $logstashInstancesCountSql= $pdo->prepare('select count(*) from logstash_instances where name=?');
        $deleteLogstashInstancesSql= $pdo->prepare('delete from logstash_instances where name=?');

        if ($configuration["launcher"]["distant_launcher"]) {
            if ($auth) {
                $logstashInstancesCountSql->execute([$_GET['topic']]);
                $logstashInstanceCount = $logstashInstancesCountSql->fetch();

                if ($logstashInstanceCount[0] > 0 && ($stopAll || $stopMdm)) {
                    $logstashInstancesSql->execute([$_GET['topic']]);
                    $logstashInstance = $logstashInstancesSql->fetch();
                    ssh2_exec($remote_conn, 'sh '.$configuration["launcher"]["distant"]["path"].'stop_logstash.sh '.$logstashInstance['pid']);

                    $deleteLogstashInstancesSql->execute([$_GET['topic']]);
                }

                $logstashInstancesCountSql->execute([$_GET['topic'].'_raw']);
                $logstashInstanceCount = $logstashInstancesCountSql->fetch();

                if ($logstashInstanceCount[0] > 0 && ($stopAll || $stopRaw)) {
                    $logstashInstancesSql->execute([$_GET['topic'] . '_raw']);
                    $logstashInstance = $logstashInstancesSql->fetch();
                    ssh2_exec($remote_conn, 'sh ' . $configuration["launcher"]["distant"]["path"] . 'stop_logstash.sh ' . $logstashInstance['pid']);

                    $deleteLogstashInstancesSql->execute([$_GET['topic'].'_raw']);
                }
            }
        }

        else {
            $logstashInstancesCountSql->execute([$_GET['topic']]);
            $logstashInstanceCount = $logstashInstancesCountSql->fetch();

            if ($logstashInstanceCount[0] > 0 && ($stopAll || $stopMdm)) {
                $logstashInstancesSql->execute([$_GET['topic']]);
                $logstashInstance = $logstashInstancesSql->fetch();
                exec('sh ' . $configuration["launcher"]["local"]["path"] . 'stop_logstash.sh ' . $logstashInstance['pid'], $pid);

                $deleteLogstashInstancesSql->execute([$_GET['topic']]);
            }

            $logstashInstancesCountSql->execute([$_GET['topic'].'_raw']);
            $logstashInstanceCount = $logstashInstancesCountSql->fetch();
            if ($logstashInstanceCount[0] > 0 && ($stopAll || $stopRaw)) {
                $logstashInstancesSql->execute([$_GET['topic'] . '_raw']);
                $logstashInstance = $logstashInstancesSql->fetch();
                exec('sh ' . $configuration["launcher"]["local"]["path"] . 'stop_logstash.sh ' . $logstashInstance['pid'], $pid);

                $deleteLogstashInstancesSql->execute([$_GET['topic'].'_raw']);
            }
        }
    }

    $logstashInstancesSql= $pdo->prepare('select count(*) from logstash_instances where name=?');

    $logstashInstancesSql->execute([$_GET['topic']]);
    $logstashInstancesCount = $logstashInstancesSql->fetch();

    $logstashInstancesSql->execute([$_GET['topic'].'_raw']);
    $logstashInstancesCount = $logstashInstancesSql->fetch();

    if ($_GET['index'] == 'startAll' || $_GET['index'] == 'startMdm' || $_GET['index'] == 'startRaw') {
        $startAll = ($_GET['index'] == 'startAll');
        $startMdm = ($_GET['index'] == 'startMdm');
        $startRaw = ($_GET['index'] == 'startRaw');

        $logstashInstancesSql= $pdo->prepare('select count(*) from logstash_instances where name=?');

        $logstashInstancesSql->execute([$_GET['topic']]);
        $countTopicLog = $logstashInstancesSql->fetch();

        $logstashInstancesSql->execute([$_GET['topic'].'_raw']);
        $countRawTopicLog = $logstashInstancesSql->fetch();

        if ($configuration["launcher"]["distant_launcher"]) {
            if ($auth) {
                if (($startAll || $startMdm) && $countTopicLog[0] == 0) {
                    $pid1Stream = ssh2_exec($remote_conn, 'sh ' . $configuration["launcher"]["distant"]["path"] . 'start_logstash.sh ' . $_GET['topic']);
                    stream_set_blocking($pid1Stream, true);
                    $pid1 = fgets($pid1Stream);
                }

                if (($startAll || $startRaw) && $countRawTopicLog[0] == 0) {
                    $pidRawStream = ssh2_exec($remote_conn, 'sh ' . $configuration["launcher"]["distant"]["path"] . 'start_logstash.sh ' . $_GET['topic'] . '_raw');
                    stream_set_blocking($pidRawStream, true);
                    $pidRaw = fgets($pidRawStream);
                }

                $remote_conn = null;
            }
        }
        else {
            if ($startAll || $startMdm)
                exec('sh ' . $configuration["launcher"]["local"]["path"] . 'start_logstash.sh ' . $_GET['topic'], $pid1Stream);

            if ($startAll || $startRaw)
                exec('sh ' . $configuration["launcher"]["local"]["path"] . 'start_logstash.sh ' . $_GET['topic'].'_raw', $pidRaw);
        }

        $logstashInstancesInsert = $pdo->prepare('INSERT INTO logstash_instances VALUES (?, ?)');

        if (($startAll || $startMdm) && (int)$pid1 > 0)
            $logstashInstancesInsert->execute([$_GET['topic'], (int)$pid1]);

        if (($startAll || $startRaw) && (int)$pidRaw > 0)
            $logstashInstancesInsert->execute([$_GET['topic'].'_raw', (int)$pidRaw]);
    }

    header('Location: map.php?topic='.$_GET['topic']);
}

$string = file_get_contents(ini_get('include_path')."/map_options.json");
$map_options = json_decode($string, true);

?>

<?php
include 'commons/head.php';
include 'commons/header.php';
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
        <div class="row">
            <div class="col-md-7">
                <h3><?php echo ucfirst($_GET['topic']); ?></h3>
                <form action="fetch.php" method="post">

                    <?php
                    if (!empty($json)) {
                        $i = 1;
                        foreach (array_keys($json) as $key) {
                            ?>
                            <h4>Clé <em><?php echo $key; ?></em></h4>

                            <input type="hidden" name="key[]" value="<?php echo $key; ?>">
                            <div class="block-form" data-id="<?php echo $i; ?>">
                                <div class="form-group row">
                                    <div class="col-sm-2">Filtre</div>
                                    <div class="col-sm-10">
                                        <div class="form-check">
                                            <input type="checkbox" name="<?php echo 'filter_'.$key; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="input-group mb-3 col-md-5">
                                        <div class="input-group-prepend">
                                            <label class="input-group-text" for="inputGroupSelect01">Préselection</label>
                                        </div>

                                        <select name="pre" class="custom-select preselect" id="inputGroupSelect01" data-id="<?php echo $i; ?>">
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

                                    <div class="input-group mb-3 col-md-7">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="">Nouvelle clé</span>
                                        </div>
                                        <input name="new_key[]" class="form-control" required>
                                    </div>
                                </div>

                                <div class="form-row tune">

                                    <div class="input-group col-md-5">
                                        <div class="input-group-prepend">
                                            <label class="input-group-text" for="inputGroupSelect02">Comparateur</label>
                                        </div>

                                        <select name="comparator[<?php echo $i; ?>]" class="custom-select"
                                                id="inputGroupSelect01" data-id="<?php echo $i; ?>" required>
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
                                    <div class="input-group col-md-7">
                                        <div class="row">
                                            <div class="input-group col-md-6">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="">Minimal</span>
                                                </div>
                                                <input type="number" class="form-control" name="low[<?php echo $i; ?>]" step="0.01"
                                                       min="0" max="1" data-id="<?php echo $i; ?>" required>
                                            </div>

                                            <div class="input-group col-md-6">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="">Maximum</span>
                                                </div>
                                                <input type="number" class="form-control" name="high[<?php echo $i; ?>]" step="0.01"
                                                       min="0" max="1" data-id="<?php echo $i; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <?php
                            $i++;
                        }
                        ?>
                        <div class="form-group col-md-8">
                            <label for="thresholdMaybe">Seuil suspicieux</label>
                            <input type="number" name="thresholdMaybe" step="0.01" min="0" max="1" class="form-control" id="thresholdMaybe" value="0.88" required>
                            <small id="thresholdHelp" class="form-text text-muted">Les ressemblances au dessus du seuil suspicieux devront être vérifiés.</small>
                        </div>

                        <div class="form-group col-md-8">
                            <label for="threshold">Seuil général</label>
                            <input type="number" name="threshold" step="0.01" min="0" max="1" class="form-control" id="threshold" value="0.91" required>
                            <small id="thresholdHelp" class="form-text text-muted">Les ressemblances au dessus du seuil général seront réconciliés.</small>
                        </div>

                        <input type="hidden" name="make_conf_file" value="1">
                        <input type="hidden" name="topic" value="<?php echo $_GET['topic']; ?>">

                        <p><strong>Pensez aux filtres !</strong></p>
                        <p><button class="btn btn-primary btn-lg">Make conf file</button></p>
                        <p>
                        <?php
                        $pdo = new PDO('sqlite:'.ini_get('include_path').'/miniduke.db');
                        $logtsashInstanceSql = $pdo->prepare('select count(*) from logstash_instances where name=?');
                        $logtsashInstanceSql->execute([$_GET['topic']]);
                        $logstashInstanceTopic = $logtsashInstanceSql->fetch();

                        $logtsashInstanceSql->execute([$_GET['topic'].'_raw']);
                        $logstashInstanceTopicRaw = $logtsashInstanceSql->fetch();

                        if ($logstashInstanceTopic[0] == 0 && $logstashInstanceTopicRaw[0] == 0) {
                            ?>
                            <a href="map.php?index=startAll&topic=<?php echo $_GET['topic']; ?>">
                                <button type="button" class="btn btn-success btn-lg">Index all</button>
                            </a>
                            <?php
                        }
                        if ($logstashInstanceTopic[0] == 0) {
                            ?>
                            <a href="map.php?index=startMdm&topic=<?php echo $_GET['topic']; ?>">
                                <button type="button" class="btn btn-success btn-lg">Index MDM</button>
                            </a>
                            <?php
                        }
                        if ($logstashInstanceTopicRaw[0] == 0) {
                            ?>
                            <a href="map.php?index=startRaw&topic=<?php echo $_GET['topic']; ?>">
                                <button type="button" class="btn btn-success btn-lg">Index raw</button>
                            </a>
                            <?php
                        }

                        if ($logstashInstanceTopic[0] > 0 && $logstashInstanceTopicRaw[0] > 0) {
                            ?>
                            <a href="map.php?index=stopAll&topic=<?php echo $_GET['topic']; ?>">
                                <button type="button" class="btn btn-danger btn-lg">Stop all index</button>
                            </a>
                            <?php
                        }

                        if ($logstashInstanceTopic[0] > 0) {
                            ?>
                            <a href="map.php?index=stopMdm&topic=<?php echo $_GET['topic']; ?>">
                                <button type="button" class="btn btn-danger btn-lg">Stop MDM index</button>
                            </a>
                            <?php
                        }
                        if ($logstashInstanceTopicRaw[0] > 0) {
                            ?>
                            <a href="map.php?index=stopRaw&topic=<?php echo $_GET['topic']; ?>">
                                <button type="button" class="btn btn-danger btn-lg">Stop raw index</button>
                            </a>
                            <?php
                        }
                        ?>
                        </p>
                    <?php
                    }
                    else
                        echo '<div><p>Aucune clé n\'apparait ? N\'oubliez pas de pull le schéma</p>';
                    ?>
                </form>
            </div>

            <div class="col-md-5">
                <h3>Map actuel</h3>
                <table class="table">
                    <tr><th>Ancienne clé</th><th>Nouvelle clé</th><th>Comparateur</th><th>Pondération Min</th><th>Pondération Max</th></tr>
                    <?php
                    function disp_weight(array $w) {
                        return $w[0].'</td><td>'.$w[1];
                    }
                    function get_pretty_name_comp(String $comp, array $options) {
                        foreach ($options as $o) {
                            if ($comp == $o['comparator'])
                                return $o['comparator_name'];
                        }
                        return null;
                    }

                    $threshold = null;
                    $thresholdMaybe = null;

                    if (!empty($json)) {
                        if (file_exists(ini_get('include_path').'/topics_conf/'.$_GET['topic'].'.conf.json')) {
                            $fopen = fopen(ini_get('include_path').'/topics_conf/'.$_GET['topic'].'.conf.json', 'r');
                            $map_json = json_decode(fread($fopen, filesize(ini_get('include_path').'/topics_conf/'.$_GET['topic'].'.conf.json')), true);

                            $threshold = $map_json['threshold'];
                            $thresholdMaybe = $map_json['thresholdMaybe'];

                            $fopen = fopen(ini_get('include_path').'/map_options.json', 'r');
                            $map_options = json_decode(fread($fopen, filesize(ini_get('include_path').'/map_options.json')), true);

                            $i = 0;
                            foreach (array_keys($json) as $key) {
                                echo '<tr><td>' . $key.'</td><td>'.$map_json['new_keys'][$i].'</td><td>'.get_pretty_name_comp($map_json['comparators'][$i], $map_options).'</td><td>'.disp_weight($map_json['weights'][$i]).'</td></tr>';
                                $i++;
                            }
                        }
                    }
                    ?>
                </table>
                <?php echo 'Seuil suspicieux: <strong>'.$thresholdMaybe.'</strong>'; ?>
                <?php echo 'Seuil général: <strong>'.$threshold.'</strong>'; ?>

                <hr>

                <h3>Elasticsearch details</h3>
                <?php
                $client = ClientBuilder::create();
                $client->setHosts([$configuration['elasticsearch']['host']]);
                $client = $client->build();

                $response = $client->count(['index' => $_GET['topic'].'_raw',
                                            'type' => 'raw',
                                            'body' => ['query' => ['match_all' => (object)[]]]
                                            ]);
                ?>

                <p>Nombre de données brutes dans <em><?php echo $_GET['topic']; ?>_raw</em>: <?php echo $response['count']; ?></p>
            </div>
        </div>
    </div> <!-- /container -->
</main>

<?php
include 'commons/foot.php';
include 'commons/footer.php';
?>


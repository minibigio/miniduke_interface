<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 27/02/2018
 * Time: 16:09
 */
?>

<?php
include 'commons/head.php';
include 'commons/header.php';

include 'vendor/autoload.php';
use Toml\Parser;
?>
<main role="main">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Activités</h3>
            <p>Afin de garantir le bon fonctionement des processus servant à l'indexation, tout est reporté ici</p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>
        </div>
    </div>

    <div class="container">
        <?php
        $conf = Parser::fromFile('config.toml');

        // Elasticsearch infos
        //region Elastic

        $elasticHost = $conf['elasticsearch']['host'];
        $elasticJson = null;
        if ($fContent = file_get_contents('http://' . $elasticHost)) {
            $elasticJsonContent = $fContent;
            $elasticJson = json_decode($elasticJsonContent);
        }

        $elasticJsonHealth = null;
        if ($fContent = file_get_contents('http://'.$elasticHost.'/_cluster/health')) {
            $elasticJsonContent = $fContent;
            $elasticJsonHealth = json_decode($elasticJsonContent);
        }

        $elasticVersion = '';
        $elasticClusterName = '';
        $elasticStatus = '';
        if ($elasticJson != null && !empty($elasticJson) && $elasticJsonHealth != null && !empty($elasticJsonHealth)) {
            $elasticVersion = $elasticJson->version->number;
            $elasticClusterName = $elasticJson->cluster_name;
            $elasticStatus = $elasticJsonHealth->status;
        }

        //endregion

        // Kafka infos
        //region Kafka
        $kafkaHosts = $conf['kafka']['hosts'];
        $kafkaAllGood = true;
        $kafkaAllBad = true;
        $kafkaHostsHealth = [];
        foreach ($kafkaHosts as $kHost) {
            $out = null;
            $host = explode(':', $kHost)[0];
            $port = explode(':', $kHost)[1];

            exec('netstat -an | grep '.$host.'.'.$port.' | grep LISTEN', $out);

            $kafkaHostsHealth[] = [$kHost => (!empty($out))];
            if (empty($out))
                $kafkaAllGood = false;
            if (!empty($out))
                $kafkaAllBad = false;
        }
        //endregion

        // Logstash instances infos
        //region Logstash
        $pdo = new PDO($conf["database"]["connection"]);

        $logstashInstancesSql= $pdo->prepare('select * from logstash_instances');
        $logstashInstancesSql->execute();
        $logstashInstances = $logstashInstancesSql->fetchAll();

        $logstashInstancesHealth = [];
        $logstashInstancesAllGood = true;
        $logstashInstancesAllBad = true;
        foreach ($logstashInstances as $lInstance) {
            $out = null;

            exec('ps -elf | grep '.$lInstance['pid'], $out);

            $logstashInstancesHealth[] = ['name' => $lInstance['name'], 'pid' => $lInstance['pid'], 'status' => (sizeof($out) == 2)];
            if (sizeof($out) == 2) {
                $logstashInstancesAllBad = false;
            }
            else {
                $logstashInstancesAllGood = false;
            }
        }
        if (sizeof($logstashInstances) == 0) {
            $logstashInstancesAllGood = true;
            $logstashInstancesAllBad = false;
        }
        //endregion

        // Launchers
        //region launchers
        $launcherPath = null;
        $launcherUser = null;
        $launcherHost = null;

        $launcherFound = false;
        if ($conf['launcher']['distant_launcher'] == true) {
            // @TODO : Find on a distant server
            $out = null;
            $launcherUser = $conf['launcher']['distant']['user'];
            $launcherHost = $conf['launcher']['distant']['host'];
            $launcherPath = $conf['launcher']['distant']['path'];

            exec('ping '.$launcherHost, $out);
            var_dump($out);

            $out = null;
            exec('ssh '.$conf["launcher"]["distant"]["user"].'@'.$conf["launcher"]["distant"]["host"].'
                         "[[ -f '.$conf["launcher"]["distant"]["path"].'start_logstash.sh ]] && echo "Found" || echo "Not found"', $out);

            $launcherFound = ($out[0] == 'Found');
            var_dump($out);

        }
        else {
            $out = null;
            exec('[[ -f '.$conf["launcher"]["local"]["path"].'start_logstash.sh ]] && echo "Found" || echo "Not found"', $out);

            $launcherPath = $conf["launcher"]["local"]["path"];
            $launcherFound = ($out[0] == 'Found');
        }
        //endregion
        ?>



        <!-- Elastic -->
        <div class="row status">
            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white <?php echo (in_array($elasticStatus, ['yellow', 'green'])) ? 'bg-success':'bg-danger'; ?>">
                        <div class="text-center">Elasticsearch</div>

                        <div class="text-center check"><i class="material-icons"><?php echo (in_array($elasticStatus, ['yellow', 'green'])) ? 'check_circle':'error'; ?></i></div>
                    </div>
                    <!--<a href="#">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>

                            <div class="clearfix"></div>
                        </div>
                    </a>-->
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white bg-info">
                        <p>Status: <?php echo ucfirst($elasticStatus); ?><br>
                           Host: <?php echo $elasticHost; ?><br>
                           Version: <?php echo $elasticVersion; ?></p>

                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->

        <!-- Kafka -->
        <div class="row status">
            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white <?php
                    if ($kafkaAllGood)
                        echo 'bg-success';
                    elseif ($kafkaAllBad)
                        echo 'bg-danger';
                    else
                        echo 'bg-warning';
                    ?>">
                        <div class="text-center">Kafka</div>

                        <div class="text-center check"><i class="material-icons">
                                <?php
                                if ($kafkaAllGood)
                                    echo 'check_circle';
                                elseif ($kafkaAllBad)
                                    echo 'error';
                                else
                                    echo 'warning';
                                ?></i></div>
                    </div>
                    <!--<a href="#">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>

                            <div class="clearfix"></div>
                        </div>
                    </a>-->
                </div>
            </div>

            <?php
            for ($i=0; $i<sizeof($kafkaHostsHealth); $i++) {
                foreach ($kafkaHostsHealth[$i] as $host => $health) {
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="panel">
                            <div class="panel-heading text-white bg-info">
                                <p>Host: <?php echo $host; ?><br>
                                    Status: <?php echo ($health) ? 'On':'Off'; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        <!-- /.row -->

        <!-- Logstash -->
        <div class="row status">
            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white <?php
                    if ($logstashInstancesAllGood)
                        echo 'bg-success';
                    elseif ($logstashInstancesAllBad)
                        echo 'bg-danger';
                    else
                        echo 'bg-warning';
                    ?>">
                        <div class="text-center">Logstash instances</div>

                        <div class="text-center check"><i class="material-icons">
                                <?php
                                if ($logstashInstancesAllGood)
                                    echo 'check_circle';
                                elseif ($logstashInstancesAllBad)
                                    echo 'error';
                                else
                                    echo 'warning';
                                ?></i></div>
                    </div>
                    <!--<a href="#">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>

                            <div class="clearfix"></div>
                        </div>
                    </a>-->
                </div>
            </div>

            <?php
            for ($i=0; $i<sizeof($logstashInstancesHealth); $i++) {
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="panel">
                        <div class="panel-heading text-white bg-info">
                            <p>Instance name: <?php echo $logstashInstancesHealth[$i]['name']; ?><br>
                                Instance PID: <?php echo $logstashInstancesHealth[$i]['pid']; ?><br>
                                Status: <?php echo ($logstashInstancesHealth[$i]['status']) ? 'Active':'Killed'; ?></p>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <!-- /.row -->

        <!-- Launchers -->
        <div class="row status">
            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white <?php
                    if ($launcherPath != null && $launcherFound)
                        echo 'bg-success';
                    else
                        echo 'bg-danger';
                    ?>">
                        <div class="text-center">Launcher file</div>

                        <div class="text-center check"><i class="material-icons">
                                <?php
                                if ($launcherPath != null && $launcherFound)
                                    echo 'check_circle';
                                else
                                    echo 'error';
                                ?></i></div>
                    </div>
                    <!--<a href="#">
                        <div class="panel-footer">
                            <span class="pull-left">View Details</span>

                            <div class="clearfix"></div>
                        </div>
                    </a>-->
                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white bg-info">
                        <?php
                        if (!$conf['launcher']['distant_launcher']) {
                            ?>
                            <p>Path: <span class="small"><?php echo $launcherPath; ?></span><br>
                                Status: <?php echo ($launcherFound) ? 'Found' : 'Not found'; ?></p>
                            <?php
                        }
                        else {
                            ?>
                            <p>Host: <?php echo $launcherUser.'@'.$launcherHost; ?><br>
                                Path: <span class="small"><?php echo $launcherPath; ?></span><br>
                                Status: <?php echo ($launcherFound) ? 'Found' : 'Not found'; ?></p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.row -->

    </div>
    <!-- /.container -->
</main>

<?php
include 'commons/foot.php';
include 'commons/footer.php';
?>

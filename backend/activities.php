<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 27/02/2018
 * Time: 16:09
 */
?>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);
include 'commons/head.php';
include 'commons/header.php';

include '../vendor/autoload.php';
include '../lib/Ping.class.php';

use JJG\Ping;
use Toml\Parser;
?>
<main role="main">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Activités</h3>
            <p>Les indicateurs permettent de comprendre l'origine d'éventuels problèmes</p>
<!--            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>-->
        </div>
    </div>

    <div class="container">
        <?php
        $conf = Parser::fromFile(ini_get('include_path').'/config.toml');

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
        $kafkaHost = $conf['kafka']['host'];
        $kafkaAllGood = true;
        $kafkaAllBad = true;
        $kafkaHostsHealth = [];

        $out = null;
        $fullHost = explode(':', $kafkaHost);

//        exec('netstat -an | grep '.$host[0].'.'.$host[1].' | grep LISTEN', $out);

        $host = $fullHost[0];
        $port = $fullHost[1];
        $waitTimeoutInSeconds = 1;
        if ($fp = fsockopen($host,$port,$errCode,$errStr,$waitTimeoutInSeconds)){
            $kafkaAllBad = false;
            $kafkaHostsHealth[] = [$kafkaHost => true];
            fclose($fp);
        } else {
            // It didn't work
            $kafkaAllGood = false;
            $kafkaHostsHealth[] = [$kafkaHost => false];
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

        $launcherStartFound = false;
        $launcherStopFound = false;
        if ($conf['launcher']['distant_launcher'] == true) {
            $out = null;
            $launcherUser = $conf['launcher']['distant']['user'];
            $launcherPwd = $conf['launcher']['distant']['pwd'];
            $launcherHost = $conf['launcher']['distant']['host'];
            $launcherPath = $conf['launcher']['distant']['path'];

            $ping = new Ping($launcherHost);
            $latency = $ping->ping('fsockopen');

            $out = null;

            if ($latency !== false) {
                $remote_conn = ssh2_connect($launcherHost, 22);

                if (ssh2_auth_password($remote_conn, $launcherUser, $launcherPwd)) {
                    $startStream = ssh2_exec($remote_conn, '[[ -f '.$launcherPath.'start_logstash.sh ]] && echo \'Found\' || echo \'Not found\'');
                    stream_set_blocking($startStream, true);
                    $launcherStartFound = (trim(fgets($startStream)) == 'Found');

                    $stopStream = ssh2_exec($remote_conn, '[[ -f '.$launcherPath.'stop_logstash.sh ]] && echo \'Found\' || echo \'Not found\'');
                    stream_set_blocking($stopStream, true);
                    $launcherStopFound = (trim(fgets($stopStream)) == 'Found');
                }
            }
        }
        else {
            $out = null;
            exec('[[ -f '.$conf["launcher"]["local"]["path"].'start_logstash.sh ]] && echo "Found" || echo "Not found"', $out);

            $launcherPath = $conf["launcher"]["local"]["path"];
            $launcherStartFound = (isset($out[0]) && $out[0] == 'Found');

            $out = null;
            exec('[[ -f '.$conf["launcher"]["local"]["path"].'stop_logstash.sh ]] && echo "Found" || echo "Not found"', $out);

            $launcherPath = $conf["launcher"]["local"]["path"];
            $launcherStopFound = (isset($out[0]) && $out[0] == 'Found');
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
                    if ($launcherPath != null && $launcherStartFound && $launcherStopFound)
                        echo 'bg-success';
                    elseif ($launcherPath != null && (($launcherStopFound && !$launcherStartFound) || ($launcherStartFound && !$launcherStopFound)))
                        echo 'bg-warning';
                    else
                        echo 'bg-danger';
                    ?>">
                        <div class="text-center">Launcher file</div>

                        <div class="text-center check"><i class="material-icons">
                                <?php
                                if ($launcherPath != null && $launcherStartFound && $launcherStopFound)
                                    echo 'check_circle';
                                elseif ($launcherPath != null && (($launcherStopFound && !$launcherStartFound) || ($launcherStartFound && !$launcherStopFound)))
                                    echo 'report_problem';
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
                                Start launcher: <?php echo ($launcherStartFound) ? 'Found' : 'Not found'; ?></p>
                            <?php
                        }
                        else {
                            ?>
                            <p>Host: <?php echo $launcherUser.'@'.$launcherHost; ?><br>
                                Path: <span class="small"><?php echo $launcherPath; ?></span><br>
                                Start launcher: <?php echo ($launcherStartFound) ? 'Found' : 'Not found'; ?></p>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="panel">
                    <div class="panel-heading text-white bg-info">
                        <?php
                        if (!$conf['launcher']['distant_launcher']) {
                            ?>
                            <p>Path: <span class="small"><?php echo $launcherPath; ?></span><br>
                                Stop launcher: <?php echo ($launcherStopFound) ? 'Found' : 'Not found'; ?></p>
                            <?php
                        }
                        else {
                            ?>
                            <p>Host: <?php echo $launcherUser.'@'.$launcherHost; ?><br>
                                Path: <span class="small"><?php echo $launcherPath; ?></span><br>
                                Stop launcher: <?php echo ($launcherStopFound) ? 'Found' : 'Not found'; ?></p>
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

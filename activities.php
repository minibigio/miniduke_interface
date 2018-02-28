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

            $elasticHost = $conf['elasticsearch']['host'];
            $elasticJson = file_get_contents("http://".$elasticHost);
            var_dump($elasticJson);


            $kafkaHosts = $conf['kafka']['hosts'];
            foreach ($kafkaHosts as $kHost) {
                $port = explode(':', $kHost)[1];
                exec('netstat -an | grep '.$port.' | grep LISTEN', $out);
                var_dump($out);
            }

            $pdo = new PDO($conf["database"]["connection"]);

            $logstashInstancesSql= $pdo->prepare('select * from logstash_instances');
            $logstashInstancesSql->execute();
            $logstashInstances = $logstashInstancesSql->fetchAll();
            echo '<hr>';
            foreach ($logstashInstances as $lInstance) {
                $out = null;
                var_dump($lInstance);
                exec('ps -A | grep '.$lInstance['pid'], $out);
                var_dump($out);
            }

            if ($conf['launcher']['distant_launcher'] == true) {
                $out = null;
                $hostDistant = $conf['launcher']['distant']['host'];
                exec('ping '.$hostDistant, $out);
                var_dump($out);

                $out = null;
                exec('ssh '.$conf["launcher"]["distant"]["user"].'@'.$conf["launcher"]["distant"]["host"].'
                             "[[ -f '.$conf["launcher"]["distant"]["path"].'start_logstash.sh ]] && echo "Found" || echo "Not found"', $out);
                if ($out == 'Found')
                    echo 'Found !!!';
                var_dump($out);

            }
            else {
                $out = null;
                exec('[[ -f '.$conf["launcher"]["local"]["path"].'start_logstash.sh ]] && echo "Found" || echo "Not found"', $out);

                if ($out[0] == 'Found')
                    echo 'Found !!!';
                else
                    echo 'ko';
            }
        ?>
    </div> <!-- /container -->

</main>

<?php
include 'commons/foot.php';
include 'commons/footer.php';
?>

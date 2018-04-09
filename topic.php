<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 20/02/2018
 * Time: 15:39
 */

?>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

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

if (isset($_POST['source']) && $_POST['source'] != null) {
    if (!in_array($_POST['source'], $topics)) {
        if (preg_match('/^[a-zA-Z0-9_]*$/', $_POST['source'])) {
            file_put_contents('./topics_conf/' . strtolower($_POST['source']) . '.conf', '');
            file_put_contents('./topics_data/' . strtolower($_POST['source']) . '.json', '');
        }
    }
}

if (isset($_GET['pull']) && in_array($_GET['pull'], $topics)) {
    $macosPATH = '/Library/Frameworks/Python.framework/Versions/3.6/bin/python3.6';
    $linuxPATH = 'python3';
    exec($linuxPATH.' consume.py '.$_GET['pull'], $output);
}

if (isset($_GET['delete']) && in_array($_GET['delete'], $topics)) {

    if (file_exists('./topics_conf/'.strtolower($_GET['delete']) . '.conf'))
        unlink('./topics_conf/' . strtolower($_GET['delete']) . '.conf');

    if (file_exists('./topics_data/'.strtolower($_GET['delete']) . '.json'))
        unlink('./topics_data/' . strtolower($_GET['delete']) . '.json');
}

?>



<?php
include 'commons/head.php';
include 'commons/header.php';
?>


<main role="main">

    <!-- Main jumbotron for a primary marketing message or call to action -->
    <div class="jumbotron">
        <div class="container">
            <h3>Sources</h3>
            <p>A chaque source est associé un topic qu'il faut créer dans NiFi et dans un fichier</p>
            <p><a class="btn btn-primary btn-lg" href="#" role="button">Comment faire &raquo;</a></p>
        </div>
    </div>

    <div class="container">


        <div class="row justify-content-md-center">
            <div class="col-md-3">
                <h4>Topics existants</h4>
                <ul>
                    <?php
                    foreach ($topics as $topic) {
                        if (strpos($topic, '_raw') === false)
                            echo '<li>'.$topic.' <a href="topic.php?edit='.$topic.'"><i class="material-icons">mode_edit</i></a></li>';
                    }
                    ?>
                </ul>
            </div>

            <?php
            if (isset($_GET['edit']) && in_array($_GET['edit'], $topics)) {
                $topic = $_GET['edit'];
                ?>
                <div class="col-md-6">
                    <h4>Edition d'un topic</h4>
                    <form action="topic.php" method="post">

                        <div class="form-group">
                            <label for="source">Topic</label>
                            <input name="source" class="form-control" id="source" value="<?php echo $topic; ?>">
                        </div>

                        <div class="form-group">
                            <label for="source_meta">Topic meta</label>
                            <input name="source_meta" class="form-control" id="source_meta" disabled value="<?php echo $topic.'_meta'; ?>">
                        </div>

                        <div class="text-center">
                            <a href="topic.php?delete=<?php echo $topic; ?>" class="deleteBtn"><button type="button" class="btn btn-danger">Supprimer</button></a>
                            <button class="btn btn-primary">Editer la source</button>
                            <a href="topic.php?pull=<?php echo $topic; ?>"><button type="button" class="btn btn-info">Pull les informations</button></a>
                        </div>
                    </form>
                </div>
                <?php
            }
            else {
                ?>

                <div class="col-md-6">
                    <h4>Nouveau topic</h4>
                    <form action="topic.php" method="post">

                        <div class="form-group">
                            <label for="source">Topic</label>
                            <input name="source" class="form-control" id="source">
                        </div>

                        <div class="form-group">
                            <label for="source_meta">Topic meta</label>
                            <input name="source_meta" class="form-control" id="source_meta" disabled>
                        </div>

                        <div class="text-center">
                            <button class="btn btn-primary">Ajouter une source</button>
                        </div>
                    </form>
                </div>
                <?php
            }
            ?>
        </div>

    </div> <!-- /container -->

</main>

<script type="text/javascript">
    document.querySelector('#source').addEventListener('keyup', function() {
        document.querySelector('#source_meta').value = this.value+'_meta';
    });
    document.querySelectorAll('.deleteBtn').forEach(function (t) {
        t.addEventListener('click', function() {
            event.preventDefault();
            var choice = confirm('Etes-vous sûr de vouloir supprimer cette entrée pour toujours ? (C\'est long !)');

            if (choice)
                window.location.href = this.getAttribute('href');
        })
    });
</script>

<?php
include 'commons/foot.php';
include 'commons/footer.php';
?>

<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 20/02/2018
 * Time: 19:02
 */
?>

<?php
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
?>

<nav class="navbar navbar-expand-md navbar-dark bg-dark">
    <a class="navbar-brand" href="#">Reconia</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="topic.php">Topics</a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="dropdown01" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Map</a>
                <div class="dropdown-menu" aria-labelledby="dropdown01">
                    <?php
                    foreach ($topics as $topic)
                        echo '<a class="dropdown-item" href="map.php?topic='.$topic.'">'.ucfirst($topic).'</a>';
                    ?>
                </div>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="activities.php">Activities</a>
            </li>
        </ul>
        <form class="form-inline my-2 my-lg-0">
            <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
            <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
        </form>
    </div>
</nav>
<div class="blue-border">
</div>

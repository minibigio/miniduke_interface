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

if (isset($_POST) && isset($_POST['nProps'])) {
    $json = [];

    for ($i=0; $i<$_POST['nProps']; $i++) {
        $prop = $_POST['prop'.$i];
        $newLabel = $_POST['newLabel'.$i];
        $type = $_POST['type'.$i];

        $json2 = [
                'name' => $prop,
                'label' => $newLabel,
                'type' => $type
        ];

        if ($type == 'radio') {
            $possibilities = [];
            for ($j=0; $j<sizeof($_POST['radioName'.$i]); $j++) {
                $rName = $_POST['radioName'.$i][$j];
                $rValue = $_POST['radioValue'.$i][$j];
                $possibilities[] = ['name' => $rName, 'value' => $rValue];
            }

            $json2['values'] = $possibilities;
        }

        $json[] = $json2;

    }

    $encoded = json_encode($json, JSON_PRETTY_PRINT);
    $fopen = fopen('../frontend/form.json', 'w');
    fwrite($fopen, $encoded);
    fclose($fopen);
}
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
        <h3>MDM field mapping</h3>
        <div class="col-md-6">
            <form action="configuration.php" method="post">

        <?php
        $conf = Parser::fromFile('../config.toml');

        $elasticHost = $conf['elasticsearch']['host'];
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

            $i=0;
            foreach ($elasticProperties as $prop) {
                ?>
                <div class="form-group">
                    <label for="newProp<?php echo $i; ?>"><?php echo $prop; ?></label>
                    <input type="hidden" name="prop<?php echo $i; ?>" value="<?php echo $prop; ?>">
                    <input name="newLabel<?php echo $i; ?>" class="form-control">
                </div>
                <div class="form-group">
                    <label for="type<?php echo $i; ?>">Type</label>
                    <select id="type<?php echo $i; ?>" name="type<?php echo $i; ?>" data-i="<?php echo $i; ?>">
                        <option value="number">Nombre</option>
                        <option value="text" selected>Chaine de caractères</option>
                        <option value="date">Date</option>
                        <option value="radio">Radio</option>
                    </select>
                    <div class="radio" data-i="<?php echo $i; ?>" style="display: none">
                        <div class="inputs">
                            <input name="radioName<?php echo $i; ?>[]">
                            <input name="radioValue<?php echo $i; ?>[]">
                            <input name="radioName<?php echo $i; ?>[]">
                            <input name="radioValue<?php echo $i; ?>[]">
                        </div>
                        <button type="button" class="addRadio btn btn-default">Ajouter</button>
                    </div>
                </div>
                <hr>
                <?php
                $i++;
            }
        }

        ?>
                <input type="hidden" name="nProps" value="<?php echo $i; ?>">
                <button class="btn btn-info">Map</button>
        </form>
        </div>
    </div>
</main>
<script type="text/javascript">
    document.querySelectorAll('select').forEach(function (value) {
        value.addEventListener('change', function (t) {
            console.log(t.target.getAttribute('data-i'));
            if (t.target.value === 'radio')
                document.querySelector('div[data-i="'+t.target.getAttribute('data-i')+'"]').style.display = 'block';
        });
    });
    document.querySelectorAll('.addRadio').forEach(function (value) {
        value.addEventListener('click', function() {
            var i = value.parentElement.getAttribute('data-i');
            console.log(value.parentElement.querySelector('.inputs'));
            value.parentElement.querySelector('.inputs').innerHTML += '<input name="radioName'+i+'[]">';
            value.parentElement.querySelector('.inputs').innerHTML += '<input name="radioValue'+i+'[]">';
        });
    });
</script>

<?php
include 'commons/footer.php';
include 'commons/foot.php';
?>

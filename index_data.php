<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 08/02/2018
 * Time: 15:28
 */

?>

<?php
//exec('hfefiega', $output);
//exec('/usr/bin/nohup /usr/local/logstash-5.6.5/bin/logstash -f /Library/WebServer/Documents/miniduke_interface/test.conf >/dev/null 2>&1 &', $output);
//exec('/usr/local/logstash-5.6.5/bin/logstash -f /Library/WebServer/Documents/miniduke_interface/test.conf 2>&1', $output);
//exec('./exec.sh >/dev/null 2>&1 &', $output);
exec('sh exec.sh', $output);
var_dump($output);
echo 'ok';
//
//
//class My extends Thread {
//    public function run() {
//        exec('/usr/local/logstash-5.6.5/bin/logstash -f /Library/WebServer/Documents/miniduke_interface/test.conf 2>&1', $output);
//        var_dump($output);
//    }
//}
//$my = new My();
//var_dump($my->start());

?>
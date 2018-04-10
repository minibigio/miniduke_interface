<?php
/**
 * Created by PhpStorm.
 * User: jmartell
 * Date: 06/02/2018
 * Time: 16:24
 */

?>

<?php

include 'vendor/autoload.php';
use Toml\Parser;

$add_field_logstash = 'add_field_logstash';
function add_field_logstash($data, $esKey, $formKey) {
    $out = '"'.$esKey.'" => [';
    $comma = 0;
    foreach ($data as $item) {
        $out .= '"'.$item[$formKey].'"';
        $out .= ($comma  < sizeof($data)-1) ? ', ':'';
        $comma++;
    }
    $out .= ']';

    return $out;
}

$disp_config_array = 'disp_config_array';
function disp_config_array($array) {
    $s = "[";
    $i = 0;
    foreach ($array as $a) {
        $s .= '"'.$a.'"';
        if ($i < sizeof($array) -1)
            $s .= ",";
        $i++;
    }
    $s .= "]";
    return $s;
}

if (isset($_POST) && $_POST['make_conf_file']) {

    if (isset($_POST['comparator']) && isset($_POST['key']) && isset($_POST['new_key']) && isset($_POST['high'])
        && isset($_POST['low']) && isset($_POST['threshold']) && isset($_POST['topic'])) {

        $comparator = $_POST['comparator'];
        $key = $_POST['key'];
        $newKey = $_POST['new_key'];
        $high = $_POST['high'];
        $low = $_POST['low'];
        $threshold = $_POST['threshold'];
        $topic = $_POST['topic'];


        $merged = [];
        for ($i = 1; $i <= sizeof($comparator); $i++) {
            $merged[] = ['comparator' => $comparator[$i],
                'key' => $key[$i - 1],
                'new_key' => $newKey[$i - 1],
                'high' => $high[$i],
                'low' => $low[$i]];

            $jsonMerged['comparators'][] = $comparator[$i];
            $jsonMerged['new_keys'][] = $newKey[$i - 1];
            $jsonMerged['weights'][] = [(double)$low[$i], (double)$high[$i]];
        }
        $jsonMerged['threshold'] = $threshold;

        file_put_contents('topics_conf/'.$topic.'.conf.json', json_encode($jsonMerged));


        $configuration = Parser::fromFile('config.toml');

        // Makes the data field
        $hash = "";
        $i = 0;
        foreach ($merged as $item) {
            $hash .= 'hash["' . $item['key'] . '"]';
            $hash .= ($i < sizeof($merged) - 1) ? ',' : '';
            $i++;
        }

        // Makes the weights
        $weight = '';
        $comma = 0;
        foreach ($merged as $item) {
            $weight .= '[' . $item['low'] . ', ' . $item['high'] . ']';
            $weight .= ($comma < sizeof($merged) - 1) ? ', ' : '';
            $comma++;
        }

        $logstashConfigurationFile = <<<TEXT
input { 
    kafka {
        "bootstrap_servers" => {$disp_config_array($configuration['kafka']['hosts'])}
        "topics" => {$disp_config_array($configuration['kafka']['topics'])}
    }
}
filter {
    ruby {
        init => "require 'json'"
        code => '
            eventHash = event.to_hash
            hash = JSON.parse(eventHash["message"])
            event.set("data", [$hash])
        '
    }
    mutate {
        add_field => {
            {$add_field_logstash($merged, 'fields', 'new_key')}
            {$add_field_logstash($merged, 'comparator', 'comparator')}
            "weight" => [ {$weight} ]
            {$add_field_logstash($merged, 'filters', 'new_key')}
            "threshold" => $threshold
            "host" => "{$configuration['elasticsearch']['host']}"
        }
    }
    prune {
        whitelist_names => ["fields", "comparator", "data", "weight", "filters", "threshold", "host", "timestamp"]
    }
}
output {
    elasticsearch { 
        hosts => "{$configuration['elasticsearch']['host']}"
        index => "{$configuration['elasticsearch']['index']}"
        document_type => "{$configuration['elasticsearch']['type']}"
        pipeline => "miniduke"
    }
    stdout { codec => rubydebug }
}
TEXT;

        file_put_contents('./topics_conf/'.$topic.'.conf', $logstashConfigurationFile);

    }
}

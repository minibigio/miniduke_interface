#!/bin/sh
nohup /usr/local/logstash-5.6.5/bin/logstash -f /Library/WebServer/Documents/miniduke_interface/topics_conf/$1.conf >> log.log 2>&1 &
echo $!

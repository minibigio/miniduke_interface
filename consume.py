import sys
from kafka import KafkaConsumer
import signal
import os

print ("Hello world !")

if len(sys.argv) > 1:
    topicsCons = KafkaConsumer(group_id='topic_search', bootstrap_servers=['192.168.0.237:9092'])
    topics = topicsCons.topics()

    topic = sys.argv[1]

    if topic in topics:
        # To consume latest messages and auto-commit offsets
        consumer = KafkaConsumer(sys.argv[1]+'_meta',
                             group_id='meta_source',
                             bootstrap_servers=['192.168.0.237:9092'])


        # Sets an handler function, you can comment it if you don't need it.
        signal.signal(signal.SIGALRM, lambda a,b: os.system('kill $PPID'))
        signal.alarm(3)

        # Reset the file
        data = open('topics_data/' + topic + '.json', 'w').close()

        for message in consumer:
            # message value and key are raw bytes -- decode if necessary!
            # e.g., for unicode: `message.value.decode('utf-8')`

            data = open('topics_data/' + topic + '.json', 'ab')
            data.write(message.value)
            data.close()


            print ("%s:%d:%d: key=%s value=%s" % (message.topic, message.partition,
                                                  message.offset, message.key,
                                                  message.value))




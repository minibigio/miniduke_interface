import sys
from kafka import KafkaConsumer
import signal
import os
import toml

modulename = 'toml'
if modulename not in sys.modules:
    print ('You have not imported the {} module'.format(modulename))

print ("Hello World")

if len(sys.argv) > 1:
    topic = sys.argv[2]

    # To consume latest messages and auto-commit offsets
    consumer = KafkaConsumer(topic+'_meta',
                         group_id='meta_source',
                         bootstrap_servers=[sys.argv[1]])

    print ("Hi again")

    # Sets an handler function, you can comment it if you don't need it.
    # signal.signal(signal.SIGALRM, lambda a,b: os.system('kill $PPID'))
    # signal.alarm(3)



    # Reset the file
    data = open('topics_data/' + topic + '.json', 'w').close()
    print ("Hi again")
    for message in consumer:
        # message value and key are raw bytes -- decode if necessary!
        # e.g., for unicode: `message.value.decode('utf-8')`

        data = open('topics_data/' + topic + '.json', 'ab')
        data.write(message.value)
        data.close()

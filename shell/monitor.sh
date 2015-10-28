#! /bin/sh

PID=`ps -ef|grep "process_keyword"|grep -v grep|awk '{print $2}'`
# if the process is not exist, run it
if [ -z "$PID" ]; then
    #start the process
fi

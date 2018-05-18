#!/bin/sh
if [ $# -lt 1 ]
then
  echo "no param：procedure_name"
  exit 1
fi
 
PROCESS=`ps -ef|grep $1|grep -v grep|grep -v PPID|awk '{ print $2}'`
for i in $PROCESS
do
  echo "Kill the $1 process [ $i ]"
  kill -9 $i
done
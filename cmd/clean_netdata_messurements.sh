#!/bin/bash

COUNTER=0
for i in $(influx -database=opentsdb -execute="SHOW MEASUREMENTS" | grep -E "netdata\\.netdata|netdata\\.fping|netdata\\.users|netdata\\.groups")
do
  #echo "influx -database=opentsdb -execute=\"drop measurement \\\"$i\\\"\" => $COUNTER"
  noterminal=$(influx -database=opentsdb -execute="drop measurement \"$i\"")
  echo "$i = $?"

  let COUNTER=COUNTER+1 
done

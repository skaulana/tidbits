#!/bin/bash
pnl_url=http://www.google.com/
pnl_result=$(curl -s -w '%{http_code}' -o /dev/null $pnl_url)
pnl_now=$(date +"%Y,%m,%d,%H,%M,%S")
pnl_yearmo=$(date +"%Y-%m")
pnl_log="pnl-$pnl_yearmo.csv"

if [ $pnl_result -eq 200 ]
then
  echo "OK,200,$pnl_url,$pnl_now" >> $pnl_log
else
  echo "ERR,$pnl_result,$pnl_url,$pnl_now" >> $pnl_log
fi

#!/bin/bash
step=5 #间隔的秒数，不能大于60

for i in `seq 0 $step 55`
do
	curl http://v2.easijar.com/index.php?route=crontab/crontab #调用链接
    if [ "$i" -ne "55" ]; then
    	sleep $step
    fi
done

#!/bin/bash
step=5 #间隔的秒数，不能大于60

# for (( i = 0; i < 60; i=(i+step) )); do
#     curl http://10.5.151.185/index.php?route=crontab/crontab #调用链接
#     sleep $step
# done
# exit 0
for i in `seq 0 $step 55`
do
	curl http://10.5.151.185/index.php?route=crontab/crontab #调用链接
    if [ "$i" -ne "55" ]; then
    	sleep $step
    fi
done

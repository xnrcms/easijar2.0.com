#!/bin/bash
step=5 #间隔的秒数，不能大于60
for (( i = 0; i < 60; i=(i+step) )); do
    curl http://v2.easijar.com/index.php?route=crontab/crontab #调用链接
    sleep $step
done
exit 0

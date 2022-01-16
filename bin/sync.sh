#!/bin/bash
# -------------------------------------------------------------
REMOTE_HOST='raspi'
REMOTE_PORT=22
REMOTE_USER='pi'
REMOTE_DIR='/home/pi/w1-mqtt-broker'
EXCLUDE_FILE='bin/sync-exclude.txt' # relative from local repo
# -------------------------------------------------------------
# init exclude file and remote shell
cd `dirname $0`/..
EXCLUDE_FILE=`pwd`/$EXCLUDE_FILE
REMOTE_SHELL="ssh -l $REMOTE_USER -p $REMOTE_PORT"
# -------------------------------------------------------------
rsync -e "${REMOTE_SHELL}" -r -t --progress --delete -avz --exclude-from=$EXCLUDE_FILE \
    ./ $REMOTE_USER@$REMOTE_HOST:$REMOTE_DIR/

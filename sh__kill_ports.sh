#!/bin/bash

# Check and kill process listening on port 465
PID_8000=$(sudo lsof -t -i :8000)
if [ ! -z "$PID_8000" ]; then
    echo "Killing process $PID_8000 on port 8000"
    sudo kill -9 $PID_8000
else
    echo "No process found on port 8000"
fi
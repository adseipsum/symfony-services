#!/usr/bin/env bash
s2i build -i .ssh/:/opt/app-root/src/.ssh  git@gitlab.com:robobloglab/sattelitor-services.git hydrargentum/php71-cli-centos7-base satterlitor-services
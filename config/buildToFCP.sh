#!/bin/sh

templatePath="../template";
staticPath="../static";
modulesPath="../modules";

modulesList="basicPage bigpipe";

requireModulesList="";

#if template or static folder exists
if [ -d "$templatePath" ]; then
    rm -rf $templatePath
fi

if [ -d "$staticPath" ]; then
    rm -rf $staticPath
fi

#start merging template files
#
mkdir $templatePath
for DIR in $modulesList; do
    if [ -d "$modulesPath/$DIR/template" ]; then
        cp $modulesPath/$DIR/template/* $templatePath
    fi
done

#start merging static files
#
mkdir $staticPath

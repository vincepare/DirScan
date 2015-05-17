#!/bin/bash
# Setup test environnement on Linux

if [ ! -d sandbox ]; then
    mkdir sandbox
fi

rm -rf sandbox/*

cd sandbox

# Creating directories
mkdir -p wheels/car/convertible
mkdir -p wheels/car/off-road/{buggy,"monster truck"}
mkdir -p wheels/bike/{sidecar,"mountain bike"}
mkdir -p wings/{plane,helicopter,seaplane}
mkdir -p wings/seaplane/canadair
mkdir -p hulls/{boat,"jet ski"}

# Creating files
echo "fuel" > fuel.txt
touch -d '1950-04-01 12:00:00' fuel.txt
cp -p fuel.txt wheels/car/convertible
cp -p fuel.txt wheels/car/off-road/buggy
cp -p fuel.txt wheels/car/off-road/"monster truck"
cp -p fuel.txt wings/seaplane
cp -p fuel.txt wings/seaplane/canadair
rm fuel.txt

# Creating symlinks
ln -sv "$PWD/wheels/car/convertible/fuel.txt" ln-file-absolute-fuel
ln -sv wheels/car/convertible/fuel.txt ln-file-relative-fuel
ln -sv "$PWD/wings/seaplane" hulls/ln-dir-absolute-seaplane
(cd hulls && ln -sv ../wings/seaplane ln-dir-relative-seaplane)

# Directory loop symlinks
ln -sv "$PWD/wheels" wheels/bike/sidecar/ln-dir-loop-absolute
(cd wheels/bike/sidecar && ln -sv ../.. ln-dir-loop-relative)

# Directories timestamp
find ./* -type d | xargs -I{} touch -d '1948-01-01 06:00:00' {}
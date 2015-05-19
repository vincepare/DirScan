DirScan - file system inventory
-------------------------------
DirScan is a file cataloging command line tool that lists all files inside a directory and its sub-directories to a text file, with file attributes like timestamps and permissions.

It can be used to compare all files in an entire partition at different times and see what changed between.

Example :

```
dirscan --deep --same-device / > "drive-content.txt"
```

DirScan is bundled with a text reporter, but you can customize its output by creating a custom reporting class, allowing to save data to, say, CSV, XML or an SQLite database.

### Download ###
Download dirscan.phar, make it executable (chmod +x dirscan.phar) and rename it if you want. On Linux, store it to `/usr/local/bin` to make it available everywhere :

```
wget -O dirscan https://github.com/finalclap/DirScan/releases/download/1.0.0/dirscan.phar
chmod +x dirscan
sudo mv dirscan /usr/local/bin/dirscan
```

### Requirement ###
Tested on PHP 5.3, 5.4, 5.5 & 5.6. There is also a [legacy release](https://raw.githubusercontent.com/finalclap/DirScan/master/src/legacy/dirscan) that works on PHP 5.2 with some limitations.

### Usage ###
```
Usage :
  dirscan [OPTIONS] TARGET

Options :
  --help, -h        This help message
  --deep, -d        Explore symbolic links (default : skip)
  --flat, -f        Do not explore subdirectories
  --access, -a      Report access time
  --htime           Report user friendly date nearby unix timestamps
  --same-device     Explore only directories on the same device as the start directory
                    Useful on Linux, to ignore special mount points like /sys or /proc
```

### About windows ###

DirScan is designed to work a Unix environment (Linux or Mac OS) but you can also use it on Windows. In this case, beware of NTFS junction points and symbolic links that are not handled properly by old php releases (see [readlink](http://php.net/manual/en/function.readlink.php) & [is_link](http://php.net/manual/en/function.is-link.php)). But you'd better use other tools like [WhereIsIt](http://www.whereisit-soft.com/).

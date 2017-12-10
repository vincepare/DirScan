DirScan - file system inventory
-------------------------------
DirScan is a file cataloging command line tool that lists all files inside a directory and its sub-directories to a text file, with file attributes like timestamps and permissions.

It can be used to compare all files in an entire partition at different times and see what changed between.

Example :

```
dirscan --deep --same-device / > "drive-content.txt"
```

DirScan is bundled with a text reporter, but you can customize its output by creating your own reporting class, allowing to save data to, say, CSV, XML or an SQLite database.

### Download
Download dirscan.phar, make it executable (chmod +x dirscan.phar) and rename it if you want. On Linux, store it to `/usr/local/bin` to make it available everywhere :

```
wget -O dirscan https://github.com/vincepare/DirScan/releases/download/1.3.0/dirscan.phar
chmod +x dirscan
sudo mv dirscan /usr/local/bin/dirscan
```

##### Update
```
wget -O "$(which dirscan)" https://github.com/vincepare/DirScan/releases/download/1.3.0/dirscan.phar
```

### Requirement
Tested on PHP 5.3, 5.4, 5.5 & 5.6. There is also a [legacy release](https://raw.githubusercontent.com/vincepare/DirScan/master/src/legacy/dirscan) that works on PHP 5.2 with some limitations.

### Usage
```
Usage :
  dirscan [OPTIONS] TARGET...

Options :
  --help, -h        This help message
  --version, -v     Print software version
  --deep, -d        Explore symbolic links (default : skip)
  --flat, -f        Do not explore subdirectories
  --same-device     Explore only directories on the same device as the start directory
                    Useful on Linux, to ignore special mount points like /sys or /proc
  --access, -a      Report access time
  --htime, -t       Report user friendly date nearby unix timestamps
  --perms, -p       Report file permissions
  --full            Report all properties
  --format=STRING   Custom reporting format, call with empty string to print format help
```

#### Formats
Format tokens to customize output (`--format`) :
```
  %u   Unique path
  %t   Type
  %s   Size
  %c   ctime
  %C   Change time
  %m   mtime
  %M   Modify time
  %a   atime
  %A   Access time
  %p   Permissions
  %o   UID
  %O   Owner
  %g   GID
  %G   Group
  %i   Inode
  %e   Extended
```

### Programmatic use
Although dirscan is released as a command line tool, you can also use its internal `DirScan` class as a file system iterator. Dirscan is available as a composer package :
```
composer require vincepare/dirscan
```

##### How to use
```php
require 'vendor/autoload.php';

use Vincepare\DirScan\DirScan;
use Vincepare\DirScan\Reporter;

class MyReporter extends Reporter
{
    public $files = [];
    
    public function push($node, DirScan $scanner)
    {
        $this->files[] = $node['path'];
    }
}

$settings = ['flat' => true];
$reporter = new MyReporter();
$scanner = new DirScan($settings, $reporter);
$scanner->scan('/tmp');
print_r($reporter->files);
```

### About windows
DirScan is designed to work on a Unix environment (Linux or Mac OS) but you can also use it on Windows. In this case, beware of NTFS junction points and symbolic links that are not handled properly by old php releases (see [readlink](http://php.net/manual/en/function.readlink.php) & [is_link](http://php.net/manual/en/function.is-link.php)). But you'd better use other tools like [WhereIsIt](http://www.whereisit-soft.com/).

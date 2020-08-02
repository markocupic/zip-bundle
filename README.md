![Alt text](src/Resources/public/logo.png?raw=true "logo")


# Zip extension
This bundle provides a simple Zip class.

# Usage
```php
$zip = (new \Markocupic\ZipBundle\Zip\Zip())
    ->stripSourcePath('path/to/source/dir')
    ->addDirRecursive('path/to/source/dir')
    ->run('path/to/destination/dir/myZip.zip');
```

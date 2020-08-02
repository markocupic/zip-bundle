![Alt text](src/Resources/public/logo.png?raw=true "logo")


# Zip extension
This bundle provides a simple Zip class.

# Usage
```php
$zip = (new \Markocupic\ZipBundle\Zip\Zip())
    ->stripSourcePath(true)
    ->zipRecursive('path/to/source/dir', 'path/to/target/dir');
```

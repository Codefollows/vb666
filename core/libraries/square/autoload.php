<?php
/*
Note, these package versions are as follows:
> composer require square/square
...
> composer show
apimatic/jsonmapper  v3.0.4
apimatic/unirest-php 2.3.0
square/square        19.1.1.20220616


jsonmapper & unirest-php are required by square/square (square php SDK).
The package files were generated via
>  composer require square/square
then manually removing unwanted files (e.g. .github & test folders, composer files),
then "flattening" out the directory structure to the hierarchy & directory names
expected by the sample autoloader (composer groups each individual package folder via
their namespaces, e.g. jsonmapper & unirest-php inside an apimatic folder, square under
a square folder). We could've gone the other way instead and updated the paths in the
autoloader to match composer, but I thought all of the folder nesting was unnecessary
and this more closely reflects the "manual" installation process.

The reason I went with composer to get the package files and this hybrid approach
is that their current docs don't specify the specific dependencies' versions to
manually pull, and the current "default" versions of those dependencies did NOT
match the listed versions defined in their square/square/composer.json file. For
example, apimatic/unirest-php "v2-master" is currently on v3.0.0 for some reason,
while the required version was 2.3.0, which may NOT be compatible (per the "major"
version increment, and the fact that directory structures seemed different).
Letting composer handle the specific versioning simplifies everything, but I did
not want to include the composer-specific files in our downloads unless absolutely
needed, thus the hybrid approach of generating the files via composer, then including
via the manual-install autoloader.

When upgrading the SDK:
If there are any dependency package changes, remember to update the $prefixToLocation
below accordingly. Most likely, you'll want to grab the sample autloader provided
by Square and merge it into this. At the time of writing, the manual install
instructions including a link to the sample autloader are found at
https://developer.squareup.com/docs/sdks/php/setup-project .

After upgrading the SDK files, you'll probably need to update
core/includes/paymentapi/class_square.php for any incompatible changes.
If the $square_api_version property still exists, update that as well.
 */

/**
 * Based on Square's sample autoloader for manual (non-composer) install,
 * which in turn seems to be based on the PSR-4 autoloader example (see
 * facebook autoloader in libraries)
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Square\Baz class
 * from /path/to/project/src/Baz.php:
 *
 *      new \Square\Baz;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class) {

    /**
     * An array with project-specific namespace prefix as keys and location relative to this autoloader.php file as values
     * You can find this information in each of the package's composer.json file on the  "autoload" field
     *
     * NOTE: The key of the autoload object denotes the format used.
     * If the key is "psr-4" then there is no need to append the namespace to the path.
     * if the key is "psr-0" then the namespace needs to be appended. Unirest is an example of the psr-0 format.
     */
    $prefixToLocation = [
        "Square\\" => "/square-php-sdk/src/",
        "apimatic\\jsonmapper\\" => "/jsonmapper/src/",
        // This is the Namespace and location from Apimatic/Unirest's composer.json
		// Note the directory structure difference between v2.3.0 vs v3.0.0
		// (https://github.com/apimatic/unirest-php/tree/2.3.0/src vs
		// https://github.com/apimatic/unirest-php/tree/3.0.0/src), specifically
		// finding e.g. Request.php in .../src/Unirest/ in 2.3.0 vs .../src/ in 3.0.0
		// for Unirest\Request
        "Unirest\\" =>  "/unirest-php/src/Unirest/",
    ];

    $matchingPrefix;
    foreach ($prefixToLocation as $prefix => $location)
	{
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0)
		{
            continue;
        }
		else
		{
            $matchingPrefix = $prefix;
        }
    }

    if (!$matchingPrefix) {
		// ClassPrefix was not found return &
        // move to the next registered autoloader
		return;
	}

    // base directory for the namespace prefix
    $base_dir = (__DIR__ . $prefixToLocation[$matchingPrefix]);

    // get the relative class name
    $relative_class = substr($class, strlen($matchingPrefix));

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});
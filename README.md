# LaunchKey WordPress Plugin [![Build Status](https://travis-ci.org/LaunchKey/launchkey-wordpress.svg?branch=master)](https://travis-ci.org/LaunchKey/launchkey-wordpress)

Development and contribution repo for http://wordpress.org/plugins/launchkey/

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request

## Development

1.  Clone the repository
    
    ```bash
    git clone git@github.com:LaunchKey/launchkey-wordpress.git
    ```

2.  Install the project dependencies
    
    The project utilizes [Composer](https://getcomposer.org/) for dependency management.

    ```bash
    php composer.phar install
    ```

3.  Run tests to make sure everything still works

    The project utilizes [PHPUnit](https://phpunit.de/) for testing and [Phake](http://phake.readthedocs.org/) for
    mocking.
    
    ```bash
    ./bin/phpunit
    ```

4. Build/install or publish changes to a WordPress installation for testing and validation
    
    The project uses [Phing](https://www.phing.info/) for builds.  A list of Phing build targets are available by
    executing `./bin/phing -l`.
    
    ### Targets:

    * clean - Remove the entire build directory to start a clean build.  It is highly suggested to use clean before
      build or deply to ensure you do not have any remnant code remaining.
    * build - Build will do the folowing:
        * Package the minimum necessary files for the plugin in the `build/package` directory
        * Generate a plugin install ZIP file at `build/launchkey.zip`.
    * deploy - Executes a build target and then adds/replaces the LaunchKey plugin installation.  This target
      requires the root directory location of the WordPress installation and assumes the plugins are located in the
      subdirectory `wp-content/plugins` off of the root directory you provide.
    
    ### Examples

    Build a clean deployment:
    
    ```bash
    ./bin/phing clean build
    ```
    
    Deploy to a local WordPress installation:
    
    ```bash
    ./bin/phing clean deploy
    ```
    
    Deploy to a local WordPress installation without prompting for the WordPress root directory:
    
    ```bash
    ./bin/phing -DwpRootDir=/usr/local/apache/htdocs/wordpress clean deploy
    ```

## Releasing

1.  Update the changelog section of the readme.txt with the changes for the version you are releasing.

2.  Make a clean build of the repository
    
    ```bash
    ./bin/phing clean build
    ```

3.  Checkout the Subversion repository to a different location:
    
    ```bash
    svn checkout https://plugins.svn.wordpress.org/launchkey build/launchkey-svn
    ```

4.  Replace the trunk directory contents of the Subversion checkout with the `build/package` directory
    
    ```bash
    rm -r build/launchkey-svn/trunk
    cp -r build/package build/launchkey-svn/trunk
    ```

5.  Resolve added and removed files in the `build/launchkey-svn/trunk` directory
    
    ```bash
    svn status build/launchkey-svn
    svn add build/launchkey-svn/trunk/missing.file build/launchkey-svn/trunk/other-missing.file
    svn rm build/launchkey-svn/trunk/removed.file build/launchkey-svn/trunk/other-removed.file
    ```

6.  Commit your changes to trunk and include the changes.
    
    Be sure to add the changes recorded in the changelog to the commit message.
    
    ```bash
    svn commit build/launchkey-svn
    ```

7.  Tag your release
    
    *x.x.x in the example is your release version.  We use [Semantic versioning](http://semver.org/) for the project.*
    
    ```bash
    svn copy http://plugins.svn.wordpress.org/launchkey/trunk http://plugins.svn.wordpress.org/launchkey/tags/x.x.x -m "Tagging release x.x.x"
    ```


8.  Test your release.
  
    The new release will be available on the plugin page for download shortly after the tagging operation is complete.
    Install that version and test that it is working properly.

9.  Make the new release the current stable version.

    1.  Update the readme.txt
        * Update the "Stable tag" value in the readme.txt to your new version: `Stable tag: x.x.x`
        * Add or update the "Upgrade Notice" section of the readme.txt as appropriate
        * Update the other sections in the readme.txt as applicable in the project

    2.  Update the assets in the assets directory.
    
    3.  Copy the readme.txt to the Subversion repository trunk.
        
        ```bash
        cp readme.txt build/launchkey-svn/trunk/
        ```
    
    4.  Replace the assets in the Subversion repository assets directory (if necessary).
        
        ```bash
        rm -r build/launchkey-svn/assets
        cp -r assets build/launchkey-svn/
        
    5.  Commit your changes.
        
        ```bash
        svn commit build/launchkey-svn
        ```
    
    6.  Test the automatic upgrade
        
        1. Install and configure the previous version of the plugin
        2. Apply the available update from the plugins page
        3. Verify the plugin is working properly

Steps for creating a new release
--------------------------------

  1. Cleanup code
  2. Export configuration
  3. Review code
  4. Run tests
  5. Generate release notes
  6. Tag and create a new release


1. Cleanup code
---------------

[Convert to short array syntax](https://www.drupal.org/project/short_array_syntax)

    drush short-array-syntax webform

Tidy YAML files

    drush webform-tidy webform; 
    drush webform-tidy webform_ui; 
    drush webform-tidy webform_test;
    drush webform-tidy webform_test_translation;


2. Export configuration
-----------------------

    # Install all sub-modules.
    drush en -y webform webform_test webform_test_translation webform_examples webform_templates webform_node
    
    # Export webform configuration from your site.
    drush features-export -y webform
    drush features-export -y webform_test
    drush features-export -y webform_test_translation
    drush features-export -y webform_examples
    drush features-export -y webform_templates
    
    # Tidy webform configuration from your site.
    drush webform-tidy -y --dependencies webform
    drush webform-tidy -y --dependencies webform_test
    drush features-tidy -y --dependencies webform_test_translation
    drush webform-tidy -y --dependencies webform_examples
    drush webform-tidy -y --dependencies webform_templates
    
    # Reset certain files.
    cd modules/sandbox/webform
    git reset HEAD webform.info.yml
    git reset HEAD tests/modules/webform_test/webform_test.info.yml
    git reset HEAD tests/modules/webform_test/config/optional


3. Review code
--------------

[Online](http://pareview.sh)

    http://git.drupal.org/project/webform.git 8.x-5.x

[Commandline](https://www.drupal.org/node/1587138)

    # Make sure to remove the node_modules directory.
    rm -Rf node_modules

    # Check Drupal coding standards
    phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info modules/sandbox/webform
    
    # Check Drupal best practices
    phpcs --standard=DrupalPractice --extensions=php,module,inc,install,test,profile,theme,js,css,info modules/sandbox/webform

[File Permissions](https://www.drupal.org/comment/reply/2690335#comment-form)

    # Files should be 644 or -rw-r--r--
    find * -type d -print0 | xargs -0 chmod 0755

    # Directories should be 755 or drwxr-xr-x
    find . -type f -print0 | xargs -0 chmod 0644


4. Run tests
------------

[SimpleTest](https://www.drupal.org/node/645286)

    # Run all tests
    php core/scripts/run-tests.sh --url http://localhost/d8_dev --module webform

[PHPUnit](https://www.drupal.org/node/2116263)

    # Execute all PHPUnit tests.
    cd core
    php ../vendor/phpunit/phpunit/phpunit --group WebformUnit

    # Execute individual PHPUnit tests.
    cd core
    export SIMPLETEST_DB=mysql://drupal_d8_dev:drupal.@dm1n@localhost/drupal_d8_dev;
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/webform/tests/src/Unit/WebformTidyTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/webform/tests/src/Unit/WebformHelperTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/webform/tests/src/Unit/WebformElementHelperTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/webform/tests/src/Unit/WebformOptionsHelperTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/webform/tests/src/Unit/WebformArrayHelperTest.php     
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/webform/src/Tests/WebformEntityElementsValidationUnitTest.php    


5. Generate release notes
-------------------------

[Git Release Notes for Drush](https://www.drupal.org/project/grn)

    drush release-notes --nouser 8.x-5.0-VERSION 8.x-5.x


6. Tag and create a new release
-------------------------------

[Tag a release](https://www.drupal.org/node/1066342)

    git tag 8.x-5.0-VERSION
    git push --tags
    git push origin tag 8.x-5.0-VERSION

[Create new release](https://www.drupal.org/node/add/project-release/2640714)

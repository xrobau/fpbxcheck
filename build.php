#!/usr/bin/env php
<?php
unlink("fpbxseccheck.phar");
system(__DIR__."/box.phar build -c build.json -v");

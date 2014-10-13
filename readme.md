     ______             _____  ______   __   _____ _               _    
    |  ____|           |  __ \|  _ \ \ / /  / ____| |             | |   
    | |__ _ __ ___  ___| |__) | |_) \ V /  | |    | |__   ___  ___| | __
    |  __| '__/ _ \/ _ \  ___/|  _ < > <   | |    | '_ \ / _ \/ __| |/ /
    | |  | | |  __/  __/ |    | |_) / . \  | |____| | | |  __/ (__|   < 
    |_|  |_|  \___|\___|_|    |____/_/ \_\  \_____|_| |_|\___|\___|_|\_\

FreePBX Vulnerability and Signature Checker
===========

###What?
FreePBX© is an open source GUI (graphical user interface) that controls and manages Asterisk© (PBX).FreePBX© is licensed under GPL.

This script will check your FreePBX system to make sure modules are signed and that any found vulnerabilities are cleaned up to the best of our abilities.

###Usage
Get the compiled script:

    cd /usr/src
    wget http://git.freepbx.org/projects/FL/repos/freepbx-check/browse/fpbxseccheck.phar?raw
    chmod +x fpbxseccheck.phar

Basic Usage:

    ./fpbxseccheck.phar
This will tell you if any modules have invalid files or modified files, you can then redownload said modules manually or run the commands below
    
Automatically attempting to clean up a compromised system

    ./fpbxseccheck.phar --clean
    
Automatically redownload any invalidly signed modules

    ./fpbxseccheck.phar --redownload

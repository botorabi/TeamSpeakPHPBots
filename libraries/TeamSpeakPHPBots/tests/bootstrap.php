<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

/**
 * bootstrap used for testing the framework.
 * 
 * @created:  27th June 2016
 * @author:   Botorabi
 */

error_reporting(E_ALL);
ini_set("display_errors",1);


require_once("TestConfiguration.php");
use com\tsphpbots\db\DB;


//! Class doing all setup for the testing.
class Bootstrap {

    //! Framework source directory used in autoloader
    protected $PATH_SRC_MAIN = "../src";

    //! Name of test table
    protected $TEST_TABLE_NAMES_TO_CLEANUP = ["user", "dbobject", "bot"];
    protected $TEST_SQL_FILE               = "create-testdatabase.sql";

    protected $testTableCreated = false;

    /**
     * Create the bootstrap instance.
     */
    public function __construct() {
        $this->init();
    }


    /**
     * Initialize the bootstrapper
     */
    protected function init() {

        // start a session needed for web interface related tests
        session_start();

        // Adapt the base directory for web interface resources
        Configuration::$TSPHPBOT_CONFIG_WEB_INTERFACE["dirBase"] = "..";
        Configuration::$TSPHPBOT_CONFIG_WEB_INTERFACE["appSrc"] = "../src";

        echo "BOOTSTRAP: Setting up the autoloader.\n";
        //! Setup our class file loader
        spl_autoload_register(function ($name) {
            //echo "<loading>: " . $name . "\n";
            $pos = strpos($name, "TeamSpeak3");
            if ($pos === false) {

                $fullpath = $this->PATH_SRC_MAIN . "/" . $name;
                $fullpath = str_replace("\\", "/", $fullpath);
                //echo "full path: ". $fullpath ."\n";
                if (file_exists($fullpath . ".php")) {
                    include $fullpath . ".php";
                    return true;
                }
                else {
                    echo "*** Module does not exist: " . $name . "\n";
                }
                return false;
            }
        });

        register_shutdown_function(function(){
            $this->shutdown();
        });

        $this->createTestDB();
    }

    /**
     * Shutdown and clear resources.
     */
    protected function shutdown() {
        $this->cleanupTestDB();
    }

    /**
     * Create a table dedicated to testing. It will be deleted on end of testing.
     */
    protected function createTestDB() {

        if (!DB::connect()) {
            echo "*** Could not connect to database, check the configuration!\n";
            return;
        }
        $file = @fopen($this->TEST_SQL_FILE, "r");
        if (!$file) {
            echo "*** Cannot open SQL file: " . $this->TEST_SQL_FILE . "\n";
            return;
        }
        $sql = fread($file, filesize($this->TEST_SQL_FILE));
        fclose($file);
        try {
            $stmt = DB::prepareStatement($sql);
            $stmt->execute();
            $res = $stmt->errorInfo();
            if (strcmp($res[0], "00000")) {
                echo "*** Problem occurred while creating test table: " . print_r($res, true) . "\n";
                return;
            }
        }
        catch(Exception $e) {
            echo "*** Could not create test table! Reason: " . $e->getMessage() . "\n";
            return;
        }

        $this->testTableCreated = true;
        echo "BOOTSTRAP: Test database successfully created\n";
    }

    /**
     * Remove the test tables from database.
     */
    protected function cleanupTestDB() {

        if (!$this->testTableCreated) {
            return;
        }

        foreach($this->TEST_TABLE_NAMES_TO_CLEANUP as $tablename) {
            $sql = "DROP TABLE IF EXISTS " . $tablename;
            try {
                $stmt = DB::prepareStatement($sql);
                $stmt->execute();
                $res = $stmt->errorInfo();
                if (strcmp($res[0], "00000")) {
                    echo "*** Problem occurred while dropping test table: " . print_r($res, true) . "\n";
                }
            }
            catch(Exception $e) {
                echo "*** Could not drop test table! Reason: " . $e->getMessage() . "\n";
            }
        }
        $this->testTableCreated = false;
        echo "BOOTSTRAP: Test tables successfully deleted.\n";   
    }
}

$bt = new Bootstrap();

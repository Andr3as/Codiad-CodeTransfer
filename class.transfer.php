<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See [root]/license.md for more information. This information must remain intact.
 */

    class transfer_controller {
        
        static public function startConnection($host, $user, $pass, $port) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                $ftp->startConnection($host, $user, $pass, $port);
            } else {
                $ssh2 = new scp_client();
                $ssh2->startConnection($host, $user, $pass, $port);
            }
        }
        
        static public function stopConnection() {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                $ftp->stopConnection();
            } else {
                $ssh2 = new scp_client();
                $ssh2->stopConnection();
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remote server index (Returns a list of files and directorys on the 
        //      remote server as json) For more information: see parseRawList();
        /////////////////////////////////////////////////////////////////////////
        static public function getServerFiles($path) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->getServerFiles($path);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->getServerFiles($path);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer a file to remote server
        /////////////////////////////////////////////////////////////////////////
        static public function transferFileToServer($cPath, $sPath, $fName, $mode) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->transferFileToServer($cPath, $sPath, $fName, $mode);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->transferFileToServer($cPath, $sPath, $fName);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer a file to Codiad Server
        /////////////////////////////////////////////////////////////////////////
        static public function transferFileToClient($cPath, $sPath, $fName, $mode) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->transferFileToClient($cPath, $sPath, $fName, $mode);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->transferFileToClient($cPath, $sPath, $fName);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Create directory on remote server
        /////////////////////////////////////////////////////////////////////////
        static public function createServerDirectory($path) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->createServerDirectory($path);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->createServerDirectory($path);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Get current directory name
        /////////////////////////////////////////////////////////////////////////
        static public function getSeverDirectory() {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->getSeverDirectory();
            } else {
                $ssh2 = new scp_client();
                return $ssh2->getSeverDirectory();
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remove file on remote server
        /////////////////////////////////////////////////////////////////////////
        static public function removeServerFile($path) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->removeServerFile($path);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->removeServerFile($path);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remove directory on remote server
        /////////////////////////////////////////////////////////////////////////
        static public function removeServerDirectory($path) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->removeServerDirectory($path);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->removeServerDirectory($path);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Change permissions of file on remote server
        /////////////////////////////////////////////////////////////////////////
        static public function changeServerFileMode($path, $mode) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->changeServerFileMode($path, $mode);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->changeServerFileMode($path, $mode);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Rename directory or file
        /////////////////////////////////////////////////////////////////////////
        static public function rename($path, $old, $new) {
            if ($_SESSION['transfer_type'] == "ftp") {
                $ftp = new ftp_client();
                return $ftp->rename($path, $old, $new);
            } else {
                $ssh2 = new scp_client();
                return $ssh2->rename($path, $old, $new);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Rename directory or file
        /////////////////////////////////////////////////////////////////////////
        static public function changeServerGroup($path, $name) {
            if ($_SESSION['transfer_type'] == "ftp") {
                return '{"status":"error","message":"FTP: Impossible To Change Group"}';
            } else {
                $ssh2 = new scp_client();
                return $ssh2->changeServerGroup($path, $name);
            }
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Sort an array of a server index
        /////////////////////////////////////////////////////////////////////////
        static public function mySort($a, $b) {
            $d = self::editString($a['fName']);
            $e = self::editString($b['fName']);
            $c = array($d, $e);
            sort($c);
            if ($d == $e) {
                return 0;
            }
            if ($c[0] == $d) {
                return -1;
            } else {
                return 1;
            }
        }
        
        static public function editString($str) {
            if (substr($str, 0, 1) == ".") {
                $str = substr($str, 1);
            }
            return strtolower($str);
        }
        
        static public function getWorkspacePath($path) {
			//Security check
			if (!Common::checkPath($path)) {
				die('{"status":"error","message":"Invalid path"}');
			}
            if (strpos($path, "/") === 0) {
                //Unix absolute path
                return $path;
            }
            if (strpos($path, ":/") !== false) {
                //Windows absolute path
                return $path;
            }
            if (strpos($path, ":\\") !== false) {
                //Windows absolute path
                return $path;
            }
            return "../../workspace/".$path;
        }
    }
?>
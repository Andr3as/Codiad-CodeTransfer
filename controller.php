<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See [root]/license.md for more information. This information must remain intact.
 */

    require_once('../../common.php');
    require_once('class.ftp.php');
    
    checkSession();
    set_time_limit(0);
    
    switch($_GET['action']) {
        
        case 'connect':
            if (isset($_POST['host']) && isset($_POST['user']) && isset($_POST['password']) && isset($_POST['port'])) {
                $ftp = new ftp_client();
                $ftp->startConnection($_POST['host'], $_POST['user'], $_POST['password'], $_POST['port']);
                echo '{"status":"success","message":"Connection started"}';
            } else {
                echo '{"status":"error","message":"Connection failed"}';
            }
            break;
        
        case 'disconnect':
            $ftp = new ftp_client();
            echo $ftp->stopConnection();
            break;
        
        case 'getServerFiles':
            if (isset($_GET['path'])) {
                $path = $_GET['path'];
            } else {
                $path = "/";
            }
            $ftp = new ftp_client();
            echo $ftp->getServerFiles($path);
            break;
            
        case 'getSeverDirectory':
            $ftp = new ftp_client();
            echo $ftp->getSeverDirectory;
            break;
        
        case 'transferFileToServer':
            if (isset($_GET['cPath']) && isset($_GET['sPath']) && isset($_GET['fName'])  && isset($_GET['mode'])) {
                $ftp    = new ftp_client();
                echo $ftp->transferFileToServer($_GET['cPath'], $_GET['sPath'], $_GET['fName'], $_GET['mode']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'transferFileToClient':
            if (isset($_GET['cPath']) && isset($_GET['sPath']) && isset($_GET['fName'])  && isset($_GET['mode'])) {
                $ftp    = new ftp_client();
                echo $ftp->transferFileToClient($_GET['cPath'], $_GET['sPath'], $_GET['fName'], $_GET['mode']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'createLocalDirectory':
            if (isset($_GET['path'])) {
                $path = $_GET['path'];
                $path = "../../workspace/" . $path;
                if (mkdir($path)) {
                    echo '{"status":"success","message":"Directory Created"}';
                } else {
                    echo '{"status":"error","message":"Failed To Create Directory!"}';
                }
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'createServerDirectory':
            if (isset($_GET['path'])) {
                $ftp = new ftp_client();
                echo $ftp->createServerDirectory($_GET['path']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        //action=remove"+data+type+"&path="+path
        case 'removeLocalFile':
            if (isset($_GET['path'])) {
                $path = "../../workspace/" . $_GET['path'];
                if (unlink($path)) {
                    echo '{"status":"success","message":"File Removed"}';
                } else {
                    echo '{"status":"error","message":"Failed To Remove File"}';
                }                
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'removeLocalDirectory':
            if (isset($_GET['path'])) {
                $path = "../../workspace/" . $_GET['path'];
                if (removeLocalDirectory($path)) {
                    echo '{"status":"success","message":"Directory Removed"}';
                } else {
                    echo '{"status":"error","message":"Failed To Remove Directory"}';
                }                
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'removeServerFile':
            if (isset($_GET['path'])) {
                $ftp = new ftp_client();
                echo $ftp->removeServerFile($_GET['path']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'removeServerDirectory':
            if (isset($_GET['path'])) {
                $ftp = new ftp_client();
                echo $ftp->removeServerDirectory($_GET['path']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'changeServerFileMode':
            if (isset($_GET['path']) && isset($_GET['mode'])) {
                $ftp = new ftp_client();
                echo $ftp->changeServerFileMode($_GET['path'], $_GET['mode']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'renameLocal':
            if (isset($_GET['path']) && isset($_GET['old']) && isset($_GET['new'])) {
                $path = "../../workspace/".$_GET['path'];
                if (rename($path."/".$_GET['old'], $path."/".$_GET['new'])) {
                    echo '{"status":"success","message":"Successfully Renamed"}';
                } else {
                    echo '{"status":"error","message":"Failed To Rename"}';
                }
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'renameServer':
            if (isset($_GET['path']) && isset($_GET['old']) && isset($_GET['new'])) {
                $ftp = new ftp_client();
                echo $ftp->rename($_GET['path'], $_GET['old'], $_GET['new']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        default:
            echo '{"status":"error","message":"No Type"}';
            break;
    }
    
    function removeLocalDirectory($dir) {
        set_time_limit(0);
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            if (is_dir("$dir/$file")) {
                removeLocalDirectory("$dir/$file");
            } else {
                unlink("$dir/$file");
            }
        }
        return rmdir($dir);
    }
?>
<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See [root]/license.md for more information. This information must remain intact.
 */
    error_reporting(0);
    
    require_once('../../common.php');
    require_once('class.ftp.php');
    require_once('class.scp.php');
    require_once('class.transfer.php');
    
    checkSession();
    set_time_limit(0);
    
    switch($_GET['action']) {
        
        case 'setMode':
            if (isset($_GET['mode'])) {
                if (isset($_SESSION['transfer_type'])) {
                    echo '{"status":"error","message":"Mode already saved"}';
                } else {
                    $_SESSION['transfer_type'] = $_GET['mode'];
                    echo '{"status":"success","message":"Mode saved"}';
                }
            } else {
                echo '{"status":"error","message":"Missing Parameter"}';
            }
            break;
        
        case 'connect':
            if (isset($_POST['host']) && isset($_POST['user']) && isset($_POST['password']) && isset($_POST['port'])) {
                transfer_controller::startConnection($_POST['host'], $_POST['user'], $_POST['password'], $_POST['port']);
                echo '{"status":"success","message":"Connection started"}';
            } else {
                echo '{"status":"error","message":"Missing Parameter"}';
            }
            break;
        
        case 'disconnect':
            echo transfer_controller::stopConnection();
            unset($_SESSION['transfer_type']);
            break;
        
        case 'getServerFiles':
            error_reporting(0);
            if (isset($_GET['path'])) {
                $path = $_GET['path'];
            } else {
                $path = "/";
            }
            echo transfer_controller::getServerFiles($path);
            break;
            
        case 'getSeverDirectory':
            echo transfer_controller::getSeverDirectory();
            break;
        
        case 'transferFileToServer':
            if (isset($_GET['cPath']) && isset($_GET['sPath']) && isset($_GET['fName'])  && isset($_GET['mode'])) {
                $path = transfer_controller::getWorkspacePath($_GET['cPath']);
                echo transfer_controller::transferFileToServer($path, $_GET['sPath'], $_GET['fName'], $_GET['mode']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'transferFileToClient':
            if (isset($_GET['cPath']) && isset($_GET['sPath']) && isset($_GET['fName'])  && isset($_GET['mode'])) {
                $path = transfer_controller::getWorkspacePath($_GET['cPath']);
                echo transfer_controller::transferFileToClient($path, $_GET['sPath'], $_GET['fName'], $_GET['mode']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'createLocalDirectory':
            if (isset($_GET['path'])) {
                $path = transfer_controller::getWorkspacePath($_GET['path']);
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
                echo transfer_controller::createServerDirectory($_GET['path']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        //action=remove"+data+type+"&path="+path
        case 'removeLocalFile':
            if (isset($_GET['path'])) {
                $path = transfer_controller::getWorkspacePath($_GET['path']);
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
                $path = transfer_controller::getWorkspacePath($_GET['path']);
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
                echo transfer_controller::removeServerFile($_GET['path']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'removeServerDirectory':
            if (isset($_GET['path'])) {
                echo transfer_controller::removeServerDirectory($_GET['path']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'changeLocalFileMode':
            if (isset($_GET['path']) && isset($_GET['mode'])) {
                $path = transfer_controller::getWorkspacePath($_GET['path']);
                $mode = $_GET['mode'];
                if ($mode[0] != '0') {
                    $mode = '0'.$mode;
                }
                $mode   = intval($mode, 8);
                if (chmod($path, $mode)) {
                    echo '{"status":"success","message":"Permissions Changed"}';
                } else {
                    echo '{"status":"error","message":"Failed To Change Permissions"}';
                }
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'changeServerFileMode':
            if (isset($_GET['path']) && isset($_GET['mode'])) {
                echo transfer_controller::changeServerFileMode($_GET['path'], $_GET['mode']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'renameLocal':
            if (isset($_GET['path']) && isset($_GET['old']) && isset($_GET['new'])) {
                $path = transfer_controller::getWorkspacePath($_GET['path']);
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
                echo transfer_controller::rename($_GET['path'], $_GET['old'], $_GET['new']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
        
        case 'changeServerGroup':
            if (isset($_GET['path']) && isset($_GET['grpName'])) {
                echo transfer_controller::changeServerGroup($_GET['path'], $_GET['grpName']);
            } else {
                echo '{"status":"error","message":"Missing Parameter!"}';
            }
            break;
            
        case 'getLocalInfo':
            if (isset($_POST['files'])) {
                echo getLocalInfo(json_decode($_POST['files']));
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
    
    //////////////////////////////////////////////////////
    //
    //  Get local information
    //
    //  Parameters:
    //
    //  $files - {Array} - Paths of the files
    //
    //////////////////////////////////////////////////////
    function getLocalInfo($files) {
        $result = array();
        $info   = array();
        $resInfo= array();
        $result['status'] = "success";
        foreach($files as $path) {
            $path = transfer_controller::getWorkspacePath($path);
            $info['name'] = basename($path);
            $size = filesize($path);
            if ($size === false) {
                $result['status'] = "error";
                $result['message'] = "size";
            } else {
                $info['size'] = $size;
            }
            if (is_dir($path)) {
                $info['type'] = "directory";
            } else {
                $info['type'] = "file";
            }
            $date = filemtime($path);
            if ($date === false) {
                $result['status'] = "error";
                $result['message'] = "date";
            } else {
                $info['date'] = date("d F - H:i", $date);
            }
            $perm = fileperms($path);
            if ($perm === false) {
                $result['status'] = "error";
                $result['message'] = "permissions";
            } else {
                $info['permissions'] = getFullPermissions($perm);
            }
            $owner = fileowner($path);
            if ($owner === false) {
                $result['status'] = "error";
                $result['message'] = "owner";
            } else {
                $owner          = posix_getpwuid($owner);
                $info['owner']  = $owner['name'];
            }
            $grp = filegroup($path);
            if ($grp === false) {
                $result['status'] = "error";
            } else {
                $grp            = posix_getgrgid($grp);
                $info['group']  = $grp['name'];
            }
            array_push($resInfo, $info);
        }
        if ($result['status'] == "success") {
            $result['info'] = $resInfo;
        } else {
            $result['message'] = "Failed To Get Information";
        }
        return json_encode($result);
    }
    
    function getFullPermissions($perms) {
        //http://de3.php.net/manual/en/function.fileperms.php
        if (($perms & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            // Symbolischer Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regulär
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Verzeichnis
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }
        
        // Besitzer
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
                    (($perms & 0x0800) ? 's' : 'x' ) :
                    (($perms & 0x0800) ? 'S' : '-'));
        
        // Gruppe
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
                    (($perms & 0x0400) ? 's' : 'x' ) :
                    (($perms & 0x0400) ? 'S' : '-'));
        
        // Andere
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
                    (($perms & 0x0200) ? 't' : 'x' ) :
                    (($perms & 0x0200) ? 'T' : '-'));
        return $info;
    }
?>
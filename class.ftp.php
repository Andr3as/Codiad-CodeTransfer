<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See [root]/license.md for more information. This information must remain intact.
 */

    class ftp_client {
        
        private $id;
        
        /////////////////////////////////////////////////////////////////////////
        //  Public methods
        /////////////////////////////////////////////////////////////////////////
        public function startConnection($host, $user, $pass, $port) {
            $_SESSION['ftp_host']   = $host;
            $_SESSION['ftp_user']   = $user;
            $_SESSION['ftp_pass']   = $pass;
            $_SESSION['ftp_port']   = $port;
            $this->connect();
            $this->disconnect();
        }
        
        public function stopConnection() {
            unset($_SESSION['ftp_host']);
            unset($_SESSION['ftp_user']);
            unset($_SESSION['ftp_pass']);
            unset($_SESSION['ftp_port']);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remote server index (Returns a list of files and directorys on the 
        //      remote server as json) For more information: see parseRawList();
        /////////////////////////////////////////////////////////////////////////
        public function getServerFiles($path) {
            set_time_limit(0);
            $this->connect();
            $array  = array();
            if (isset($this->id)) {
                //Get index
                $id = $this->id;
                if (ftp_chdir($id, $path) === false) {
                    $this->getError("Impossible to Change Directory");
                } else {
                    $raw    = ftp_rawlist($id, "-al .");
                    $parsed = $this->parseRawList($raw);
                    //Correct style
                    $dirs   = array();
                    $files  = array();
                    for ($i = 0; $i < count($parsed); $i++) {
                        //Change Name
                        $parsed[$i]['fName'] = $parsed[$i]['name'];
                        if ($path == "/") {
                            $parsed[$i]['name'] = $path . $parsed[$i]['name'];
                        } else {
                            $parsed[$i]['name'] = $path ."/". $parsed[$i]['name'];
                        }
                        //Edit type
                        $type = $parsed[$i]['type'];
                        if ($type == 'd') {
                            $parsed[$i]['type'] = "directory";
                        } else if ($type == 'l') {
                            if ($this->ftp_isdir($parsed[$i]['name'])) {
                                //Is directory
                                $parsed[$i]['type'] = "directory";
                            } else {
                                //Is file
                                $parsed[$i]['type'] = "file";
                            }
                        } else if ($type == '-') {
                            $parsed[$i]['type'] = "file";
                        } else {
                            $parsed[$i]['type'] = "error";
                        }
                        //Sort entries I
                        if ($parsed[$i]['type'] == "directory") {
                            array_push($dirs, $parsed[$i]);
                        } else {
                            array_push($files, $parsed[$i]);
                        }
                    }
                    //Sort entries II
                    usort($dirs, "transfer_controller::mySort");
                    usort($files, "transfer_controller::mySort");
                    $parsed = array_merge($dirs, $files);
                    //Result
                    $array['status']    = 'success';
                    $array['files']     = $parsed;
                }
            } else {
                //Error
                $this->getError("No Connection ID");
            }
            $this->disconnect();
            return json_encode($array);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer a file to remote server
        /////////////////////////////////////////////////////////////////////////
        public function transferFileToServer($cPath, $sPath, $fName, $mode) {
            set_time_limit(0);
            $this->connect();
            $msg    = array();
            if (isset($this->id)) {
                if (!ftp_chdir($this->id, $sPath)) {
                    //Create Directory
					if (ftp_mkdir($this->id, $sPath) === false) {
						//Error
						$msg = $this->getError("Failed To Create Directory");
					}
				} else {
					$mode = $this->getTransferMode($mode);
                    if (is_dir($cPath)) {
                        $result = $this->putDirectory($cPath, $sPath."/".$fName, $mode);
                    } else {
                        $result = ftp_put($this->id, $fName, $cPath, $mode);
                    }
                    
                    if ($result) {
                        $msg['status']  = 'success';
                        $msg['message'] = $fName.' uploaded';
                    } else {
						//Error
						$msg = $this->getError("Failed to upload ".$fName);
					}
				}
			} else {
                $msg = $this->getError("No Connection ID");
			}
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer a file to Codiad Server
        /////////////////////////////////////////////////////////////////////////
        public function transferFileToClient($cPath, $sPath, $fName, $mode) {
            set_time_limit(0);
            $this->connect();
            $msg    = array();
            if (isset($this->id)) {
                if (!ftp_chdir($this->id, $sPath)) {
                    //Directory doesn't exist
                    $msg = $this->getError("Server Directory Doesn't Exist");
				} else {
					$mode = $this->getTransferMode($mode);
                    
                    if ($this->ftp_isdir($sPath."/".$fName)) {
                        $result = $this->getDirectory($cPath, $sPath, $fName, $mode);
                    } else {
                        $result = ftp_get($this->id, $cPath, $fName, $mode);
                    }
                    
					if ($result) {
						$msg['status']  = 'success';
                        $msg['message'] = $fName.' downloaded';
					} else {
						//Error
						$msg = $this->getError("Failed to download ".$fName);
					}
				}
			} else {
                $msg = $this->getError("No Connection ID");
			}
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Create directory on remote server
        /////////////////////////////////////////////////////////////////////////
        public function createServerDirectory($path) {
            $this->connect();
            $msg = array();
            $pos = strrpos($path, "/") + 1;
            $name = substr($path, $pos, strlen($path));
            $path = substr($path, 0, $pos-1);
            if (!ftp_chdir($this->id, $path)) {
                //Directory doesn't exist
                $msg = $this->getError("Server Directory Doesn't Exist");
            } else {
                if (ftp_mkdir($this->id, $name) === false) {
                    //Error
                    $msg = $this->getError("Failed To Create Directory");
                } else {
                    $msg['status']  = 'success';
                    $msg['message'] = 'Directory Created';
                }
            }
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Get current directory name
        /////////////////////////////////////////////////////////////////////////
        public function getSeverDirectory() {
            $this->connect();
            $array  = array();
            $pwd    = ftp_pwd($this->id);
            if ($pwd === false) {
                $array = $this->getError('Impossible to Get Directory');
            } else {
                $array['status'] = "success";
                $array['dir']   = $pwd;
            }
            $this->disconnect();
            return json_encode($array);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remove file on remote server
        /////////////////////////////////////////////////////////////////////////
        public function removeServerFile($path) {
            set_time_limit(0);
            $this->connect();
            $msg = array();
            if (ftp_delete($this->id, $path)) {
                $msg['status']  = "success";
                $msg['message'] = "File Removed";
            } else {
                $msg = $this->getError("Failed To Delete File");
            }
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remove directory on remote server
        /////////////////////////////////////////////////////////////////////////
        public function removeServerDirectory($path) {
            set_time_limit(0);
            $this->connect();
            $msg = array();
            if ($this->removeServerTree($path)) {
                $msg['status']  = "success";
                $msg['message'] = "Directory Removed";
            } else {
                $msg = $this->getError("Failed To Delete Directory");
            }
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Change permissions of file on remote server
        /////////////////////////////////////////////////////////////////////////
        public function changeServerFileMode($path, $mode) {
            $this->connect();
            $msg    = array();
            if ($mode[0] != '0') {
                $mode = '0'.$mode;
            }
            $mode   = intval($mode, 8);
            if (ftp_chmod($this->id, $mode, $path) !== false) {
                $msg['status']  = "success";
                $msg['message'] = "Permissions Changed";
            } else {
                $msg = $this->getError("Failed To Change Permissions");
            }
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Rename directory or file
        /////////////////////////////////////////////////////////////////////////
        public function rename($path, $old, $new) {
            $this->connect();
            $msg = array();
            if (!ftp_chdir($this->id, $path)) {
                //Directory doesn't exist
                $msg = $this->getError("Server Directory Doesn't Exist");
            } else {
                if (ftp_rename($this->id, $old, $new)) {
                    $msg['status']  = "success";
                    $msg['message'] = "Successfully Renamed";
                } else {
                    $msg = $this->getError("Failed To Rename");
                }
            }
            
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //
        //  Private methods
        //
        /////////////////////////////////////////////////////////////////////////
        
        /////////////////////////////////////////////////////////////////////////
        //  Connect remote server
        /////////////////////////////////////////////////////////////////////////
        private function connect() {
            $connection_id = ftp_connect($_SESSION['ftp_host'], $_SESSION['ftp_port']);
            if ($connection_id === false) {
                die('{"status":"error","message":"Connection failed! Wrong Host or Port?"}');
            }
            $login_result = ftp_login($connection_id, $_SESSION['ftp_user'], $_SESSION['ftp_pass']);
            if ($login_result === false) {
                die('{"status":"error","message":"Connection failed! Wrong Username or Password?"}');
            }
            $this->id = $connection_id;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Disconnect remote server
        /////////////////////////////////////////////////////////////////////////
        private function disconnect() {
            ftp_close($this->id);
            unset($this->id);
        }
        
        private function removeServerTree($path) {
            set_time_limit(0);
            if (!isset($this->id)) {
                return false;
            }
            $files = $this->parseRawList(ftp_rawlist($this->id, $path));
            foreach ($files as $file) {
                if ($file['type'] == 'd') {
                    $this->removeServerTree($path.'/'.$file['name']);
                } else {
                    ftp_delete($this->id, $path.'/'.$file['name']);
                }
            }
            return ftp_rmdir($this->id, $path);
        }
        
        private function getError($msg) {
            $error = array();
            $error['status'] = 'error';
            $error['message']= $msg;
            return $error;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Parse transfermode
        /////////////////////////////////////////////////////////////////////////
        private function getTransferMode($mode) {
            //Workaround
			if ($mode == "FTP_ASCII") {
				return FTP_ASCII;
			} else {
				return FTP_BINARY;
			}
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer directory to server
        /////////////////////////////////////////////////////////////////////////
        private function putDirectory($cPath, $sPath, $mode) {
            if (!ftp_chdir($this->id, $sPath)) {
                //Create Directory
                if (ftp_mkdir($this->id, $sPath) === false) {
                    return false;
                }
                ftp_chdir($this->id, $sPath);
            }
            $files  = scandir($cPath);
            foreach ($files as $file) {
                //filter . and ..
                if ($file != "." && $file != "..") {
                    //check if $file is a folder
                    if (is_dir($cPath."/".$file)) {
                        if (!$this->putDirectory($cPath."/".$file, $sPath."/".$file, $mode)) {
                            return false;
                        }
                    } else {
                        if (!ftp_put($this->id, $file, $cPath."/".$file, $mode)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer Directory to client
        /////////////////////////////////////////////////////////////////////////
        private function getDirectory($cPath, $sPath, $fName, $mode) {
            if (!file_exists($cPath)) {
                if (!mkdir($cPath)) {
					return false;
				}
			}
            if (!ftp_chdir($this->id, $sPath."/".$fName)) {
                return false;
            }
            $files = $this->parseRawList(ftp_rawlist($this->id, "-al ."));
            foreach ($files as $file) {
                //filter . and ..
                if ($file['name'] != "." && $file['name'] != "..") {
                    if ($this->ftp_isdir($sPath."/".$fName."/".$file['name'])) {
                        if (!$this->getDirectory($cPath."/".$file['name'], $sPath."/".$fName, $file['name'], $mode)) {
                            return false;
                        }
                    } else {
                        if (!ftp_get($this->id, $cPath."/".$file['name'], $file['name'], $mode)) {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  FTP-IsDir
        /////////////////////////////////////////////////////////////////////////
        private function ftp_isdir($path) {
            return is_dir('ftp://'.$_SESSION['ftp_user'].':'.$_SESSION['ftp_pass'].'@'.$_SESSION['ftp_host'].':'.$_SESSION['ftp_port'].$path);
        }
        
        private function parseRawList($rawList)
        {
            //@Do not touch - More: http://de3.php.net/manual/de/function.ftp-rawlist.php#110561
            $start = 2;
            $orderList = array("d", "l", "-");
            $typeCol = "type";
            $cols = array("permissions", "number", "owner", "group", "size", "month", "day", "time", "name");
           
            foreach($rawList as $key=>$value)
            {
                $parser = null;
                if($key >= $start) $parser = explode(" ", preg_replace('!\s+!', ' ', $value));
                if(isset($parser))
                {
                    foreach($parser as $key=>$item)
                    {
                        $parser[$cols[$key]] = $item;
                        unset($parser[$key]);
                    }
                    $parsedList[] = $parser;
                }
            }
            foreach($orderList as $order)
            {
                foreach($parsedList as $key=>$parsedItem) {
                    $type = substr(current($parsedItem), 0, 1);
                    if($type == $order) {
                        $parsedItem[$typeCol] = $type;
                        unset($parsedList[$key]);
                        $parsedList[] = $parsedItem;
                    }
                }
            }
            return array_values($parsedList);
        }
    }
?>
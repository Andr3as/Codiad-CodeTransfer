<?php
/*
 * Copyright (c) Codiad & Andr3as, distributed
 * as-is and without warranty under the MIT License. 
 * See [root]/license.md for more information. This information must remain intact.
 */

    class scp_client {
        
        private $id;
        
        /////////////////////////////////////////////////////////////////////////
        //  Public methods
        /////////////////////////////////////////////////////////////////////////
        public function startConnection($host, $user, $pass, $port) {
            $_SESSION['ssh2_host']   = $host;
            $_SESSION['ssh2_user']   = $user;
            $_SESSION['ssh2_pass']   = $pass;
            $_SESSION['ssh2_port']   = $port;
            $this->connect();
            $this->disconnect();
        }
        
        public function stopConnection() {
            unset($_SESSION['ssh2_host']);
            unset($_SESSION['ssh2_user']);
            unset($_SESSION['ssh2_pass']);
            unset($_SESSION['ssh2_port']);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Remote server index (Returns a list of files and directorys on the 
        //      remote server as json) For more information: see parseRawList();
        /////////////////////////////////////////////////////////////////////////
        public function getServerFiles($path) {
            $this->connect();
            $msg  = array();
            if (isset($this->id)) {
                //Get index
                if ($this->execCommand("cd ".$path) === false) {
                    $msg = $this->getError("Impossible to Change Directory");
                } else {
                    $raw    = $this->execCommand("ls -al ".$path."/");
                    $raw    = explode("\n", $raw);
                    $raw    = array_slice($raw, 1);
                    $parsed = $this->parseRawList($raw);
                    $parsed = array_slice($parsed, 1);
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
                            if ($this->scp_isdir($parsed[$i]['name'])) {
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
                    $msg['status']  = 'success';
                    $msg['files']   = $parsed;
                }
            } else {
                //Error
                $this->getError("No Connection ID");
            }
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer a file to remote server
        /////////////////////////////////////////////////////////////////////////
        public function transferFileToServer($cPath, $sPath, $fName) {
            set_time_limit(0);
            $this->connect();
            $msg    = array();
            if (isset($this->id)) {
                if ($this->execCommand("cd ".$sPath) === false) {
                    //Create Directory
                    if ($this->execCommand("mkdir ".$sPath) === false) {
						//Error
						$msg = $this->getError("Failed To Create Directory");
					}
				} else {
					if (is_dir($cPath)) {
                        $result = $this->putDirectory($cPath, $sPath."/".$fName);
					} else {
                        $result = ssh2_scp_send($this->id, $cPath, $sPath."/".$fName);
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
        public function transferFileToClient($cPath, $sPath, $fName) {
            set_time_limit(0);
            $this->connect();
            $msg    = array();
            if (isset($this->id)) {
                if ($this->execCommand("cd ".$sPath) === false) {
                    //Directory doesn't exist
                    $msg = $this->getError("Server Directory Doesn't Exist");
				} else {
                    if ($this->scp_isdir($sPath."/".$fName)) {
                        $result = $this->getDirectory($cPath, $sPath, $fName);
                    } else {
                        $result = ssh2_scp_recv($this->id, $sPath."/".$fName, $cPath);
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
            if ($this->execCommand("mkdir ".$path) === false) {
                //Error
                $msg = $this->getError("Failed To Create Directory");
            } else {
                $msg['status']  = 'success';
                $msg['message'] = 'Directory Created';
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
            $pwd    = $this->execCommand("cd");
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
            if ($this->execCommand("rm ".$path) !== false) {
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
            if ($this->execCommand("rm -R ".$path) !== false) {
                $msg['status']  = "success";
                $msg['message'] = "Directory Removed";
            } else {
                $msg = $this->getError("Failed To Delete Directory");
            }
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Change permissions of file or directory on remote server
        /////////////////////////////////////////////////////////////////////////
        public function changeServerFileMode($path, $mode) {
            $this->connect();
            $msg    = array();
            if ($this->execCommand("chmod -R ".$mode." ".$path) !== false) {
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
            if ($this->execCommand("mv ".$path."/".$old." ".$path."/".$new) !== false) {
                $msg['status']  = "success";
                $msg['message'] = "Successfully Renamed";
            } else {
                $msg = $this->getError("Failed To Rename");
            }
            
            $this->disconnect();
            return json_encode($msg);
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Rename directory or file
        /////////////////////////////////////////////////////////////////////////
        public function changeServerGroup($path, $name) {
            $this->connect();
            $msg    = array();
            if ($this->execCommand("chgrp -R $name $path") !== false) {
                $msg['status']  = "success";
                $msg['message'] = "Group Changed";
                $msg['res'] = $res;
            } else {
                $msg = $this->getError("Failed To Change Group");
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
            $connection_id = ssh2_connect($_SESSION['ssh2_host'], $_SESSION['ssh2_port']);
            if ($connection_id === false) {
                die('{"status":"error","message":"Connection failed! Wrong Host or Port?"}');
            }
            $login_result = ssh2_auth_password($connection_id, $_SESSION['ssh2_user'], $_SESSION['ssh2_pass']);
            if ($login_result === false) {
                die('{"status":"error","message":"Connection failed! Wrong Username or Password?"}');
            }
            $this->id = $connection_id;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Disconnect remote server
        /////////////////////////////////////////////////////////////////////////
        private function disconnect() {
            $this->execCommand("exit;");
            unset($this->id);
        }
        
        private function execCommand($cmd) {
            $stream = ssh2_exec($this->id, $cmd);
            $err    = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
            stream_set_blocking($err, true);
            stream_set_blocking($stream, true);
            $err = stream_get_contents($err);
            if ($err === "") {
                return stream_get_contents($stream);
            } else {
                return false;
            }            
        }
        
        private function getError($msg) {
            $error = array();
            $error['status'] = 'error';
            $error['message']= $msg;
            return $error;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  Transfer directory to server
        /////////////////////////////////////////////////////////////////////////
        private function putDirectory($cPath, $sPath) {
            if ($this->execCommand("cd ".$sPath) === false) {
                //Create Directory
                if ($this->execCommand("mkdir ".$sPath) === false) {
                    return false;
                }
            }
            $files  = scandir($cPath);
            foreach ($files as $file) {
                //filter . and ..
                if ($file != "." && $file != "..") {
                    //check if $file is a folder
                    if (is_dir($cPath."/".$file)) {
                        if (!$this->putDirectory($cPath."/".$file, $sPath."/".$file)) {
                            return false;
                        }
                    } else {
                        if (!ssh2_scp_send($this->id, $cPath."/".$file, $sPath."/".$file)) {
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
        private function getDirectory($cPath, $sPath, $fName) {
            if (!file_exists($cPath)) {
                if (!mkdir($cPath)) {
					return false;
				}
			}
            if ($this->execCommand("cd ".$sPath."/".$fName) === false) {
                return false;
            }
            $raw    = $this->execCommand("ls -al ".$sPath."/".$fName."/");
            $raw    = explode("\n", $raw);
            $raw    = array_slice($raw, 1);
            $files  = $this->parseRawList($raw);
            $files  = array_slice($files, 1);
            foreach ($files as $file) {
                //filter . and ..
                if ($file['name'] != "." && $file['name'] != "..") {
                    if ($this->scp_isdir($sPath."/".$fName."/".$file['name'])) {
                        if (!$this->getDirectory($cPath."/".$file['name'], $sPath."/".$fName, $file['name'])) {
                            return false;
                        }
                    } else {
                        if (!ssh2_scp_recv($this->id, $sPath."/".$fName."/".$file['name'], $cPath."/".$file['name']))
                        {
                            return false;
                        }
                    }
                }
            }
            return true;
        }
        
        /////////////////////////////////////////////////////////////////////////
        //  SCP-IsDir
        /////////////////////////////////////////////////////////////////////////
        private function scp_isdir($path) {
            if ($this->execCommand("ls -al ".$path."/") === false) {
                return false;
            } else {
                return true;
            }
        }
        
        private function parseRawList($rawList)
        {
            //@Do not touch - More: http://de3.php.net/manual/de/function.ftp-rawlist.php#110561
            //@Do not touch - 26.07.2013 - Andr3as
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
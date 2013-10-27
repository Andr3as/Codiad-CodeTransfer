/*
* Copyright (c) Codiad & Andr3as, distributed
* as-is and without warranty under the MIT License.
* See [root]/license.md for more information. This information must remain intact.
*/

(function(global, $){
    
    var codiad = global.codiad,
        scripts = document.getElementsByTagName('script'),
        path = scripts[scripts.length-1].src.split('?')[0],
        curpath = path.split('/').slice(0, -1).join('/')+'/';

    $(function() {
    codiad.CodeTransfer.init();
    });

codiad.CodeTransfer = {
        
        path        : curpath,
        controller  : curpath + "controller.php",
        cBase       : "",
        cDir        : "",
        sBase       : "/",
        sDir        : "/",
        localSel    : [],
        serverSel   : [],
        mode        : "ftp",
        hided       : false,
        
        init: function() {
        },
        
        showSwitchDialog: function() {
            if (this.hided) {
                this.showDialog(this.mode);
            } else {
                codiad.modal.load(300,this.path+'dialog.php?action=switch');
            }
        },
        
        setMode: function(mode) {
            this.mode = mode;
            $.getJSON(this.controller+"?action=setMode&mode="+mode, function(data){
                if (data.status == "error") {
                    alert(data.message);
                }
            });
        },
        
        showDialog: function(mode) {
            var _this     = this;
            codiad.modal.load(1000,this.path+'dialog.php?action='+mode);
            $('#transfer_form').ready(function(){
                //Hide Loading
                _this.hideLoadingAnimation();
                //List Local Files
                _this.cBase     = $('#project-root').attr('data-path');
                _this.cDir      = _this.cBase;
                _this.updateLocalFiles(_this.cDir);
                $('#close-handle').click(function() {
                    _this.closeDialog();
                });
                if (_this.hided) {
                    _this.updateServerFiles(_this.sDir);
                } else {
                    _this.setMode(mode);
                }
            });
        },
        
        closeDialog: function() {
            //Disconnect ftp connection
            this.disconnect();
            codiad.modal.unload();
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Add an Entry to the Log
		//
		//  Parameters:
		//
		//  entry - {String} - String or HTML-Code (Table) to add to the Log
		//
		//////////////////////////////////////////////////////////
        addLogEntry: function(entry) {
            var last = $('#transfer_log').html().replace("<tbody>", "").replace("</tbody>", "");
            entry = "<tr><td>" + entry + "</td></tr>" + last;
            $('#transfer_log').html(entry);
        },
        
        //////////////////////////////////////////////////////////
        //
		//  Add files and directory to the lists
		//
		//  Parameters:
		//
		//  index - {Array} - Index of the directory: [0] {name,type}
        //  id - {String} - Selector of the list to update it
        //  dot - {String} - Path of the current directory
		//
		//////////////////////////////////////////////////////////
        updateList: function(index, id, dot) {
            //Add upper dir
            dot = this.getParentDir(dot);
            if (dot === "") {
                dot = "/";
            }
            var insert = '<li class="directory open" data-path="'+dot+'" data-type="directory">..</li>';
            if (index !== null) {
                var buf, name, path, icon, type, ext, mode;
                for (var i = 0; i < index.length; i++) {
                    buf  = index[i].name;
                    name = buf.substring(buf.lastIndexOf("/")+1);
                    path = buf;
                    icon, type;
                    if (index[i].type == "file") {
                        ext  = name.substring(name.lastIndexOf(".")+1);
                        icon = "file ext-"+ext;
                        type = "file";
                    } else {
                        icon = "directory close";
                        type = "directory";
                    }
                    insert += '<li class="'+icon+'" data-path="'+path+'" data-type="'+type+'">'+name+'</li>';
                }
            }
            
            $(id).html(insert);
            if ("#transfer_localList" == id) {
                this.updateLocalClick();
                $('#local_path').text(this.cDir);
            } else if ("#transfer_serverList" == id) {
                this.updateServerClick();
                $('#server_path').text(this.sDir);
            }
            //Set max-height absolute
            $('.file_list_div').css('max-height', function(){
                return 0.4*window.innerHeight + "px";
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Update the displayed files of the remote server
		//
		//  Parameters:
		//
		//  path - {String} - Path of the current directory
		//
		//////////////////////////////////////////////////////////
        updateServerFiles: function(path) {
            var _this = this;
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=getServerFiles&path="+path, function(data) {
                _this.hideLoadingAnimation();
                if (data.status == 'error') {
                    _this.addLogEntry(data.message);
                } else {
                    _this.updateList(data.files, "#transfer_serverList", _this.sDir);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Update the displayed files of the Codiad server
        //
		//  Parameters:
		//
		//  path - {String} - Path of the current Directory
		//
		//////////////////////////////////////////////////////////
        updateLocalFiles: function(path) {
            var _this = this;
            _this.showLoadingAnimation();
            $.getJSON("components/filemanager/controller.php?action=index&path="+path, 
                function(data) {
                    _this.hideLoadingAnimation();
                    codiad.filemanager.rescan(_this.cBase);
                    _this.updateList(data.data.index, '#transfer_localList', _this.cDir);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Transfer file to remote server
        //
		//  Parameters:
		//
		//  cPath - {String} - Path of the file on the Codiad server with filename
        //  sPath - {String} - Directory on the remote server without filename
        //  fName - {String} - Name of the file
        //  mode - {String} - FTP-Transfermode / either FTP_ASCII or FTP_BINARY
		//
		//////////////////////////////////////////////////////////
        transferFileToServer: function(cPath, sPath, file, mode) {
            var _this = this;
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=transferFileToServer&cPath="+cPath+"&sPath="+sPath+"&fName="+file+"&path="+this.sDir+"&mode="+mode,
                function(data) {
                    _this.hideLoadingAnimation();
                    if (data.status != 'error') {
                        _this.updateServerFiles(_this.sDir);
                    }
                    _this.addLogEntry(data.message);
                });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Transfer file to Codiad Server
        //
        //  Parameters:
		//
		//  cPath - {String} - Path of the file on the Codiad server with filename
        //  sPath - {String} - Directory on the remote server without filename
        //  fName - {String} - Name of the file
        //  mode - {String} - FTP-Transfermode / either FTP_ASCII or FTP_BINARY
        //
		//////////////////////////////////////////////////////////
        transferFileToClient: function(cPath, sPath, file, mode) {
            var _this = this;
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=transferFileToClient&cPath="+cPath+"&sPath="+sPath+"&fName="+file+"&path="+this.sDir+"&mode="+mode,
                function(data) {
                    _this.hideLoadingAnimation();
                    if (data.status != 'error') {
                        _this.updateLocalFiles(_this.cDir);
                        codiad.filemanager.rescan(_this.cBase);
                    }
                    _this.addLogEntry(data.message);
                });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Update click listener of filelist of the Codiad server
        //
		//////////////////////////////////////////////////////////
        updateLocalClick: function() {
            var _this = this;
            //Remove old selections
            $('#transfer_localList li').removeClass("selected");
            this.localSel = [];
            //Add new listener
            $('#transfer_localList li').click(function(e){
                _this.handleSelection(this, e, 'localSel', '#transfer_localList li');
            });
            $('#transfer_localList li').dblclick(function(){
                var path = $(this).attr('data-path');
                if ($(this).attr('data-type') == 'directory') {
                    //Open dir
                    if (path == "/") {
                        path = path.replace("/", _this.cBase);
                    }
                    _this.cDir = path;
                    _this.updateLocalFiles(path);
                    _this.addLogEntry('Directory Changed');
                } else {
                    //Transfer file
                    var file = $(this).text();
                    var mode = $('#transfer_mode').val();
                    _this.transferFileToServer(path, _this.sDir, file, mode);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Update click listener of filelist of the remote server
        //
        //////////////////////////////////////////////////////////
        updateServerClick: function() {
            var _this = this;
            //Remove old selections
            $('#transfer_serverList li').removeClass("selected");
            this.serverSel = [];
            //Add new listener
            $('#transfer_serverList li').click(function(e){
                _this.handleSelection(this, e, 'serverSel', '#transfer_serverList li');
            });
            $('#transfer_serverList li').dblclick(function(){
                var path = $(this).attr('data-path');
                if ($(this).attr('data-type') == 'directory') {
                    //Open dir
                    if (path === "") {
                        path = "/";
                    } 
                    _this.sDir = path;
                    _this.updateServerFiles(path);
                    _this.addLogEntry('Directory changed');
                } else {
                    var file = $(this).text();
                    var mode = $('#transfer_mode').val();
                    _this.transferFileToClient(_this.cDir+'/'+file, _this.sDir, file, mode);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Handle click event of selection
        //
        //  Parameters:
        //
        //  item - {jQuery Object} - Clicked dom element
        //  e - {jQuery eventObject} - EvenObject of the jQuery 
        //                                  click handler
        //  selArr - {String} - Name of the array which contains 
        //                          all selected elements
        //                          serverSel or localSel
        //  listSel - {String} - jQuery file list item selector
        //                          "transfer_localList li" or
        //                          "transfer_serverList li"
        //
        //////////////////////////////////////////////////////////
        handleSelection: function(item, e, selArr, listSel) {
            //Catch ..
            if ($(item).text == "..") {
                return;
            }
            //Unselect if already selected
            if ($(item).hasClass("selected")) {
                $(item).removeClass("selected");
                //Check if multiselection
                if (this[selArr].length > 1) {
                    //Multiselection
                    if (e.shiftKey) {
                        //Shift pressed -> keep other selected, remove only item one
                        var index = this[selArr].indexOf(item);
                        this[selArr] = this[selArr].splice(index, 1);
                    } else {
                        //Remove all selected, keep only item
                        $(listSel).removeClass("selected");
                        $(item).addClass("selected");
                        this[selArr][0] = $.makeArray(item);
                    }
                } else {
                    this[selArr] = [];
                    return;
                }
                return;
            }
            //Remove old selections
            $(listSel).removeClass("selected");
            //Shift pressed
            if (e.shiftKey) {
                if (this[selArr] == []) {
                    this[selArr][0] = $.makeArray(item);
                } else {
                    this[selArr].push($.makeArray(item));
                }
            } else {
                //New single click
                this[selArr]      = [];
                this[selArr][0]   = $.makeArray(item);
            }
            //Mark selection
            var arr;
            for (var i = 0; i < this[selArr].length; i++) {
                $(this[selArr][i].reverse()).addClass("selected");
            }
            $(item).addClass("selected");
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Create folder
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains 
        //                      all selected elements
        //                      serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        createFolder: function(selArr) {
            var _this   = this;
            var name    = prompt("Directory:");
            var path    = "";
            if (name === null) {
                return false;
            }
            if ((this[selArr].length == 1) && ($(this[selArr][0].reverse()).attr('data-type') == 'directory') ) {
                //Only one selection
                if ($(this[selArr][0].reverse()).text() == "..") {
                    //Create Directory in upper Directory
                    alert("Not Allowed!");
                    return;
                }
                path = $(this[selArr][0].reverse()).attr('data-path');
            } else {
                if (selArr == "localSel") {
                    path = this.cDir;
                } else {
                    path = this.sDir;
                }
            }
            //Create folder in the current dir
            selArr = selArr.replace("Sel", "");
            selArr = selArr.substring(0,1).toUpperCase() + selArr.substring(1);
            if (path == "/") {
                path = path + name;
            } else {
                path = path + "/" + name;
            }
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=create"+selArr+"Directory&path="+path, function(data){
                _this.hideLoadingAnimation();
                if (data.status != "error") {
                    if (selArr == "Local") {
                        _this.updateLocalFiles(_this.cDir);
                        codiad.filemanager.rescan(_this.cBase);
                    } else {
                        _this.updateServerFiles(_this.sDir);
                    }
                }
                _this.addLogEntry(data.message);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Delete all selected elements
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        deleteSel: function(selArr) {
            var result = confirm('Really Delete?');
            if (result) {
                for (var i = 0; i < this[selArr].length; i++) {
                    this.deleteObject(this[selArr][i], selArr);
                }
                //Delete Selection
                this.unselect(selArr);
            }
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Delete object on Codiad or remote server
        //      either file or directory
        //
        //  Parameters:
        //
        //  obj - {Array of a jQuery object} - Array of the clicked
        //                                      dom element
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        deleteObject: function(obj, selArr) {
            var _this   = this;
            selArr      = selArr.replace("Sel", "");
            selArr      = selArr.substring(0,1).toUpperCase() + selArr.substring(1);
            var path    = $(obj.reverse()).attr('data-path');
            var type    = $(obj.reverse()).attr('data-type');
            type        = type.substring(0,1).toUpperCase() + type.substring(1);
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=remove"+selArr+type+"&path="+path, function(data){
                _this.hideLoadingAnimation();
                _this.addLogEntry(data.message);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Display info of selected elements on the remote server
        //
        //////////////////////////////////////////////////////////
        serverInfo: function() {
            var _this   = this;
            var buf     = [];
            var obj, data;
            for (var i = 0; i < this.serverSel.length; i++) {
                obj     = this.serverSel[i].reverse();
                data    = $(obj).attr('data-path');
                if (buf == []) {
                    buf[0] = data;
                } else {
                    buf.push(data);
                }
            }
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=getServerFiles&path="+this.sDir, function(data) {
                _this.hideLoadingAnimation();
                var index   = data.files;
                var info    = [];
                var item    = [];
                var name;
                for (var j = 0; j < index.length; j++) {
                    if (buf.indexOf(index[j].name) != -1) {
                        name = index[j].name.substring(index[j].name.lastIndexOf("/")+1);
                        item.name = name;
                        item.type = index[j].type;
                        item.permissions = index[j].permissions;
                        item.owner = index[j].owner;
                        item.group = index[j].group;
                        item.size = index[j].size;
                        item.date = index[j].day +" "+ index[j].month + " - " + index[j].time;
                        if (info == []) {
                            info[0] = item;
                        } else {
                            info.push(item);
                        }
                        item = [];
                    }
                }
                _this.createInfoTable(info);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Display info of selected elements on the local server
        //
        //////////////////////////////////////////////////////////
        localInfo: function() {
            var _this = this;
            var buf = [];
            var obj, data, files;
            for (var i = 0; i < this.localSel.length; i++) {
                obj = this.localSel[i].reverse();
                data = $(obj).attr('data-path');
                if (buf == []) {
                    buf[0] = data;
                } else {
                    buf.push(data);
                }
            }
            files = JSON.stringify(buf);
            this.showLoadingAnimation();
            $.post(this.controller+"?action=getLocalInfo", { "files":files}, function(data){
                _this.hideLoadingAnimation();
                data = JSON.parse(data);
                if (data.status == "error") {
                    _this.addLogEntry(data.message);
                } else {
                    _this.createInfoTable(data.info);
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Create Table of informations
        //
        //  Parameters:
        //
        //  info - {Array} - File informations
        //                      name - file name
        //                      size - file size in bytes
        //                      type - type of the file (File or Directory)
        //                      date - Date of the last modification
        //                      permissions - Permissions of the file
        //                      owner - Owner of the file
        //                      group - Group of the file
        //
        //////////////////////////////////////////////////////////
        createInfoTable: function(info) {
            var alertText   = '<table><tr><td>Name</td><td><abbr title="Size in Bytes">Size</abbr></td>';
            alertText      += '<td>Type</td><td>Date</td><td>Permissions</td><td>Owner/Group</td></tr>';
            for (var i = 0; i < info.length; i++) {
                alertText += "<tr>";
                alertText += "<td>"+info[i].name+"</td>";
                alertText += "<td>"+info[i].size+"</td>";
                alertText += "<td>"+info[i].type+"</td>";
                alertText += "<td>"+info[i].date+"</td>";
                alertText += "<td>"+info[i].permissions+"</td>";
                alertText += "<td>"+info[i].owner+"/"+info[i].group+"</td>";
                alertText += "</tr>";
            }
            alertText += "</table>";
            this.addLogEntry(alertText);
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Change permissions of selection
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        fileModeSel: function(selArr) {
            var obj, path;
            var mode = prompt("Permissions: (Octal Value)");
            if (mode === null) {
                return false;
            }
            var type = selArr.replace("Sel", "");
            type     = type.substring(0,1).toUpperCase() + type.substring(1);
            for (var i = 0; i < this[selArr].length; i++) {
                obj = this[selArr][i].reverse();
                if (this.mode == "ftp") {
                    if ($(obj).attr('data-type') == 'file') {
                        path = $(obj).attr('data-path');
                        this.changeFileMode(path, type, mode);
                    }
                } else {
                    path = $(obj).attr('data-path');
                    this.changeFileMode(path, type, mode);
                }
                
            }
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Change permissions of file on remote server
        //
        //  Parameters:
        //
        //  path - {String} - Path of the file with filename
        //  type - {String} - Location of the file
        //                      either Server or Local
        //  mode - {String} - New permissions of the file as an
        //                      octal value
        //
        //////////////////////////////////////////////////////////
        changeFileMode: function(path, type, mode) {
            var _this = this;
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=change"+type+"FileMode&path="+path+"&mode="+mode, function(data) {
                _this.hideLoadingAnimation();
                _this.addLogEntry(data.message);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Rename selected files and directory
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        renameSel: function(selArr) {
            var obj, path, newName, old;
            var type    = selArr.replace("Sel", "");
            type        = type.substring(0,1).toUpperCase() + type.substring(1);
            for (var i = 0; i < this[selArr].length; i++) {
                obj = this[selArr][i].reverse();
                old = $(obj).text();
                newName = prompt("Rename:", old);
                if (newName !== null) {
                    path = $(obj).attr('data-path');
                    this.rename(path, type, newName);
                }
            }
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Rename file or directory local or on remote server
        //
        //  Parameters:
		//
		//  path - {String} - Path of the file with filename
        //  type - {String} - Location of the file
        //                      either Server or Local
        //  newName - {String} - New name of the file or directory
		//
		//////////////////////////////////////////////////////////
        rename: function(path, type, newName) {
            var _this = this;
            var old = path.substring(path.lastIndexOf("/")+1);
            path    = this.getParentDir(path);
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=rename"+type+"&path="+path+"&old="+old+"&new="+newName, function(data) {
                _this.hideLoadingAnimation();
                _this.addLogEntry(data.message);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Change group of selected files and directory
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        changeGroupSel: function(selArr) {
            var obj, path, newGroup, name;
            newGroup = prompt("Change group:");
            if (newGroup === null) {
                return false;
            }
            var type = selArr.replace("Sel", "");
            type     = type.substring(0,1).toUpperCase() + type.substring(1);
            for (var i = 0; i < this[selArr].length; i++) {
                obj = this[selArr][i].reverse();
                name = $(obj).text();
                path = $(obj).attr('data-path');
                this.changeGroup(path, type, newGroup);
            }
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Change group local or on remote server
        //
        //  Parameters:
        //
		//  path - {String} - Path of the file with filename
        //  type - {String} - Location of the file
        //                      either Server or Local
        //  newGroup - {String} - New name of the new group
		//
		//////////////////////////////////////////////////////////
        changeGroup: function(path, type, newGroup) {
            var _this   = this;
            this.showLoadingAnimation();
            $.getJSON(this.controller+"?action=change"+type+"Group&path="+path+"&grpName="+newGroup, function(data) {
                _this.hideLoadingAnimation();
                _this.addLogEntry(data.message);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Get current directory of remote server
        //
        //  Parameters:
        //
		//  isBase - {Boolean} - Set current directory as server
        //                          base directory
        //  update - {Boolean} - Update the value of the form
		//
		//////////////////////////////////////////////////////////
        getServerDirectory: function(isBase, update) {
            var _this = this;
            $.getJSON(this.controller+"?action=getSeverDirectory", function(data){
                if (data.status != 'error') {
                    _this.sDir = data.dir;
                    if (isBase) {
                        _this.sBase = data.dir;
                    }
                    if (update) {
                        $('#server_path').text(_this.sDir);
                    }
                }
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Transfer selection
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        transferSel: function(selArr) {
            var mode    = $('#transfer_mode').val();
            var source  = selArr.replace("Sel", "");
            var obj, path, file;
			for (var i = 0; i < this[selArr].length; i++) {
				obj = this[selArr][i].reverse();
				file = $(obj).text();
				path = $(obj).attr("data-path");
				if (source == "local") {
					//TransferFileToServer
                    this.transferFileToServer(path, this.sDir, file, mode);
				} else {
					//TransferFileToClient
                    this.transferFileToClient(this.cDir+"/"+file, this.sDir, file, mode);
				}
            }
            this.unselect(selArr);
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Delete selection
        //
        //  Parameters:
        //
        //  selArr - {String} - Name of the array which contains
        //                          all selected elements
        //                          serverSel or localSel
        //
        //////////////////////////////////////////////////////////
        unselect: function(selArr) {
            selList         = selArr.replace("Sel", "List");
            this[selArr]    = [];
            $('#transfer_'+selList+" li").removeClass("selected");
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Display loading circle
        //
        //////////////////////////////////////////////////////////
        showLoadingAnimation: function() {
            $('.drops').show();
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Hide loading circle
        //
        //////////////////////////////////////////////////////////
        hideLoadingAnimation: function() {
            $('.drops').hide();
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Get parent directory
        //
        //  Parameters:
        //
        //  path - {String} - Path of file or directory to get
        //                      parent directory
        //
        //////////////////////////////////////////////////////////
        getParentDir: function(path) {
            return path.substring(0,path.lastIndexOf("/"));
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Hide dialog
        //
        //////////////////////////////////////////////////////////
        hide: function() {
            this.hided = true;
            codiad.modal.unload();
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Connect to remote server
        //
        //////////////////////////////////////////////////////////
        connect: function() {
            var _this= this;
            var host = $('#transfer_host').val();
            var user = $('#transfer_user').val();
            var pass = $('#transfer_password').val();
            var port = $('#transfer_port').val();
            this.showLoadingAnimation();
            $.post(_this.controller+"?action=connect", {"host":host, "user":user, "password":pass, "port":port}, function(data) {
                _this.hideLoadingAnimation();
                data = $.parseJSON(data);
                if (data.status != "error") {
                    _this.updateServerFiles(_this.sDir);
                }
                _this.addLogEntry(data.message);
            });
        },
        
        //////////////////////////////////////////////////////////
        //
        //  Disconnect from remote server
        //
        //////////////////////////////////////////////////////////
        disconnect: function() {
            var _this = this;
            this.showLoadingAnimation();
            $.get(this.controller+"?action=disconnect", function() {
                _this.hideLoadingAnimation();
                _this.sDir  = "/";
                _this.sBase = "/";
                _this.hided = false;
            });
        }
    };
})(this, jQuery);

<!--
    Copyright (c) Codiad & Andr3as, distributed
	as-is and without warranty under the MIT License.
	See [root]/license.md for more information. This information must remain intact.
-->
<?php
    error_reporting(0);
    
    require_once('../../common.php');
    checkSession();
    
    switch($_GET['action']) {
        case 'switch':
            echo '
                <form>
                    <p>Choose transfer mode:</p>
                    <button onclick="codiad.CodeTransfer.showDialog(\'ftp\'); return false;">FTP</button>
                    <button onclick="codiad.CodeTransfer.showDialog(\'scp\'); return false;">SCP</button>
                    <button onclick="codiad.CodeTransfer.closeDialog(); return false;">Close</button>
                </form>
            ';
            break;
        
        case 'ftp':
        case 'scp':
            ?>
                <div id="transfer_form_div">
                    <form id="transfer_form">
                        <table>
                            <tr>
                                <td>Host:<input type="text" id="transfer_host"></td>
                                <td>User:<input type="text" id="transfer_user"></td>
                                <td>Password:<input type="password" id="transfer_password"></td>
                                <?php
                                    if ($_GET['action'] == 'ftp') {
                                        $mode = 21;
                                    } else {
                                        $mode = 22;
                                    }
                                    echo '<td id="transfer_port_td">Port:<input type="number" value="'.$mode.'" id="transfer_port"></td>';
                                ?>
                                <td id="transfer_connect_td">
                                    <button id="transfer_connect" onclick="codiad.CodeTransfer.connect(); return false;">Connect</button>
                                </td>
                                <?php
                                    if ($_GET['action'] == 'ftp') {
                                        echo '
                                            <td>Transfer Type<select id="transfer_mode">
                                                    <option value="FTP_ASCII">ASCII</option>
                                                    <option value="FTP_BINARY">Binary</option>
                                                </select>
                                            </td>
                                        ';
                                    }
                                ?>
                            </tr>
                        </table>
                        <hr>
                        <table id="transfer_list">
                            <tr>
                                <td>
                                    <i class="icon-folder" onclick='codiad.CodeTransfer.createFolder("localSel");' title="Create folder"></i>
                                    <i class="icon-trash" onclick='codiad.CodeTransfer.deleteSel("localSel");' title="Delete"></i>
                                    <i class="icon-info" onclick='codiad.CodeTransfer.localInfo();' title="Information"></i>
                                    <i class="icon-key" onclick='codiad.CodeTransfer.fileModeSel("localSel");' title="Change permissions"></i>
                                    <i class="icon-pencil" onclick='codiad.CodeTransfer.renameSel("localSel");' title="Rename"></i>
                                    <i class="icon-upload" onclick='codiad.CodeTransfer.transferSel("localSel");' title="Upload"></i>
                                    <i class="icon-arrows-ccw" onclick='codiad.CodeTransfer.updateLocalFiles(codiad.CodeTransfer.cDir);' title="Update"></i>
                                    Codiad Server: <span id="local_path"></span>
                                </td>
                                <td style="width: 12px;">
                                    <i class="icon-lock-open big-icon" id="ct_mirror_icon" style="margin-right: -10px;" onclick="codiad.CodeTransfer.mirror();" title="Mirror navigation"></i>
                                </td>
                                <td>
                                    <i class="icon-folder" onclick='codiad.CodeTransfer.createFolder("serverSel");' title="Create Folder"></i>
                                    <i class="icon-trash" onclick='codiad.CodeTransfer.deleteSel("serverSel");' title="Delete"></i>
                                    <i class="icon-info" onclick='codiad.CodeTransfer.serverInfo();' title="Information"></i>
                                    <i class="icon-key" onclick='codiad.CodeTransfer.fileModeSel("serverSel");' title="Change permissions"></i>
                                    <?php
                                        if ($_GET['action'] == 'scp') {
                                            echo '<i class="icon-users" onclick="codiad.CodeTransfer.changeGroupSel(\'serverSel\');" title="Change group"></i>';
                                        }
                                    ?>
                                    <i class="icon-pencil" onclick='codiad.CodeTransfer.renameSel("serverSel");' title="Rename"></i>
                                    <i class="icon-download" onclick='codiad.CodeTransfer.transferSel("serverSel")' title="Download"></i>
                                    <i class="icon-export" onclick='codiad.CodeTransfer.editSel("serverSel")' title="Edit locally"></i>
                                    <i class="icon-arrows-ccw" onclick='codiad.CodeTransfer.updateServerFiles(codiad.CodeTransfer.sDir);' title="Update"></i>
                                    <?php
                                        if ($_GET['action'] == "ftp") {
                                            echo "FTP Server: ";
                                        } else {
                                            echo "SSH Server: ";
                                        }
                                    ?>
                                    <span id="server_path"></span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="fileList"><div class="file_list_div"><ul id="transfer_localList"></ul></div></td>
                                <td class="fileList"><div class="file_list_div"><ul id="transfer_serverList"></ul></div></td>
                            </tr>
                        </table>
                        <hr>
                        <div class="loading">
                            <ul class="drops">
                                <li></li><li></li><li></li><li></li><li></li>
                            </ul>
                        </div>
                        <hr>
                        <h2>Log:</h2>
                        <div id="transfer_log_div">
                            <table id="transfer_log"></table>
                        </div>
                        <button onclick="codiad.CodeTransfer.hide(); return false;">Hide</button>
                        <button onclick="codiad.CodeTransfer.closeDialog(); return false;">Logout</button>
                    </form>
                </div>
            <?php
        break;
    }
?>
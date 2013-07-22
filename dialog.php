<!--
    Copyright (c) Codiad & Andr3as, distributed
	as-is and without warranty under the MIT License.
	See [root]/license.md for more information. This information must remain intact.
-->
<div id="ftp_form_div">
    <form id="ftp_form">
        <label>FTP-Client</label>
        <hr>
        <table>
            <tr>
                <td>Host:<input type="text" id="ftp_host"></td>
                <td>User:<input type="text" id="ftp_user"></td>
                <td>Password:<input type="password" id="ftp_password"></td>
                <td id="ftp_port_td">Port:<input type="number" value="21" id="ftp_port"></td>
                <td id="ftp_connect_td"><button id="ftp_connect" onclick="codiad.FTP_Client.connect(); return false;">Connect</button></td>
                <td>Transfer Type<select id="ftp_mode">
                        <option value="FTP_ASCII">ASCII</option>
                        <option value="FTP_BINARY">Binary</option>
                    </select>
                </td>
            </tr>
        </table>
        <hr>
        <table id="ftp_list">
            <tr>
                <td>
                    <i class="icon-folder" onclick='codiad.FTP_Client.createFolder("localSel");'></i>
                    <i class="icon-trash" onclick='codiad.FTP_Client.deleteSel("localSel");'></i>
                    <i class="icon-arrows-ccw" onclick="codiad.FTP_Client.updateLocalFiles(codiad.FTP_Client.cDir);"></i>
                    Codiad Server: <span id="local_path"></span>
                </td>
                <td>
                    <i class="icon-folder" onclick='codiad.FTP_Client.createFolder("serverSel");'></i>
                    <i class="icon-trash" onclick='codiad.FTP_Client.deleteSel("serverSel");'></i>
                    <i class="icon-info" onclick='codiad.FTP_Client.serverInfo();'></i>
                    <i class="icon-key" onclick='codiad.FTP_Client.serverFileMode();'></i>
                    <i class="icon-arrows-ccw" onclick="codiad.FTP_Client.updateServerFiles(codiad.FTP_Client.sDir);"></i>
                    FTP Server: <span id="server_path"></span>
                </td>
            </tr>
            <tr>
                <td class="fileList"><ul id="ftp_localList"></ul></td>
                <td class="fileList"><ul id="ftp_serverList"></ul></td>
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
        <div id="ftp_log_div">
            <table id="ftp_log"></table>
        </div>
        <button onclick="codiad.FTP_Client.closeDialog(); return false;">Close</button>
    </form>
</div>
<?php
include_once('database_class.php');
include_once('util_inc.php');
include_once('language.php');

class Documents {

	var $db;
	var $db2;
	var $tz_offset;
	var $cur_user_id;

	function Documents ($current_user_id, $type, $host, $database, $user, $pass) {
		$this->cur_user_id = $current_user_id;
		$this->db = new database($type, $host, $database, $user, $pass);
		$this->db2 = new database($type, $host, $database, $user, $pass);
		$this->db->query("SELECT `timezone` FROM `fcms_user_settings` WHERE `user` = $current_user_id") or die('<h1>Timezone Error (prayers_class.php 17)</h1>' . mysql_error());
		$row = $this->db->get_row();
		$this->tz_offset = $row['timezone'];
	}

	function showDocuments ($page = '1') {
		global $LANG;
		$from = (($page * 25) - 25); 
		$sql = "SELECT `id`, `name`, `description`, `user`, `date` FROM `fcms_documents` AS d ORDER BY `date` DESC LIMIT " . $from . ", 25";
		$this->db->query($sql) or displaySQLError('Get Documents Error', 'inc/documents_class.php [' . __LINE__ . ']', $sql, mysql_error());
		if ($this->db->count_rows() > 0) {
			echo "<script type=\"text/javascript\" src=\"inc/tablesort.js\"></script>\n";
			echo "\t\t\t<table id=\"docs\" class=\"sortable\">\n\t\t\t\t<thead>\n\t\t\t\t\t<tr><th class=\"sortfirstasc\">" . $LANG['docs_name'] . "</th><th>" . $LANG['docs_desc'] . "</th><th>" . $LANG['docs_user'] . "</th><th>" . $LANG['docs_date'] . "</th></tr>\n\t\t\t\t</thead>\n";
			echo "\t\t\t\t<tbody>\n";
			while($r = $this->db->get_row()) {
				$date = fixDST(gmdate('m/d/Y h:ia', strtotime($r['date'] . $this->tz_offset)), $this->cur_user_id, 'm/d/Y h:ia');
				echo "\t\t\t\t\t<tr><td><a href=\"?download=" . $r['name'] . "\">" . $r['name'] . "</a>";
				if (checkAccess($_SESSION['login_id']) < 3 || $_SESSION['login_id'] == $r['user']) {
					echo "&nbsp;<form method=\"post\" action=\"documents.php\"><div><input type=\"hidden\" name=\"id\" value=\"".$r['id']."\"/><input type=\"hidden\" name=\"name\" value=\"".$r['name']."\"/><input type=\"submit\" name=\"deldoc\" value=\" \" class=\"delbtn\" title=\"".$LANG['title_del_doc']."\"/></div></form>";
				}
				echo "</td><td>" . $r['description'] . "</td><td>" . getUserDisplayName($r['user']) . "</td><td>$date</td></tr>\n";
			}
			echo "\t\t\t\t</tbody>\n\t\t\t</table>\n";
			$sql = "SELECT count(`id`) AS c FROM `fcms_documents`";
			$this->db2->query($sql) or displaySQLError('Count Documents Error', 'inc/documents_class.php [' . __LINE__ . ']', $sql, mysql_error());
			while ($r = $this->db2->get_row()) { $docscount = $r['c']; }
			$total_pages = ceil($docscount / 25); 
			if ($total_pages > 1) {
				echo "<div class=\"pages clearfix\"><ul>"; 
				if ($page > 1) { 
					$prev = ($page - 1); 
					echo "<li><a title=\"".$LANG['title_first_page']."\" class=\"first\" href=\"documents.php?page=1\"></a></li>"; 
					echo "<li><a title=\"".$LANG['title_prev_page']."\" class=\"previous\" href=\"documents.php?page=$prev\"></a></li>"; 
				} 
				if ($total_pages > 8) {
					if($page > 2) {
						for($i = ($page-2); $i <= ($page+5); $i++) {
							if($i <= $total_pages) { echo "<li><a href=\"documents.php?page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; }
						} 
					} else {
						for($i = 1; $i <= 8; $i++) { echo "<li><a href=\"documents.php?page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>"; } 
					}
				} else {
					for($i = 1; $i <= $total_pages; $i++) {
						echo "<li><a href=\"documents.php?page=$i\"";  if($page == $i) { echo " class=\"current\""; } echo ">$i</a></li>";
					} 
				}
				if ($page < $total_pages) { 
					$next = ($page + 1); 
					echo "<li><a title=\"".$LANG['title_next_page']."\" class=\"next\" href=\"documents.php?page=$next\"></a></li>"; 
					echo "<li><a title=\"".$LANG['title_last_page']."\" class=\"last\" href=\"documents.php?page=$total_pages\"></a></li>"; 
				} 
				echo "</ul></div>";
			}
		} else {
			echo "<div class=\"info-alert\"><h2>" . $LANG['info_docs1'] . "</h2><p><i>" . $LANG['info_docs2'] . "</i></p><p>" . $LANG['info_docs3'] . " <a href=\"?adddoc=yes\">" . $LANG['info_docs4'] . "</a></p></div>";
		}
	}

	function displayForm () {
		global $LANG;
		echo "<script type=\"text/javascript\" src=\"inc/livevalidation.js\"></script>\n\t\t\t";
		echo "<form method=\"post\" enctype=\"multipart/form-data\" name=\"addform\" action=\"documents.php\">\n\t\t\t\t<h3>".$LANG['add_document']."</h3>\n\t\t\t\t";
		echo "<div><label for=\"doc\">" . $LANG['docs_name'] . "</label>: <input type=\"file\" name=\"doc\" id=\"doc\" size=\"30\" title=\"".$LANG['title_doc']."\"/></div><br/>\n\t\t\t\t";
		echo "<div><label for=\"desc\">" . $LANG['docs_desc'] . "</label>: <input type=\"text\" name=\"desc\" id=\"desc\" class=\"required\" size=\"60\" title=\"" . $LANG['title_desc_doc'] . "\"/></div>\n\t\t\t\t";
		echo "\t\t\t\t<script type=\"text/javascript\">\n\t\t\t\t\tvar fdesc = new LiveValidation('desc', { validMessage: \"".$LANG['lv_thanks']."\", wait: 500});\n\t\t\t\t\tfdesc.add(Validate.Presence, {failureMessage: \"".$LANG['lv_sorry_req']."\"});\n\t\t\t\t</script>\n\t\t\t\t";
		echo "<div><input type=\"submit\" name=\"submitadd\" value=\"".$LANG['submit']."\"/></div>";
		echo "</form>\n\t\t\t<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>";
	}

	function uploadDocument ($filetype, $filename, $filetmpname) {
		global $LANG;
		$known_photo_types = array('application/msword' => 'doc', 'text/plain' => 'txt', 'application/excel' => 'xsl', 'application/vnd.ms-excel' => 'xsl', 'application/x-msexcel' => 'xsl', 
			'application/x-compressed' => 'zip', 'application/x-zip-compressed' => 'zip', 'application/zip' => 'zip', 'multipart/x-zip' => 'zip', 'application/rtf' => 'rtf', 
			'application/x-rtf' => 'rtf', 'text/richtext' => 'rtf', 'application/mspowerpoint' => 'ppt', 'application/powerpoint' => 'ppt', 'application/vnd.ms-powerpoint' => 'ppt', 
			'application/x-mspowerpoint' => 'ppt', 'application/x-excel' => 'xsl', 'application/pdf' => 'pdf');
		if (!array_key_exists($filetype, $known_photo_types)) {
			echo "<p class=\"error-alert\">".$LANG['err_not_doc1']." $filetype ".$LANG['err_not_doc2']."<br/>".$LANG['err_not_doc3']."</p>";
			return false;
		} else {
			copy($filetmpname, "gallery/documents/$filename");
			return true;
		}
	}

	function displayWhatsNewDocuments () {
		global $LANG;
		$today = date('Y-m-d');
		$tomorrow  = date('Y-m-d', mktime(0, 0, 0, date("m")  , date("d")+1, date("Y")));
		$this->db->query("SELECT * FROM `fcms_documents` WHERE `date` >= DATE_SUB(CURDATE() , INTERVAL 30 DAY) ORDER BY `date` DESC LIMIT 0 , 5");
		if ($this->db->count_rows() > 0) {
			echo "\n\t\t\t\t<h3>".$LANG['link_documents']."</h3>\n\t\t\t\t<ul>\n";
			while ($r = $this->db->get_row()) {
				$name = $r['name'];
				$displayname = getUserDisplayName($r['user']);
				$monthName = gmdate('M', strtotime($r['date'] . $this->tz_offset));
				$date = gmdate('. j, Y, g:i a', strtotime($r['date'] . $this->tz_offset));
				if (
                    strtotime($r['date']) >= strtotime($today) && 
                    strtotime($r['date']) > $tomorrow
                ) {
                    $full_date = $LANG['today'];
                    $d = ' class="today"';
                } else {
                    $full_date = getLangMonthName($monthName) . $date;
                    $d = '';
                }
                echo "\t\t\t\t\t<li><div$d>$full_date</div>";
				echo "<a href=\"documents.php\">$name</a> - <a class=\"u\" ";
                echo "href=\"profile.php?member=" . $r['user'] . "\">$displayname</a></li>\n";
			}
			echo "\t\t\t\t</ul>\n";
		}
	}

} ?>

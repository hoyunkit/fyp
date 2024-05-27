<?php
/**
 * Implementation of DocumentAccess view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Class which outputs the html page for DocumentAccess view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_DocumentAccess extends SeedDMS_Bootstrap_Style {
	function printAccessesModeSelection($defMode) { /* {{{ */
		echo self::getAccessModeSelection($defMode);
	} /* }}} */

	/// Glenn edited on 6 May, 2022
	function printAccessModeSelection($defMode, $attr="", $allAccesses, $identify) { /* {{{ */
		echo self::getAccessesModeSelection($defMode, $attr="", $allAccesses, $identify);
	} /* }}} */

	function getAccessModeSelection($defMode) { /* {{{ */
		$content = "<select name=\"mode\">\n";
		$content .= "\t<option value=\"".M_NONE."\"" . (($defMode == M_NONE) ? " selected" : "") . ">" . getMLText("access_mode_none") . "</option>\n";
		$content .= "\t<option value=\"".M_READ."\"" . (($defMode == M_READ) ? " selected" : "") . ">" . getMLText("access_mode_read") . "</option>\n";
		$content .= "\t<option value=\"".M_READWRITE."\"" . (($defMode == M_READWRITE) ? " selected" : "") . ">" . getMLText("access_mode_readwrite") . "</option>\n";
		$content .= "\t<option value=\"".M_ALL."\"" . (($defMode == M_ALL) ? " selected" : "") . ">" . getMLText("access_mode_all") . "</option>\n";
		$content .= "</select>\n";
		return $content;
	} /* }}} */

	// Glenn edited on 6 May, 2022
	function getAccessesModeSelection($defMode, $attr="", $allAccesses, $identify) {
		$content = "<select id=\"editMode".$identify."\" name=\"mode\" ".$attr." >\n";
		foreach ($allAccesses as $accessItem) {
			$content .= "\t<option value=\"".$accessItem['mode']."\"" . (($defMode == $accessItem['mode']) ? " selected" : "") . ">" . $accessItem['name'] . "\n";
		}
		$content .= "</select>\n";
		return $content;
	}

	// Glenn edited on 6 May, 2022
	function accessSelectionForUserGroup($defMode, $attr="", $allAccesses) {
		$content = "<select id=\"newMode\" name=\"mode\" ".$attr." >\n";
		foreach ($allAccesses as $accessItem) {
			$content .= "\t<option value=\"".$accessItem['mode']."\"" . (($defMode == $accessItem['mode']) ? " selected" : "") . ">" . $accessItem['name'] . "\n";
		}
		$content .= "</select>\n";
		return $content;
	}

	function js() { /* {{{ */
		header('Content-Type: application/javascript');
?>
function checkForm()
{
	msg = new Array();
	if ((document.form1.userid.options[document.form1.userid.selectedIndex].value == -1) && 
		(document.form1.groupid.options[document.form1.groupid.selectedIndex].value == -1))
			msg.push("<?php printMLText("js_select_user_or_group");?>");
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}

$(document).ready( function() {
	$('body').on('submit', '#form1', function(ev){
		if(checkForm()) return;
		ev.preventDefault();
	});

	$('#userid').live('change', function() {
		let useridSelected = $('#userid option:selected').val();
		if (useridSelected != -1) {
			$('#groupid').attr('disabled', true);
			$('#select2-groupid-container').attr('title', 'You cannot select group at this moment');
			$('#select2-groupid-container').text('You cannot select group at this moment');
		} else {
			$('#groupid').attr('disabled', false);
			$('#select2-groupid-container').attr('title', "<?php echo getMLText('select_one'); ?>");
			$('#select2-groupid-container').text("<?php echo getMLText('select_one'); ?>");
		}
	});

	$('#groupid').live('change', function() {
		let groupidSelected = $('#groupid option:selected').val();
		if (groupidSelected != -1) {
			$('#userid').attr('disabled', true);
			$('#select2-userid-container').attr('title', 'You cannot select user at this moment');
			$('#select2-userid-container').text('You cannot select user at this moment');
		} else {
			$('#userid').attr('disabled', false);
			$('#select2-userid-container').attr('title', "<?php echo getMLText('select_one'); ?>");
			$('#select2-userid-container').text("<?php echo getMLText('select_one'); ?>");
		}
	});

	$('#reset-target').on('click', function() {
		$("#userid").val(-1).change();
		$("#groupid").val(-1).change();
		$('#groupid').attr('disabled', false);
		$('#userid').attr('disabled', false);
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$document = $this->params['document'];
		$folder = $this->params['folder'];
		$allUsers = $this->params['allusers'];
		$allGroups = $this->params['allgroups'];


		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);

		$this->contentHeading(getMLText("edit_document_access"));
		echo "<div class=\"row-fluid\">\n";
		echo "<div class=\"span4\">\n";
		$this->contentContainerStart();

		if ($user->isAdmin()) {

?>
	<form action="../op/op.DocumentAccess.php">
  <?php echo createHiddenFieldWithKey('documentaccess'); ?>
	<input type="Hidden" name="action" value="setowner">
	<input type="Hidden" name="documentid" value="<?php print $document->getId();?>">
<?php
		$owner = $document->getOwner();
		$options = array();
		foreach ($allUsers as $currUser) {
			if (!$currUser->isGuest())
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), ($currUser->getID()==$owner->getID()), array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));
		} 
		$this->formField(
			getMLText("set_owner"),
			array(
				'element'=>'select',
				'name'=>'ownerid',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
		$this->formSubmit("<i class=\"icon-save\"></i> ".getMLText('save'));
?>
	</form>
<?php
		}

		$this->contentSubHeading(getMLText("access_inheritance"));

		if ($document->inheritsAccess()) {
			printMLText("inherits_access_msg");
?>
  <p>
	<form action="../op/op.DocumentAccess.php" style="display: inline-block;">
  <?php echo createHiddenFieldWithKey('documentaccess'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getId();?>">
	<input type="hidden" name="action" value="notinherit">
	<input type="hidden" name="mode" value="copy">
	<input type="submit" class="btn" value="<?php printMLText("inherits_access_copy_msg")?>">
	</form>
	<form action="../op/op.DocumentAccess.php" style="display: inline-block;">
  <?php echo createHiddenFieldWithKey('documentaccess'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getId();?>">
	<input type="hidden" name="action" value="notinherit">
	<input type="hidden" name="mode" value="empty">
	<input type="submit" class="btn" value="<?php printMLText("inherits_access_empty_msg")?>">
	</form>
	</p>
<?php
			$this->contentContainerEnd();
			echo "</div>";
			echo "</div>";
			$this->contentEnd();
			$this->htmlEndPage();
			return;
		}
?>
	<form action="../op/op.DocumentAccess.php">
  <?php echo createHiddenFieldWithKey('documentaccess'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getId();?>">
	<input type="hidden" name="action" value="inherit">
	<input type="submit" class="btn" value="<?php printMLText("does_not_inherit_access_msg")?>">
	</form>
<?php
		$this->contentContainerEnd();
		echo "</div>";
		echo "<div class=\"span4\">";
		$this->contentContainerStart();

		$accessList = $document->getAccessList();

?>
<!-- Glenn edited on 6 May, 2022 -->
<h3 style="text-align: center;">Allocate Access To Document</h3>
<form class="form-horizontal" action="../op/op.DocumentAccess.php" style="border-bottom: 1px solid black; padding-bottom: 20px;">
  <?php echo createHiddenFieldWithKey('documentaccess'); ?>
	<input type="hidden" name="documentid" value="<?php print $document->getId();?>">
	<input type="hidden" name="action" value="setdefault">
<?php
		// Get all access permissions - Glenn edited on 6 May, 2022
		$allAccesses = $dms->getAllAccesses();
		$this->formField(
			getMLText("default_access"),
			// Glenn edited on 10 May, 2022
			$this->getAccessesModeSelection($document->getDefaultAccess(), "", $allAccesses, 0)
		);
		$this->formSubmit("<i class=\"icon-save\"></i> ".getMLText('save'));
?>
</form>

<!-- Glenn edited on 6 May, 2022 -->
<h3 style="text-align: center;">Allocate Access To User/Group</h3>
<div class="controls text-center" style="margin-bottom: 10px;">
	<button class="btn" type="button" id="reset-target"><i class="icon-repeat"></i>&nbsp;<?php echo getMLText('reset_target'); ?></button>
</div>
<form class="form-horizontal" action="../op/op.DocumentAccess.php" name="form1" id="form1">
<?php echo createHiddenFieldWithKey('documentaccess'); ?>
<input type="hidden" name="documentid" value="<?php print $document->getId()?>">
<input type="hidden" name="action" value="addaccess">
<?php
		$options = array();
		$options[] = array(-1, getMLText('select_one'));
		foreach ($allUsers as $currUser) {
			if (!$currUser->isGuest())
				// Glenn edited on 6 May, 2022
				$options[] = array($currUser->getID(), htmlspecialchars($currUser->getLogin()), false, array(array('data-subtitle', htmlspecialchars($currUser->getFullName()))));  
		}
		// Glenn edited on 6 May, 2022
		$this->formField(
			getMLText("user"),
			array(
				'element'=>'select',
				'name'=>'userid',
				'id'=>'userid',
				'class'=>'chzn-select',
				'attributes'=>array(array('data-placeholder',getMLText('select_user'))),
				'options'=>$options
			)
		);
		$options = array();
		$options[] = array(-1, getMLText('select_one'));
		foreach ($allGroups as $groupObj) {
			$options[] = array($groupObj->getID(), htmlspecialchars($groupObj->getName()));
		}
		// Glenn edited on 6 May, 2022
		$this->formField(
			getMLText("group"),
			array(
				'element'=>'select',
				'name'=>'groupid',
				'id'=>'groupid',
				'class'=>'chzn-select',
				'attributes'=>array(array('data-placeholder', getMLText('select_group'))),
				'options'=>$options
			)
		);

		// Glenn edited on 6 May, 2022
		$this->formField(
			getMLText("choose_access_for_target"),
			// We allocate the default access is 'no access', 1 means 'no access'
			$this->accessSelectionForUserGroup("1", "", $allAccesses)
		);
		echo '
			<div class="controls">
				<button type="submit" class="btn" id="addAccess-btn"><i class="icon-plus"></i> '. getMLText('add') .'</button>
			</div>
		';
?>
</form>
<?php
		$this->contentContainerEnd();
?>
	</div>
	<div class="span4">
<?php

		if (count($accessList["users"]) != 0 || count($accessList["groups"]) != 0) {

			// Glenn edited on 6 May, 2022
			print "<h3 class='text-center'>User</h3>";
			print "<table class=\"table-condensed\">";
			foreach ($accessList["users"] as $userAccess) {
				$userObj = $userAccess->getUser();
				print "<tr>\n";
				print "<td style=\"width: 15%\"><i class=\"icon-user\"></i></td>\n";
				print "<td>". htmlspecialchars($userObj->getFullName()) . "</td>\n";
				// Save mode action for user
				print "<form action=\"../op/op.DocumentAccess.php\" id=\"saveAccess-user-form".$userObj->getID()."\">\n";
				echo createHiddenFieldWithKey('documentaccess')."\n";
				print "<input type=\"hidden\" name=\"documentid\" value=\"".$document->getId()."\">\n";
				print "<input type=\"hidden\" name=\"action\" value=\"editaccess\">\n";
				print "<input type=\"hidden\" name=\"userid\" id=\"saveAccess-userid".$userObj->getID()."\" value=\"".$userObj->getID()."\">\n";
				print "<td>\n";
				$this->printFollowGroupPopupBox($dms->followGroupInfo($userObj->getID()));
				print "</td>\n";
				print "<td>\n";
				$this->printAccessModeSelection($userAccess->getMode(), "", $allAccesses, $userObj->getID());
				print "</td>\n";
				print "<td>\n";
				print "<button type=\"submit\" data-identify=\"".$userObj->getID()."\" class=\"btn btn-save-user btn-mini\"><i class=\"icon-save\"></i> ".getMLText("save")."</button>";
				print "</td>\n";
				print "</form>\n";
				// Delete mode action for user
				print "<form action=\"../op/op.DocumentAccess.php\">\n";
				echo createHiddenFieldWithKey('documentaccess')."\n";
				print "<input type=\"hidden\" name=\"documentid\" value=\"".$document->getId()."\">\n";
				print "<input type=\"hidden\" name=\"action\" value=\"delaccess\">\n";
				print "<input type=\"hidden\" name=\"userid\" value=\"".$userObj->getID()."\">\n";
				print "<td>\n";
				print "<button type=\"submit\" class=\"btn btn-mini\"><i class=\"icon-remove\"></i> ".getMLText("delete")."</button>";
				print "</td>\n";
				print "</form>\n";
				print "</tr>\n";
			}
			print "</table><br>";
 
			// Glenn edited on 7 May, 2022
			print "<h3 class='text-center'>Group</h3>";
			print "<table class=\"table-condensed\">";
			foreach ($accessList["groups"] as $groupAccess) {
				$groupObj = $groupAccess->getGroup();
				$mode = $groupAccess->getMode();
				print "<tr>";
				print "<td style=\"width: 15%\"><i class=\"icon-group\"></i></td>";
				print "<td>". htmlspecialchars($groupObj->getName()) . "</td>";
				print "<form action=\"../op/op.DocumentAccess.php\" id=\"saveAccess-group-form".$groupObj->getID()."\">";
				echo createHiddenFieldWithKey('documentaccess')."\n";
				print "<input type=\"hidden\" name=\"documentid\" value=\"".$document->getId()."\">";
				print "<input type=\"hidden\" name=\"action\" value=\"editaccess\">";
				print "<input type=\"hidden\" name=\"groupid\" id=\"saveAccess-groupid".$groupObj->getID()."\" value=\"".$groupObj->getID()."\">";
				print "<td>";
				$this->printAccessModeSelection($groupAccess->getMode(), "", $allAccesses, $groupObj->getID());
				print "</td>\n";
				print "<td><span class=\"actions\">\n";
				print "<button type=\"submit\" style=\"padding: 6px\" data-identify=\"".$groupObj->getID()."\" class=\"btn btn-save-group btn-mini\"><i class=\"icon-save\"></i> ".getMLText("save")."</button>";
				print "</span></td>\n";
				print "</form>";
				print "<form action=\"../op/op.DocumentAccess.php\" id=\"deleteAccess-group-form".$groupObj->getID()."\">\n";
				echo createHiddenFieldWithKey('documentaccess')."\n";
				print "<input type=\"hidden\" name=\"documentid\" value=\"".$document->getId()."\">\n";
				print "<input type=\"hidden\" name=\"action\" value=\"delaccess\">\n";
				print "<input type=\"hidden\" name=\"groupid\" id=\"deleteAccess-groupid".$groupObj->getID()."\" value=\"".$groupObj->getID()."\">\n";
				print "<td>";
				print "<button type=\"submit\" style=\"padding: 6px\" data-identify=\"".$groupObj->getID()."\" class=\"btn btn-delete-group btn-mini\"><i class=\"icon-remove\"></i> ".getMLText("delete")."</button>";
				print "</td>\n";
				print "</form>";
				print "</span></td>\n";
				print "</tr>\n";
			}
			print "</table><br>";
		}
?>
	</div>
	</div>

<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>

<?php

/**
 * Implementation of AccessMgr view
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
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for AccessMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_AccessMgr extends SeedDMS_Bootstrap_Style
{

  function js()
  { /* {{{ */
    $selgroup = $this->params['selgroup'];
    $strictformcheck = $this->params['strictformcheck'];

    header("Content-type: text/javascript");
?>
    function checkForm1() {
    msg = new Array();

    if($("#name").val() == "") msg.push("<?php printMLText("js_no_name"); ?>");
    <?php
    if ($strictformcheck) {
    ?>
      if($("#comment").val() == "") msg.push("<?php printMLText("js_no_comment"); ?>");
    <?php
    }
    ?>
    if (msg != "") {
      noty({
        text: msg.join('<br />'),
        type: 'error',
        dismissQueue: true,
        layout: 'topRight',
        theme: 'defaultTheme',
        _timeout: 1500,
      });
      return false;
    } else
      return true;
    }

    function checkForm2() {
    msg = "";

    if($("#userid").val() == -1) msg += "<?php printMLText("js_select_user"); ?>\n";

    if (msg != "") {
      noty({
        text: msg,
        type: 'error',
        dismissQueue: true,
        layout: 'topRight',
        theme: 'defaultTheme',
        _timeout: 1500,
      });
      return false;
    } else
      return true;
    }

    $(document).ready( function() {
      <!-- When select a new mode, transfer the form data to out file without page loaded  -->
      $("#mode").live('change', function() {
        $('div.ajax').trigger('update', {
          name: $('#name').val(),
          comment: $('#comment').val(),
          mode: $('#mode').val()
        });
      });

      $('body').on('submit', '#form_1', function(ev){
        if(checkForm1())
        return;
        ev.preventDefault();
      });

      $('body').on('submit', '#form_2', function(ev){
        if(checkForm2())
        return;
        ev.preventDefault();
      });

      $("#selector").change(function() {
        $('div.ajax').trigger('update', {accessName: $(this).val()});
      });
    });

    <?php
  } /* }}} */

  function info()
  { /* {{{ */
    $dms = $this->params['dms'];

    $this->contentHeading(getMLText("access_info"));
    $accessesInfo = $dms->getAllAccesses();
    echo "<table class=\"table table-condensed\">\n";
    echo "<tr><th>Access Name</th><th>Mode</th></tr>";
    foreach ($accessesInfo as $access) {
      $accessName = $access['name'];
      $accessMode = $access['mode'];
      echo "<tr><td>" . $accessName . "</td><td>" . $accessMode . "</td></tr>";
    }
    echo "</table>";
  } /* }}} */

  function actionmenu()
  { /* {{{ */
    $selaccess = $this->params['selaccess'];

    if ($selaccess) {
    ?>
      <section style="display: flex; align-items: center;">
        <div class="btn-group">
          <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
            <?php echo getMLText('action'); ?>
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu">
            <?php
            echo '<li><a href="../out/out.RemoveAccess.php?accessName=' . $selaccess->getName() . '"><i class="icon-remove"></i> ' . getMLText("rm_access") . '</a><li>';
            ?>
          </ul>
        </div>
        <i class="icon-question" data-toggle="modal" data-target="#removeaccess" style="font-size: 20px; color: #6f6868; margin-left: 5px; cursor: pointer;"></i>
      </section>
    <?php
    }
  } /* }}} */

  function showAccessForm($access)
  { /* {{{ */
    $dms = $this->params['dms'];
    $newName = $this->params['newname'];
    $newMode = $this->params['newmode'];
    $newComment = $this->params['newcomment'];

    ?>
    <form class="form-horizontal" action="../op/op.AccessMgr.php" name="form_1" id="form_1" method="post">
      <?php
      if ($access) {
        echo createHiddenFieldWithKey('editaccess');
      ?>
        <input type="hidden" name="accessName" value="<?php print $access->getName(); ?>">
        <input type="hidden" name="action" value="editaccess">
      <?php
      } else {
        echo createHiddenFieldWithKey('addaccess');
      ?>
        <input type="hidden" name="action" value="addaccess">
      <?php
      }
      // If selecting a access, then showing right-side privileges part. Otherwise, disappear.
      if ($access) {
        $accessName = $access->getName();
        print '<h3 style="text-align: center;">Local Setting</h3>';
        if ($accessName === 'No access' || $accessName === 'All access' || $accessName === 'Read' || $accessName === 'Read-Write') {
          $this->formField(
            getMLText("name"),
            array(
              'element' => 'input',
              'type' => 'text',
              'id' => 'name',
              'name' => 'name',
              'readonly' => 'readonly',
              'value' => ($access ? htmlspecialchars($access->getName()) : '')
            )
          );
          $this->formField(
            getMLText("comment"),
            array(
              'element' => 'textarea',
              'id' => 'comment',
              'name' => 'comment',
              'rows' => 4,
              'value' => ($access ? htmlspecialchars($access->getComment()) : '')
            )
          );
        } else {
          $this->formField(
            getMLText("name"),
            array(
              'element' => 'input',
              'type' => 'text',
              'id' => 'name',
              'name' => 'name',
              'value' => ($access ? htmlspecialchars($access->getName()) : '')
            )
          );
          $this->formField(
            getMLText("comment"),
            array(
              'element' => 'textarea',
              'id' => 'comment',
              'name' => 'comment',
              'rows' => 4,
              'value' => ($access ? htmlspecialchars($access->getComment()) : '')
            )
          );
        }
        $this->formSubmit("<i class=\"icon-save\"></i> " . getMLText('save'));
      } else {
        $this->formField(
          getMLText("name"),
          array(
            'element' => 'input',
            'type' => 'text',
            'id' => 'name',
            'name' => 'name',
            'required' => 'required',
            'value' => $newName
          )
        );
        $this->formField(
          getMLText("comment"),
          array(
            'element' => 'textarea',
            'id' => 'comment',
            'name' => 'comment',
            'rows' => 4,
            'value' => $newComment
          )
        );
        $options = [];
        $options[] = array(null, getMLText("select_access"));
        $allAccesses = $dms->getAllAccesses();
        foreach ($allAccesses as $accessItem) {
          // We will not show the all access's mode
          if ($accessItem['name'] != 'All access') {
            $originalMode = $accessItem['mode'];
            $modePlueOne = $accessItem['mode'] + 1;
            $options[] = array($modePlueOne, htmlspecialchars($accessItem['mode'] + 1 . ' (After ' . $accessItem['name'] . ')'), $newMode && $modePlueOne == $newMode);
          }
        }
        echo '<div style="display: flex">';
        $this->formField(
          getMLText("mode"),
          array(
            'element' => 'select',
            'id' => 'mode',
            'name' => 'mode',
            'required' => 'required',
            'class' => 'chzn-select',
            'options' => $options
          )
        );
        echo '<i class="icon-question" data-toggle="modal" data-target="#selectNewMode" style="font-size: 20px; color: #6f6868; margin-left: 5px; cursor: pointer;"></i>';
        echo '</div>';
        $privilegeOptions = [];
        $originalMode = $newMode - 1;
        $availablePrivileges = $dms->getAvailablePrivileges($originalMode, $newMode);
        foreach ($availablePrivileges as $key => $val) {
          $privilegeOptions[] = array($key, getMLText($key), $val);
        }
        echo '<div style="display: flex">';
        $this->formField(
          "Select Privileges",
          array(
            'element' => 'select',
            'name' => 'privileges[]',
            'class' => 'chzn-select',
            'multiple' => true,
            'attributes' => array(array('data-placeholder', " click here to select privileges...")),
            'options' => $privilegeOptions
          )
        );
        echo '<i class="icon-question" data-toggle="modal" data-target="#availablePrivileges" style="font-size: 20px; color: #6f6868; margin-left: 5px; cursor: pointer;"></i>';
        echo '</div>';
        $this->formSubmit("<i class=\"icon-save\"></i> " . getMLText('save'));
      }
      ?>
    </form>
    <?php
    if ($access) {
    ?>

    <?php
      print '<form class="form-inline" action="../op/op.AccessMgr.php" method="POST" name="form_3" id="form_3">';
      $options = array();
      $tmparr = $access->getPrivileges();
      foreach ($tmparr as $key => $val) {
        $options[] = array($key, getMLText($key), $val); // option value(used when submit), html, 1=selected
      }
      // If it is no access or all access, we should disable the select privileges field
      $accessName = $access->getName();
      if ($accessName === 'No access' || $accessName === 'All access' || $accessName === 'Read' || $accessName === 'Read-Write') {
        $this->formField(
          "Select Privileges",
          array(
            'element' => 'select',
            'name' => 'privileges[]',
            'class' => 'chzn-select',
            'multiple' => true,
            'attributes' => array(array('data-placeholder', " click here to select privileges...")),
            'disabled' => 'disabled',
            'options' => $options
          )
        );
      } else {
        $this->formField(
          "Select Privileges",
          array(
            'element' => 'select',
            'name' => 'privileges[]',
            'class' => 'chzn-select',
            'multiple' => true,
            'attributes' => array(array('data-placeholder', " click here to select privileges...")),
            'options' => $options
          )
        );
      }
      print '<input type="hidden" name="action" value="editprivileges">';
      print '<input type="hidden" name="accessName" value="' . $access->getName() . '">';
      $accessName === 'No access' || $accessName === 'All access' || $accessName === 'Read' || $accessName === 'Read-Write' ? print '<input type="submit" class="btn" value="Confirm" style="cursor: not-allowed;" disabled>' : print '<input type="submit" class="btn" value="Confirm">';
      print '</form>';

      // Add new privilege form
      print '<form class="form-inline" action="../op/op.AccessMgr.php" method="POST" name="form_4" id="form_4">';
      print '<h3 style="text-align: center;">Global Setting</h3>';
      $this->formField(
        'Add Privilege',
        array(
          'element' => 'input',
          'type' => 'text',
          'id' => 'newPrivilege',
          'name' => 'newPrivilege',
          'style' => 'width: 98.7%',
          'required' => 'required',
          'placeholder' => 'i.e. Add document',
          'pattern' => '[a-zA-Z]*[ ][a-zA-Z]*',
          'title' => 'Only allow string. i.e Add document',
          'value' => ''
        )
      );
      print '<input type="hidden" name="action" value="addprivilege">';
      print '<input type="hidden" name="accessName" value="No access">';
      echo createHiddenFieldWithKey('addprivilege');
      print '<input type="submit" class="btn" value="Confirm">';
      print '</form>';

      // Drop privilege form
      print '<form class="form-inline" action="../op/op.AccessMgr.php" method="POST" name="form_5" id="form_5">';
      $this->formField(
        'Drop Privilege',
        array(
          'element' => 'select',
          'name' => 'privileges[]',
          'class' => 'chzn-select',
          'multiple' => false,
          'attributes' => array(array('data-placeholder', " click here to select privileges...")),
          'options' => $options
        )
      );
      print '<input type="hidden" name="action" value="dropprivilege">';
      print '<input type="hidden" name="accessName" value="' . $access->getName() . '">';
      echo createHiddenFieldWithKey('dropprivilege');
      print '<input type="submit" class="btn" value="Confirm">';
      print '</form>';
    }
  } /* }}} */

  function form()
  { /* {{{ */
    $selaccess = $this->params['selaccess'];
    $this->showAccessForm($selaccess);
  } /* }}} */

  function show()
  { /* {{{ */
    $dms = $this->params['dms'];
    $selaccess = $this->params['selaccess'];
    $allAccesses = $this->params['allaccess'];

    $this->htmlStartPage(getMLText("admin_tools"));
    $this->globalNavigation();
    $this->contentStart();
    $this->pageNavigation(getMLText("admin_tools"), "admin_tools");

    $this->contentHeading(getMLText("access_management"));
    ?>

    <div class="row-fluid">
      <div class="span4">
        <form class="form-horizontal">
          <?php
          $options = array();
          $options[] = array("-1", getMLText("add_access"));
          foreach ($allAccesses as $access) {
            $mode = $access->getMode();
            $modeString = ' (mode: ' . $mode . ')';
            $options[] = array($access->getName(), htmlspecialchars($access->getName() . $modeString), $selaccess && $access->getName() == $selaccess->getName());
          }
          $this->formField(
            null,
            array(
              'element' => 'select',
              'id' => 'selector',
              'class' => 'chzn-select',
              'options' => $options
            )
          );
          ?>
        </form>
        <!-- If selaccess exists, it will show the remove action button -->
        <div class="ajax" style="margin-bottom: 15px;" data-view="AccessMgr" data-action="actionmenu" <?php echo ($selaccess ? "data-query=\"accessName=" . $selaccess->getName() . "\"" : "") ?>></div>

        <!-- Show the permission information -->
        <div class="ajax" data-view="AccessMgr" data-action="info" <?php echo ($selaccess ? "data-query=\"accessName=" . $selaccess->getName() . "\"" : "") ?>></div>
      </div>

      <div class="span8">
        <?php $this->contentContainerStart(); ?>
        <div class="ajax" data-view="AccessMgr" data-action="form" <?php echo ($selaccess ? "data-query=\"accessName=" . $selaccess->getName() . "\"" : "") ?>></div>
        <?php $this->contentContainerEnd(); ?>
      </div>


    </div>

    <!-- Remove access modal -->
    <div class="modal fade" id="removeaccess" tabindex="-1" role="dialog" aria-labelledby="removeaccess" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Remove Access Action</h5>
          </div>
          <div class="modal-body">
            You cannot remove default accesses, including "No access", "Read", "Read-Write", and "All access".
            But you can remove any newly added accesses.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Select new mode modal -->
    <div class="modal fade" id="selectNewMode" tabindex="-1" role="dialog" aria-labelledby="selectNewMode" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Select New Mode</h5>
          </div>
          <div class="modal-body">
            When you select mode as 2 for the new access, the corresponding privileges that this access can choose can only be within the range of privileges corresponding to the original mode value of 1 and 2. The initial mode value can be in the "info" on the left table check.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Avaliable privileges modal -->
    <div class="modal fade" id="availablePrivileges" tabindex="-1" role="dialog" aria-labelledby="availablePrivileges" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Available Privileges</h5>
          </div>
          <div class="modal-body">
            This selection bar will only display the available privileges corresponding to the new mode.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

<?php
    $this->contentEnd();
    $this->htmlEndPage();
  } /* }}} */
}
?>
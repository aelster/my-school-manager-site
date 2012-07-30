<?php
function UserManager() {
	include( "globals.php" );
	
	$area = func_get_arg( 0 );
	
	if( $gTrace ) {
		$gFunction[] = "UserManager($area)";
		Logger();
	}
	
	static $inited = 0;
	
	if( ! $inited ) {
		UserManagerInit();
		$inited = 1;
	}
		
	switch( $area )
	{
		case( 'authorized' ):
			$num_args = func_num_args();
			if( $num_args == 2 ) {
				return UserManagerAuthorized( func_get_arg(1) );
			} else {
				return UserManagerAuthorized( func_get_arg(1), func_get_arg(2) );
			}
			break;
		
		case( 'control' ):
			UserManagerControl();
			break;

		case( 'features' );
			UserManagerFeatures();
			break;
			
		case( 'inactive' );
			UserManagerInactive();
			break;
			
		case( 'levels' ):
			UserManagerLevels();
			break;
		
		case( 'load' ):
			UserManagerLoad( func_get_arg( 1 ) );
			break;
		
		case( 'login' ):
			UserManagerLogin();
			break;
		
		case( 'logout' ):
			UserManagerLogout();
			break;

		case( 'newpassword' ):
			UserManagerPassword();
			break;
		
		case( 'privileges' ):
			UserManagerPrivileges();
			break;
		
		case( 'report' ):
			UserManagerReport();
			break;
		
		case( 'resend' ):
			UserManagerResend();
			break;
		
		case( 'reset' ):
			UserManagerReset();
			break;
		
		case( 'update' ):
			UserManagerUpdate();
			break;
		
		case( 'verify' ):
			UserManagerVerify();
			break;
		
		default:
			echo "Uh-oh:  Contact Andy regarding UserManager( $area )<br>";
			break;
	}
	
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerAdd()
{
	include( "globals.php" );
	if( $gTrace ) {
		$GLOBALS[ 'gFunction' ][] = "UserManagerAdd()";
		Logger();
	}
	
	$text = array();
	$text[] = sprintf( "insert into users set first = '%s'", $_POST['first'] );
	$text[] = sprintf( "last = '%s'", $_POST['last'] );
	$text[] = sprintf( "email = '%s'", $_POST['email'] );
	$text[] = sprintf( "username = '%s'", $_POST['username'] );
	$text[] = sprintf( "password = '%s'", md5( sprintf( "%d", time())));
	$text[] = sprintf( "active = '1'" );
	$query = join( ",", $text );
	DoQuery( $query );
	$id = mysql_insert_id();

	if( $id ) {
		$text = array();
		$text[] = "insert event_log set time=now()";
		$text[] = "type = 'control'";
		$text[] = sprintf( "userid = '%d'", $GLOBALS['gUserId'] );
		$text[] = sprintf( "item = 'added user %s %s, username %s, id %d, e-mail %s'",
					$_POST[ 'first' ], $_POST[ 'last' ], $_POST[ 'username' ], $id, $_POST[ 'email' ] );
		$query = join( ",", $text );
		DoQuery( $query );
		
		$query = "insert into access set userid = '$id', privid = '" . $_POST['access'] . "'";
		DoQuery( $query );
	}
	
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerActivate( $new_val )
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerActivate( $new_val )";
		Logger();
	}
	
	$id = $_POST[ 'id' ];
	$query = "update users set active = '$new_val' where userid = '$id'";
	DoQuery( $query );

	DoQuery( "select username from users where userid = '$id'");
	list( $username ) = mysql_fetch_array( $local_result );
	
	$text = array();
	$text[] = "insert event_log set time=now()";
	$text[] = "type = 'control'";
	$text[] = sprintf( "userid = '%d'", $GLOBALS['gUserId'] );
	$text[] = sprintf( "item = 'user %s(%d), active changed to %d'", $username, $id, $new_val );
	$query = join( ",", $text );
	DoQuery( $query );

	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerAuthorized()
{
	include( "globals.php" );
	if( func_num_args() == 1 ) {
		$privilege = func_get_arg(0);
		$db = $gDbControl;
		$dbt = "control";
	} else {
		$privilege = func_get_arg(0);
		$db = func_get_arg(1);
		$dbt = ( $db == $gDbControl ) ? "control" : "other";
	}
	if( $gTrace ) {
		$gFunction[] = "UserManagerAuthorized($privilege,$dbt)";
		Logger();
	}
	
	$ok = 0;
	if( $gUserId ) {
		DoQuery( "select levelId, enabled from user_privileges where id = $gUserId", $db );
		list ($lid,$ena) = mysql_fetch_array( $gResult );
		if( empty( $ena ) ) {
			$ok = 0;
			if( $gTrace ) Logger( "user $gUserId not enabled" );
		} else {
			$level_req = $gLevelNameToVal[$privilege];
			$level_auth = $gLevelIdToLevel[$lid];
			$ok = ( $level_req <= $level_auth ) ? 1 : 0;
			if( $gTrace ) Logger( "user $gUserId requesting auth $level_req, authorized for $level_auth" );
		}	
	}
	if( $gTrace ) array_pop( $gFunction );
	return $ok;
}

function UserManagerControl()
{
	include( "globals.php" );
	if( $gTrace ) { 
		$gFunction[] = "UserManagerControl()";
		Logger();
	}

	$action = isset( $_POST[ 'btn_action' ] ) ? $_POST[ 'btn_action' ] : "";
	
	if( empty( $action ) ) {
		UserManagerDisplay();
	} elseif( $action == 'Add' ) {
		UserManagerAdd();
	} elseif( $action == 'Delete' ) {
		UserManagerDelete();
	} elseif( $action == 'Edit' ) {
		UserManagerEdit();
		$GLOBALS['gFrom'] = 'Done';
	} elseif( $action == "Enable" ) {
		UserManagerActivate( 1 );
	} elseif( $action == "Disable" ) {
		UserManagerActivate( 0 );
	}

	$_POST[ 'btn_action' ] = NULL;
	
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerDelete()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerDelete()";
		Logger();
	}
	
	$id = $_POST[ 'id' ];
	
	DoQuery( "select * from users where userid = '$id'" );
	$user = mysql_fetch_assoc( $local_result );
	
	DoQuery( "delete from users where userid = '$id'" );
	
	$text = array();
	$text[] = "insert event_log set time=now()";
	$text[] = "type = 'control'";
	$text[] = sprintf( "userid = '%d'", $GLOBALS['gUserId'] );
	$text[] = sprintf( "item = 'deleted user %s %s, username %s, e-mail %s'",
				$user[ 'first' ], $user[ 'last' ], $user[ 'username' ], $user[ 'email' ] );
	$query = join( ",", $text );
	DoQuery( $query );

	DoQuery( "delete from access where userid = '$id'" );
	DoQuery( "delete from grades where userid = '$id'" );
	
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerDisplay()
{
	include( "globals.php" );
	if( $gTrace ) { 
		$gFunction[] = "UserManagerDisplay()";
		Logger();
	}
	
	echo "<h2>Users</h2>";
	echo "<div class=CommonV2>";
	echo "<input type=hidden name=from value=Users>";
	echo sprintf( "<input type=hidden name=userid value='%d'>", $gUserId );

	$acts = array();
	$acts[] = "MySetValue('area','users')";
	$acts[] = "MySetValue('func','update')";
	$acts[] = "MySetValue('id', '" . $gUserId . "')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<input type=button onClick=\"%s\" id=update value=Update>", join(';',$acts ) );
	
	echo "<table class=sortable>";
	echo "<tr>";
	echo "<th>Username</th>";
	echo "<th>First</th>";
	echo "<th>Last</th>";
	echo "<th>E-Mail</th>";
	echo "<th>Last Login</th>";
	echo "<th>Active</th>";
	echo "<th>Actions</th>";
	echo "</tr>";
	
	foreach( $gUsers as $user ) {
		$id = $user['id'];
		$jscript = "onChange=\"MyAddField('$id');MyToggleBgRed('update');\"";
		echo "<tr>";
		printf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_username value=\"%s\" $jscript size=10></td>\n", $user['username'], $id, $user['username']);
		printf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_first value=\"%s\" $jscript size=10></td>\n", $user['first'], $id, $user['first']);
		printf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_last value=\"%s\" $jscript size=10></td>\n", $user['last'], $id, $user['last']);
		printf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_email value=\"%s\" $jscript size=30></td>\n", $user['email'], $id, $user['email']);
		if( $user['lastlogin'] == '0000-00-00 00:00:00' )
			$str = "never";
		else {
			$diff = time() - strtotime( $user['lastlogin'] );
			$days = $diff / 60 / 60 / 24;
			$str = sprintf( "%d days ago", $days );
		}
		echo "<td align=center>$str</td>";

		$checked = $user['active'] ? "checked" : "";
		printf( "<td class=c><input type=checkbox name=u_%d_active value=1 $checked $jscript ></td>\n", $id );
		echo "<td class=c>";
		$acts = array();
		$acts[] = "MySetValue('area','users')";
		$acts[] = "MySetValue('func','delete')";
		$acts[] = "MySetValue('id', '$id')";
		$name = sprintf( "%s %s", $user['first'], $user['last'] );
		$acts[] = "MyConfirm('Are you sure you want to delete $name')";
		echo sprintf( "<input type=button onClick=\"%s\" id=update value=Del>", join(';',$acts ) );
		echo "</td>";
		echo "</tr>";
	}

	echo "<tr>";
	echo "<td><input type=text name=username size=10></td>";
	echo "<td><input type=text name=first size=10></td>";
	echo "<td><input type=text name=last size=10></td>";
	echo "<td><input type=text name=email size=30></td>";
	echo "<td>&nbsp;</td>";
	echo "<td>&nbsp;</td>";
	echo "<td class=c>";
	$acts = array();
	$acts[] = "MySetValue('area','users')";
	$acts[] = "MySetValue('func','add')";
	$acts[] = "MySetValue('id', '" . $GLOBALS['gUserId'] . "')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<input type=button onClick=\"%s\" id=update value=Add>", join(';',$acts ) );
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "</div>";
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerEdit()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerEdit()";
		Logger();
	}

	$id = $_POST['id'];
	$query = "select * from users where userid = '$id'";
	DoQuery($query);
	$user = mysql_fetch_assoc( $local_result );
	
	echo sprintf( "<input type=hidden name=userid value='%d']>", $GLOBALS['gUserId'] );
?>
<input type=hidden name=from value=UserEdit>
<input type=hidden name=id id=id>
<input type=hidden name=btn_action id=btn_action>
<div id=users>
<table>
<tr>
	<th>First</th>
	<th>Last</th>
	<th>Username</th>
	<th>E-Mail</th>
</tr>
<tr>
<?php
	echo sprintf( "<td><input type=text name=first size=20 value='%s'></td>", $user['first'] );
	echo sprintf( "<td><input type=text name=last size=20 value='%s'></td>", $user['last'] );
	echo sprintf( "<td><input type=text name=username size=20 value='%s'></td>", $user['username'] );
	echo sprintf( "<td><input type=text name=email size=20 value='%s'></td>", $user['email'] );
	echo "<tr>";
	echo "</table>";
	echo "</div>";
	echo "<input type=submit name=action value=Back>";
	echo "<input type=button onclick=\"MySetValue( 'id', '$id'); MySetValue( 'btn_action', 'Update' ); MyAddAction('Update');\" value=Update>";
		
	if( $gTrace) array_pop( $gFunction );

}

function UserManagerReset()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerReset";
		Logger();
	}
#
# Remove old challenges
#
	$query = "delete from challenge_record where sess_id = '" . session_id() . "'";
	$query .= " or timestamp < " . time();
	DoQuery( $query, $gDbControl );
#
# Store a new challenge to use
#
	$challenge = SHA256::hash(uniqid(mt_rand(), true));
	$query = "insert into challenge_record (sess_id, challenge, timestamp)";
	$query .= " values ('". session_id() ."', '". $challenge ."', ". (time() + 60*5) . ")";
	DoQuery( $query, $gDbControl );
#
# Display the login
#
	echo <<<END
<table>
<tr>
	<td>E-mail Address:</td>
	<td><input type="text" name="email" id=default value="" size="32" ></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align=center>
		<input type=submit name=action value="Resend">
	</td>
</tr>
</table>
<input type="hidden" name="challenge" id="challenge" value="$challenge">
<input type="hidden" name="response" id="response" value="">
<input type="hidden" name="bypass" id="bypass" value="">

<script type="text/javascript">
	var e = document.getElementById( 'default' );
	if( e ) e.focus();
</script>
END;
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerFeatures()
{
	include( "globals.php" );
	if( $gTrace ) { 
		$gFunction[] = "UserManagerFeatures()";
		Logger();
	}
	
	echo "<h2>Features</h2>";
	echo "<div class=CommonV2>";

	$acts = array();
	$acts[] = "MySetValue('area','features')";
	$acts[] = "MySetValue('func','update')";
	$acts[] = "MySetValue('id', '" . $gUserId . "')";
	$acts[] = "MyAddAction('Update')";
	$tag = MakeTag('update');
	echo sprintf( "<input type=button onClick=\"%s\" $tag value=Update>", join(';',$acts ) );

	echo "<table>";
	echo "<tr>";
	echo "  <th>Feature</th>";
	echo "  <th>Enabled</th>";
	echo "  <th>Action</th>";
	echo "</tr>";
	
	foreach( $gFeatures as $row ) {
		$id = $row['id'];
		$jscript = "onChange=\"MyAddField('$id');MyToggleBgRed('update');\"";
		echo "<tr>";
		$tag = MakeTag('f_name',$id);
		printf( "<td><input type=text $tag $jscript value='%s'></td>", $row['name'] );
		$checked = $row['enabled'] ? "checked" : "";
		$tag = MakeTag('enabled', $id);
		printf( "<td class=c><input type=checkbox $tag value=1 $checked $jscript ></td>\n" );
		
		$acts = array();
		$acts[] = "MySetValue('area','features')";
		$acts[] = "MySetValue('func','del')";
		$acts[] = "MySetValue('id','$id')";
		$str = sprintf( "Are you sure you want to delete %s?", $row['name'] );
		$acts[] = "MyConfirm('$str')";
		echo sprintf( "<td class=c><input type=button onClick=\"%s\" value=Del></td>", join(';',$acts ) );
		echo "</tr>";
	}
	$id = 0;
	$jscript = "onChange=\"MyAddField('$id');MyToggleBgRed('update');\"";
	echo "<tr>";
	$tag = MakeTag('f_name',$id);
	printf( "<td><input type=text $tag value='%s'></td>", "" );
	echo "<td>&nbsp;</td>";
	
	$acts = array();
	$acts[] = "MySetValue('area','features')";
	$acts[] = "MySetValue('func','add')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<td class=c><input type=button onClick=\"%s\" value=Add></td>", join(';',$acts ) );

	echo "</tr>";
	echo "</table>";
}

function UserManagerInactive()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerInactive()";
		Logger();
	}
	
	if( empty( $GLOBALS[ 'gEnabled' ] ) ) {
		$level = $GLOBALS['gAccessLevel'];
		DoQuery( "select name from privileges where level = '$level'" );
		list( $name ) = mysql_fetch_array( $local_result );
		echo "$name access has been temporarily disabled.  ";
		
	} else if( empty( $GLOBALS[ 'gActive' ] ) ) {
		echo "Access to your account has been temporarily disabled.  ";
		
	}
	
	echo "Please try again later or click ";
	echo "<a href=\"mailto:lunches@tarbut.com?subject=Lunch account access disabled\">here</a>";
	echo " for support.";
	echo "<br><br>";
	echo "<input type=submit name=action value=Logout>";
}

function UserManagerInit()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerInit()";
		Logger();
	}
	
	$gUsers = array();
	$query = "select * from users order by username asc";
	DoQuery( $query, $gDbControl );
	while( $row = mysql_fetch_assoc( $gResult ) ) {
		$id = $row['id'];
		$gUsers[$id] = $row;
	}
	
	$gFeatures = array();
	DoQuery( "select * from features order by name asc", $gDbControl );
	while( $row = mysql_fetch_assoc( $gResult ) ) {
		$id = $row['id'];
		$gFeatures[$id] = $row;
	}
	
	$gLevels = array();
	$gLevelIdToLevel = array();
	$gLevelIdToName = array();
	$gLevelToName = array();
	$gLevelNameToVal = array();
	
	$query = "select * from levels order by level desc";
	DoQuery( $query, $gDbControl );
	while( $row = mysql_fetch_assoc( $gResult ) ) {
		$id = $row['id'];
		$level = $row['level'];
		$gLevels[$id] = $row;
		$gLevelIdToLevel[$id] = $level;
		$gLevelIdToName[$id] = $row['name'];
		$gLevelToName[$level] = $row['name'];
		$gLevelNameToVal[$row['name']] = $level;
	}
	
	$gPrivileges = array();
	$query = "select * from user_privileges";
	DoQuery( $query );
	while( $row = mysql_fetch_assoc( $gResult ) ) {
		$id = $row['id'];
		$gPrivileges[$id] = $row;
	}
	if( $gTrace) array_pop( $gFunction );
}

function UserManagerLoad( $userid )
{
	include( "globals.php" );	
	if( $gTrace ) {
		$gFunction[] = "UserManagerLoad()";
		Logger();
	}

	$query = "SELECT * from `users` WHERE `id` = '$userid'";
	DoQuery( $query, $gDbControl );
	$gUser = mysql_fetch_assoc( $gResult );
	$gUserId = $gUser['id'];
	$gUserVerified = 1;
	
	DoQuery( "select levelId, enabled from user_privileges where id = '$gUserId'", $gDbControl );
	list( $levelId, $gUserEnabled ) = mysql_fetch_array( $gResult );
	$gLevel = empty($gLevelIdToLevel[$levelId]) ? 0 : $gLevelIdToLevel[$levelId];
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerLogin()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerLogin()";
		Logger();
	}
#
# Remove old challenges
#
	$query = "delete from challenge_record where sess_id = '" . session_id() . "'";
	$query .= " or timestamp < " . time();
	DoQuery( $query, $gDbControl );
#
# Store a new challenge to use
#
	$challenge = SHA256::hash(uniqid(mt_rand(), true));
	$query = "insert into challenge_record (sess_id, challenge, timestamp)";
	$query .= " values ('". session_id() ."', '". $challenge ."', ". (time() + 60*5) . ")";
	DoQuery( $query, $gDbControl );
#
# Display the login
#
	$def_user = empty( $_POST['username'] ) ? "" : $_POST['username'];

	echo <<<END
<table>
<tr>
	<td>Username:</td>
	<td><input type="text" name="username" id="username" tabindex=1 value="$def_user " size="16" onkeydown="MyGetPassword(event);"></td>
END;
	if( ! empty( $gMessage1 ) ) { echo "<td class=msg>" . $gMessage1 . "</td>"; }

	echo <<<END
</tr>
<tr>
	<td>Password:</td>
	<td><input type="password" name="password" id="password" tabindex=2 value="" size="16" onkeydown="MyKeyDown(event);"></td>
END;
	if( ! empty( $gMessage2 ) ) { echo "<td class=msg>" . $gMessage2 . "</td>"; }
	
	echo <<<END
</tr>
<tr>
	<td colspan=2 align=center>
		<input type=submit value=Login tabindex=4 id=login onclick="MyChallengeResponse();">
		<input type=submit value="Reset Password" tabindex=5 onclick="MyAddAction( 'Reset Password' );">
	</td>
</tr>
</table>
<input type="hidden" name="challenge" id="challenge" value="$challenge">
<input type="hidden" name="response" id="response" value="">
<input type="hidden" name="bypass" id="bypass" value="">

<script type="text/javascript">
	var e = document.getElementById( 'username' );
	if( e ) e.focus();
</script>
END;

	if($gTrace) array_pop( $gFunction );
}

function UserManagerLogout() {
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerLogout()";
		Logger();
	}
	SessionStuff( 'logout' );
	SessionStuff('start');
}

function UserManagerPassword() {
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerPassword()";
		Logger();
	}
	
	$id = $gUserId;
	DoQuery( "select username from users where id = '$id'", $gDbControl );
	list( $username ) = mysql_fetch_array( $gResult );
	$disabled = empty( $username ) ? "" : "disabled";

	echo <<<END
<div align=center>
<div style="width:5in">
<br>
You will need to select a username if it is blank<br>
You will now need to select a new password.<br>
The password is secure and encrypted and never transmitted or stored in clear text.<br>

The UPDATE button will be activated once your password, entered twice, has been verified for a match.
<br><br>
</div>
<input type=hidden name=from value=UserManagerPassword>
<input type=hidden name=userid id=userid value=$id>
<input type=hidden name=id id=id value=$id>
<input type=hidden name=update_pass value=1>
<input type=hidden name=nobypass value=1>
<table class=norm>
<tr>
	<th class=norm>Username</th>
	<td><input type=text name=username id=username size=20 value="$username" $disabled></td>
</tr>
<tr>
	<th class=norm>Password
	<td class=norm><input type=password name=newpassword1 id=newpassword1 onKeyUp="MyVerifyPwd(1);" value="oneoneone" size=20>
</tr>
<tr>
	<th class=norm>One more time
	<td class=norm><input type=password name=newpassword2 id=newpassword2 onKeyUp="MyVerifyPwd(2);" value="twotwotwo" size=20>
</tr>
</table>
<br>
<a id=pwdval>&nbsp;</a>
<br><br>
END;

	$acts = array();
	$acts[] = "MyMungePwd()";
	$acts[] = "MySetValue('area','newpass')";
	$acts[] = "MyAddAction('Update')";
	$click = "onClick=\"" . join(';',$acts ) . "\"";
?>
<input type=button id=userSettingsUpdate name=userSettingsUpdate disabled <?php echo $click ?> value=Update></th>
<script type="text/javascript">MySetFocus('newpassword1');</script>
<?php
	if( $gTrace ) array_pop( $gFunction );
	exit;
}

function UserManagerLevels()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerLevels()";
		Logger();
	}
?>
<h2>Level Control</h2>
<input type=hidden name=from value=UserManagerLevels>
<input type=hidden name=userid id=userid>
<?php

		  $acts = array();
		  $acts[] = "MySetValue('area','levels')";
		  $acts[] = "MySetValue('func','modify')";
		  $acts[] = "MyAddAction('Update')";
		  echo sprintf( "<input type=button onClick=\"%s\" id=update value=Update>", join(';',$acts ) );

	echo "<br><br>";
	
	echo "<div class=CommonV2>";
	echo "<table>";
	echo "<tr>";
	echo "<th>Name</th>";
	echo "<th>Level</th>";
	echo "<th>Enabled</th>";
	echo "<th>Actions</ht>";
	echo "</tr>";

	DoQuery( "select * from levels order by level desc", $gDbControl );	
	while( $row = mysql_fetch_assoc( $gResult ) )
	{
		$id = $row['id'];
		$jscript = "onChange=\"MyAddField('$id');MyToggleBgRed('update');\"";
		echo "<tr>";
		echo "<td><input type=text size=8 name=p_${id}_name $jscript value=\"" . $row['name'] . "\"></td>";
		echo "<td><input type=text size=8 name=p_${id}_level $jscript value=\"" . $row['level'] . "\"></td>";
		$checked = empty( $row['enabled'] ) ? "" : "checked";
		echo "<td class=c><input type=checkbox name=p_${id}_enabled $jscript value=1 $checked></td>";

		$acts = array();
		$acts[] = "MySetValue('area','levels')";
		$acts[] = "MySetValue('func','delete')";
		$acts[] = "MySetValue('id','$id')";
		$str = sprintf( "Are you sure you want to delete name %s, level %d?", $row['name'], $row['level'] );
		$acts[] = "MyConfirm('$str')";
		echo sprintf( "<td class=c><input type=button onClick=\"%s\" value=Del></td>", join(';',$acts ) );

		echo "</tr>";
	}
	$id = 0;
	$jscript = "onChange=\"addField('$id');toggleBgRed('update');\"";
	echo "<tr>";
	echo "<td><input type=text size=8 name=p_${id}_name $jscript value=\"\"></td>";
	echo "<td><input type=text size=8 name=p_${id}_level $jscript value=\"\"></td>";
	echo "<td class=c><input type=checkbox name=p_${id}_enabled $jscript value=1 ></td>";

	$acts = array();
	$acts[] = "MySetValue('area','levels')";
	$acts[] = "MySetValue('func','add')";
	$acts[] = "MySetValue('id','$id')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<td class=c><input type=button onClick=\"%s\" value=Add></td>", join(';',$acts ) );

	echo "</tr>";
	echo "</table>";
	echo "</div>";
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerPrivileges()
{
	include( "globals.php" );
	if( $gTrace ) { 
		$gFunction[] = "UserManagerPrivileges()";
		Logger();
	}
	
	if( $gDb == $gDbControl ) {
		$tag = "Control";
	} elseif( $gDb == $gDbEEdge ) {
		$tag = "EEdge";
	} else {
		$tag = "Other";
	}
	echo "<h2>$tag Privileges</h2>";
	echo "<div class=CommonV2>";

	$acts = array();
	$acts[] = "MySetValue('area','privileges')";
	$acts[] = "MySetValue('func','update')";
	$acts[] = "MySetValue('id', '" . $gUserId . "')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<input type=button onClick=\"%s\" id=update value=Update>", join(';',$acts ) );

	echo "<table>";
	echo "<tr>";
	echo "  <th>User</th>";
	echo "  <th>Level</th>";
	echo "  <th>Enabled</th>";
	echo "  <th>Action</th>";
	echo "</tr>";
	
	$uids_used = array();
	
	DoQuery( "select id, levelId, enabled from user_privileges" );
	while( list( $uid, $lid, $ena ) = mysql_fetch_array( $gResult ) ) {
		$name = $gUsers[$uid]['username'];
		$uids_used[$uid] = 1;
		$jscript = "onChange=\"MySetValue('feature','$gFeature');MyAddField('$uid');MyToggleBgRed('update');\"";
		echo "<tr>";
		printf( "<td>%s</td>", $name );
		
		echo "<td>";
		$tag = MakeTag('levelId', $uid);
		echo "<select $tag $jscript>";
		foreach( $gLevelIdToName as $lid => $pname ) {
			$selected = ( $lid == $gPrivileges[$uid]['levelId'] ) ? "selected" : "";
			printf( "<option value=%d $selected>%s</option>", $lid, $pname );
		}
		echo "</select>";
		
		echo "</td>";
		$checked = $gPrivileges[$uid]['enabled'] ? "checked" : "";
		$tag = MakeTag('enabled', $uid);
		printf( "<td class=c><input type=checkbox $tag value=1 $checked $jscript ></td>\n" );
		
		echo "<td class=c>";
		$acts = array();
		$acts[] = "MySetValue('area','privileges')";
		$acts[] = "MySetValue('feature','$gFeature')";
		$acts[] = "MySetValue('func','delete')";
		$acts[] = "MyAddField('$uid')";
		$acts[] = "MyConfirm('Are you sure you want to delete $name from the privileges')";
		echo sprintf( "<input type=button onClick=\"%s\" id=update value=Del>", join(';',$acts ) );
		echo "</tr>";
	}
	
#	if( $gFeature != 'control' ) {
		echo "<tr>";
		
		DoQuery( "select * from users order by username asc", $gDbControl );
		$tag = MakeTag('uid', 0);
		echo "<td><select $tag>";
		echo "<option value=0>-- Click Here to Add --</option>";
		while( $row = mysql_fetch_assoc( $gResult ) ) {
			$uid = $row['id'];
			if( empty( $uids_used[$uid] ) ) {
				printf( "<option value=%d>%s</option>", $uid, $row['username'] );
			}
		}
		echo "</td>";
		
		echo "<td>";
		$tag = MakeTag('levelId', 0);
		echo "<select $tag $jscript>";
		echo "<option value=-1></option>";
		foreach( $gLevelIdToName as $lid => $name ) {
			printf( "<option value=%d>%s</option>", $lid, $name );
		}
		echo "</select>";		
		echo "</td>";
		
		echo "<td>&nbsp;</td>";
		
		echo "<td class=c>";
		$acts = array();
		$acts[] = "MySetValue('area','privileges')";
		$acts[] = "MySetValue('feature','$gFeature')";
		$acts[] = "MySetValue('func','add')";
		$acts[] = "MySetValue('id', '$gUserId')";
		$acts[] = "MyAddAction('Update')";
		echo sprintf( "<input type=button onClick=\"%s\" id=update value=Add>", join(';',$acts ) );
		echo "</td>";
		
		echo "</tr>";
#	}
	echo "</table>";
}

function UserManagerReport()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerReport";
		Logger();
	}
	
	echo "<input type=hidden name=from value=Users>";
	echo "<input type=hidden name=addr_list id=addr_list>";
	echo "<div id=users>";
	
	echo "<table>";
	
	echo "<tr>";
	echo "<th>#</th>";
	echo "<th>Name</th>";
	echo "<th colspan=2>Email</th>";
	echo "<th>Contact</th>";
	echo "<th>Last Login</th>";
	echo "</tr>";
	$i = 0;

	$query = "select * from users";
	$query .= " where access > 1 and access < '" . $gAccessLevels[ 'author' ] . "'";
	$query .= " order by last ASC";

	DoQuery( $query );
	
	while( $user = mysql_fetch_assoc( $local_result ) )
	{
		$userid = $user['userid'];
		
		$cl = ( $user['lastlogin'] == '0000-00-00 00:00:00' ) ? "class=never" : "";

		$i++;
		echo "<tr $cl>";
		echo "<td>$i</td>";
		echo sprintf( "<td>%s, %s</td>", $user['last'], $user['first'] );
		echo sprintf( "<td><a id=email_%s href=\"mailto:%s\">%s</a></td>", $userid, $user[ 'email' ], $user['email']);
		echo sprintf( "<td><input type=checkbox name=btn_email_%s id=btn_email_%s value=1 onclick=\"javascript:toggleEmail();\"></td>", $userid, $userid );
		
		$text = array();
		$text[] = "<div id=\"popup_members\">";
		$text[] = "<table>";
		$text[] = "<tr><th>Home Phone</th><td>" . FormatPhone( $user[ 'home' ] ) . "</td></tr>";
		$text[] = "<tr><th>Work Phone</th><td>" . FormatPhone( $user[ 'work' ] ) . "</td></tr>";
		$text[] = "<tr><th>Cell Phone</th><td>" . FormatPhone( $user[ 'cell' ] ) . "</td></tr>";
		$text[] = "<tr><th>Street</th><td>" . $user[ 'street' ] . "</td></tr>";
		$text[] = "<tr><th>City</th><td>" . $user[ 'city' ] . "</td></tr>";
		$text[] = "<tr><th>ZIP</th><td>" . $user[ 'zip' ] . "</td></tr>";
		$text[] = "</table>";
		$text[] = "</div>";

		$str = CVT_Str_to_Overlib( join( "", $text ) );
		$cap = sprintf( "Contact info for %s %s", $user['first'], $user['last'] );

		echo "<td><a href=\"javascript:void(0);\"" . 
				"onmouseover=\"return overlib('$str', CAPTION, '$cap', WIDTH, 300)\"" .
				"onmouseout=\"return nd();\">info</a></td>";

		if( $user['lastlogin'] == '0000-00-00 00:00:00' )
		{
			$str = "never";
		}
		else
		{
			$diff = time() - strtotime( $user['lastlogin'] );
			$days = $diff / 60 / 60 / 24;
			$str = sprintf( "%d days ago", $days );
		}
		echo "<td align=center>$str</td>";
		echo "</tr>";
	}
	
	echo "<tr>";
	echo "<td colspan=2>&nbsp;</td>";
	echo "<td><input type=button name=action value=Mail onclick=\"MyAddAction('Mail');\"></td>";
	echo "<td><input type=button id=btn_all_email name=btn_all_email value=All onclick=\"javascript:toggleEmail('all');\"></td>";
	echo "<td colspan=2>&nbsp;</td>";
	echo "</tr>";
	
	echo "</table>";
	echo "</div>";
	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerResend()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerResend()";
		Logger();
	}
	
	$email = $_POST["email"];
	
	$gNumRows = 0;
	
	if( ! empty( $email ) ) DoQuery( "select * from users where email = '$email'", $gDbControl );

	if( $gNumRows ) {
		$user = mysql_fetch_assoc( $gResult );
		$userid = $user['id'];
		
		$str = mt_rand();
		$new_password = substr( SHA256::hash( $str ), 0, 6 );
		$opts = array();
		$opts[] = "password = '$new_password'";
		$opts[] = "pwdchanged = '0000-00-00 00:00:00'";
		$opts[] = sprintf( "pwdexpires = '%s'", date( 'Y-m-d H:i:s', time() + 60*10 ) );
		$query = "update users set " . join( ',', $opts ) . " WHERE id = '$userid'";
		DoQuery( $query, $gDbControl );
		
		$uri = sprintf( "http://%s%s", $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI']);
		
		$body = array();
		$body[] = "Your password has been changed as follows:";
		$body[] = "";
		$body[] = "    Username: " . $user['username'];
		$body[] = "    Password: " . $new_password;
		$body[] = "";
		$body[] = "This combination will only be good for the next ten minutes";
		$body[] = "";
		$body[] = "To access the site again, please paste the following into your browswer or click on the following link:";
		$body[] = "";
		$body[] = "  ${uri}";
		$body[] = "";
      
		$gma = $gSupport;
		$from = is_array( $gma ) ? $gma : array( $gma );
			
		if( $GLOBALS['gMailLive'] ) {
			$name = $user['first'] . " " . $user['last'];
			$to = array( $user['email'] => $name );
		} else {
			$to = $from;
		}
		$subject = "Password Reset";
		
		if( $gSiteName == 'MacBook Pro' ) {
			echo join('<br>', $body );
		} else {
			$message = Swift_Message::newInstance( $subject );
			$message->setTo( $to );
			$message->setFrom( $from );
			$message->setBody( join( "\n", $body ), 'text/plain' );
			MyMail( $message );
		}
		echo "A reset link has been sent to $email";
	} else {
		echo "No user with that e-mail";
	}
	echo "<br><br>";
	echo "<input type=hidden name=from value=UserManagerResend>";
	echo "<input type=submit name=action value=Continue>";

	if( $gTrace ) array_pop( $gFunction );
}

function UserManagerSettings()
{
	include( "globals.php" );
	if( $gTrace ) { 
		$gFunction[] = "UserManagerSettings";
		Logger();
	}

	$num_args = func_num_args();
	switch( $num_args )
	{
		case( 1 ):
			$userid = func_get_arg( 0 );
			$mode = "";
			break;
		
		case( 2 ):
			$userid = func_get_arg( 0 );
			$mode = func_get_arg( 1 );
			break;
		
		default:
			echo "Bad # of arguments ($num_args) to UserManagerSettings<br>";
			exit;
	}

	DoQuery( "SELECT * from `users` WHERE `userid` = '$userid'" );
	$user = mysql_fetch_assoc( $local_result );
	
	echo "<input type=hidden name=from value=UserSettings$mode>";
	echo "<input type=hidden name=userid id=userid value=$userid>";
	echo "<input type=hidden name=id id=id>";
	echo "<input type=hidden name=update_pass id=update_pass value=0>";
	
	echo sprintf( "<h2>%s %s</h2>", $user['first'], $user['last'] );
	echo "<div id=settings>";
	echo "<table>";
	
	echo "<tr>";
	echo "<th>Last Login</th>";
	$ts = strtotime( $user[ 'lastlogin' ] );
	echo sprintf( "<td class=transp>%s</td>", date( "Y, M j, g:i A", $ts ) );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Username</th>";
	echo sprintf( "<td><input type=text name=username value=\"%s\"></td>", $user[ 'username' ] );
	echo "<th></th>";
	echo "</tr>";
	
	if( $gAccess >= $gAccessLevels[ 'author' ] )
	{
		echo "<tr>";
		echo "<th>Last</th>";
		echo sprintf( "<td><input type=text name=last value=\"%s\"></td>", $user[ 'last' ] );
		echo "<th></th>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>First</th>";
		echo sprintf( "<td><input type=text name=first value=\"%s\"></td>", $user[ 'first' ] );
		echo "<th></th>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Access</th>";
		echo "<td>";
		echo "<select name=access>";
		foreach( $gAccessLevels as $level ) {
			$opt = ( $user['access'] == $level ) ? "selected" : "";
			echo sprintf( "<option value=%s $opt>%s</option>", $level, $gAccessLevelToName[ $level ] );
		}
		echo "</select>";
		echo "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<th>Active</th>";
		echo sprintf( "<td><input type=text name=active value=\"%s\"></td>", $user[ 'active' ] );
		echo "<th></th>";
		echo "</tr>";
	}
	
	DoQuery( "select * from contacts where id = $userid" );
	$user = mysql_fetch_assoc( $local_result );
	
	echo "<tr>";
	echo "<th>Home Phone</th>";
	echo sprintf( "<td><input type=text name=home value=\"%s\"></td>", FormatPhone( $user[ 'home' ] ) );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Work Phone</th>";
	echo sprintf( "<td><input type=text name=work value=\"%s\"></td>", FormatPhone( $user[ 'work' ] ) );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Cell Phone</th>";
	echo sprintf( "<td><input type=text name=cell value=\"%s\"></td>", FormatPhone( $user[ 'cell' ] ) );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Street</th>";
	echo sprintf( "<td><input type=text name=street value=\"%s\"></td>", $user[ 'street' ] );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>City</th>";
	echo sprintf( "<td><input type=text name=city value=\"%s\"></td>", $user[ 'city' ] );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Zip</th>";
	echo sprintf( "<td><input type=text name=zip value=\"%s\"></td>", $user[ 'zip' ] );
	echo "<th></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Password</th>";
	echo "<td><input type=password id=newpassword1 name=newpassword1 onKeyUp=\"verifypwd(3);\"></td>";
	echo "<th align=left></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th>Confirm</th>";
	echo "<td><input type=password id=newpassword2 name=newpassword2 onKeyUp=\"verifypwd(4);\"></td>";
	echo "<th align=left><a id=\"pwdval\">&nbsp;</a></th>";
	echo "</tr>";
	
	echo "<tr>";
	echo "<th></th>";
	echo "<th align=center><input type=button class=btn id=userSettingsUpdate name=action onClick=\"MyMungePwd(); MySetValue( 'userid', '$userid'); MyAddAction('Update');\" value=Update></th>";
	echo "<th></th>";
	echo "</tr>";
	
	echo "</table>";
	echo "</div>";
}

function UserManagerUpdate()
{
	include( "globals.php" );
	
	if( $gTrace ) {
		if( func_num_args() > 0 ) {
			$args = func_get_args();
			$vargs = join( ',', $args );
		} else {
			$vargs = "";
		}
		$gFunction[] = "UserManagerUpdate($vargs)";
		Logger();
	}

	$id = $_POST[ 'id' ];
	if( empty( $gUserId ) ) {
		$gUserId = $id;
		$_SESSION['userid'] = $id;
	}
	
	$userid = $GLOBALS['gUserId'];
	
	if( ! empty( $_POST[ 'update_pass' ] ) )
	{
		$newpwd = $_POST[ 'newpassword1' ];
		$query = "update users set pwdchanged = now(), password = '$newpwd' where id = '$id'";
		DoQuery( $query, $gDbControl );
		$gPasswdChanged = date( "Y-m-d H:i:s" );
		unset( $text );
		$text[] = "insert event_log set time=now()";
		$text[] = "type = 'pwd change'";
		$text[] = "userid = '$userid'";
		$text[] = "item = 'n/a'";
		$query = join( ",", $text );
		DoQuery( $query, $gDbControl );
		
		$GLOBALS['gAction'] = 'Main';
	}
	
	$area = $_POST['area'];
	$func = $_POST['func'];
	
	if( $area == 'features' ) {
		if( $func == 'add' ) {
			$id = $_POST['id'];
			$query = sprintf( "insert into features set name = '%s', enabled = 0", $_POST['f_name_0'] );
			DoQuery( $query, $gDbControl );
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'feature'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = '%s'", str_replace( "'", "\'", $query ) );
			$query = join( ',', $text );
			DoQuery( $query, $gDbControl );
		}

		if( $func == 'del' ) {
			$id = $_POST['id'];
			$query = "delete from features where id = '$id'";
			DoQuery( $query, $gDbControl );
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'feature'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = '%s'", str_replace( "'", "\'", $query ) );
			$query = join( ',', $text );
			DoQuery( $query, $gDbControl );
		}

		if( $func == 'update' ) {
			$tmp = preg_split( '/,/', $_POST['fields'] );
			$ids = array_unique($tmp);
			foreach( $ids as $id ) {
				if( empty( $id ) ) continue;
				$name = $_POST['f_name_' . $id];
				$ena = empty( $_POST['enabled_' . $id] ) ? 0 : 1;
				$acts = array();
				if( $name != $gFeatures[$id]['name'] ) {
					$acts[] = "name = '$name'";
				}
				if( $ena != $gFeatures[$id]['enabled'] ) {
					$acts[] = "`enabled` = '$ena'";
				}
				if( empty( $acts ) ) continue;
				$query = "update features set " . join( ',', $acts ) . " where id = '$id'";
				DoQuery( $query, $gDbControl );
				
				$text = array();
				$text[] = "insert event_log set time=now()";
				$text[] = "type = 'feature'";
				$text[] = "userid = '$userid'";
				$text[] = sprintf( "item = '%s'", str_replace( "'", "\'", $query ) );
				$query = join( ',', $text );
				DoQuery( $query, $gDbControl );
			}
		}
	}
	
	if( $area == "levels" ) {
		if( $func == "add" ) {
			$acts = array();
			$acts[] = sprintf( "name = '%s'", addslashes( $_POST['p_0_name'] ) );
			$acts[] = sprintf( "level = '%d'", $_POST['p_0_level'] );
			$val = isset( $_POST['p_0_enabled'] ) ? 1 : 0;
			$acts[] = "enabled = '$val'";
			$query = "insert into levels set " . join(',',$acts );
			DoQuery( $query, $gDbControl );
			$id = mysql_insert_id();
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'privilege'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = 'add %s'", $_POST['p_0_name'], $id );
			$query = join( ',', $text );
			DoQuery( $query, $gDbControl );
		}
		
		if( $func == "delete" ) {
			$id = $_POST['id'];
			DoQuery( "select * from levels where id = '$id'", $gDbControl );
			$row = mysql_fetch_assoc( $gResult);
			
			$query = "delete from levels where id = '$id'";
			DoQuery( $query, $gDbControl );
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'privilege'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = 'delete %s'", $row['name'], $id );
			$query = join( ',', $text );
			DoQuery( $query, $gDbControl );
		}
		
		if( $func == "modify" ) {
			$done = array();
			$pids = preg_split('/,/', $_POST['fields']);
			foreach( $pids as $pid ) {
				if( ! empty( $pid ) ) {
					if( array_key_exists( $pid, $done ) ) continue;
					$done[$pid] = 1;
					$query = "select * from levels where id = '$pid'";
					DoQuery( $query, $gDbControl );
					$row = mysql_fetch_assoc( $gResult );
					$acts = array();
					
					$tag = "p_${pid}_name";
					if( strcmp( $_POST[$tag], $row['name'] ) ) $acts[] = "name = '" . addslashes( $_POST[$tag] ) . "'";
					
					$tag = "p_${pid}_level";
					if( $_POST[$tag] !== $row['level'] ) $acts[] = "level = '" . $_POST[$tag] . "'";
					
					$tag = "p_${pid}_enabled";
					$val = isset( $_POST[$tag] ) ? 1 : 0;
					if( $val !== $row['enabled'] ) $acts[] = "enabled = '$val'";
					
					if( count( $acts ) ) {
						$query = "update levels set " . join( ',', $acts ) . " where id = '$pid'";
						DoQuery( $query, $gDbControl );
						
						$text = array();
						$text[] = "insert event_log set time=now()";
						$text[] = "type = 'privilege'";
						$text[] = "userid = '$userid'";
						$text[] = sprintf( "item = 'update %s(%d), set %s'", $row['name'], $pid, addslashes( join(',', $acts ) ) );
						$query = join( ',', $text );
						DoQuery( $query, $gDbControl );
					}
				}
			}
		}
	}

	if( $area == 'privileges' ) {
		if( $func == 'add' ) {
			$uid = $_POST['uid_0'];
			$lid = $_POST['levelId_0'];
			$query = "insert into user_privileges set id = '$uid', levelId = '$lid'";
			DoQuery( $query );
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'privilege'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = '%s'", str_replace( "'", "\'", $query ) );
			$query = join( ',', $text );
			DoQuery( $query );

		} elseif( $func == 'delete' ) {
			$uid = $_POST['fields'];
			$query = "delete from user_privileges where id = '$uid'";
			DoQuery( $query );
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'privilege'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = '%s'", str_replace( "'", "\'", $query ) );
			$query = join( ',', $text );
			DoQuery( $query );

		} elseif( $func == 'update' ) {
			$tmp = preg_split( '/,/', $_POST['fields'] );
			$uids = array_unique($tmp);
			foreach( $uids as $uid ) {
				if( empty( $uid ) ) continue;
				$lid = $_POST['levelId_' . $uid];
				$ena = empty( $_POST['enabled_' . $uid] ) ? 0 : 1;
				$acts = array();
				if( $lid != $gPrivileges[$uid]['levelId'] ) {
					$acts[] = "levelId = '$lid'";
				}
				if( $ena != $gPrivileges[$uid]['enabled'] ) {
					$acts[] = "`enabled` = '$ena'";
				}
				if( empty( $acts ) ) continue;
				$query = "update user_privileges set " . join( ',', $acts ) . " where id = '$uid'";
				DoQuery( $query );
				
				$text = array();
				$text[] = "insert event_log set time=now()";
				$text[] = "type = 'privilege'";
				$text[] = "userid = '$userid'";
				$text[] = sprintf( "item = '%s'", str_replace( "'", "\'", $query ) );
				$query = join( ',', $text );
				DoQuery( $query );
			}
		}
	}

	if( $area == 'users' ) {	
		if( $func == "add" ) {
			$uname = addslashes( $_POST['username'] );
			
			$acts = array();
			$acts[] = sprintf( "username = '%s'", $uname );
			$acts[] = sprintf( "last = '%s'", addslashes( $_POST['last'] ) );
			$acts[] = sprintf( "first = '%s'", addslashes( $_POST['first'] ) );
			$acts[] = sprintf( "email = '%s'", addslashes( $_POST['email'] ) );
			$acts[] = sprintf( "password = '%s'", md5( sprintf( "%d", time())));
			$acts[] = sprintf( "active = '1'" );
			$query = "insert into users set " . join(',', $acts );
			DoQuery( $query, $gDbControl );
			$uid = mysql_insert_id();
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'user'";
			$text[] = "userid = '$id'";
			$text[] = sprintf( "item = 'add %s(%d), set %s'", $uname, $uid, addslashes( join(',', $acts ) ) );
			$query = join( ',', $text );
			DoQuery( $query, $gDbControl );
		}
	
		if( $func == "delete" ) {
			$id = $_POST['id'];
			$query = "delete from users where id = '$id'";
			DoQuery( $query, $gDbControl );
			
			$query = "delete from user_privileges where id = '$id'";
			DoQuery( $query, $gDbControl );
	
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'user'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = 'delete %s(%d)'", $_POST["u_${id}_username"], $id );
			$query = join( ',', $text );
			DoQuery( $query, $gDbControl );
		}
	
		if( $func == "update" ) {
			$done = array();
			$uids = preg_split( '/,/', $_POST['fields'] );
			foreach( $uids as $uid ) {
				if( ! empty( $uid ) ) {
					if( array_key_exists( $uid, $done ) ) continue;
					$done[ $uid ] = 1;
					$query = "select * from users where id = '$uid'";
					DoQuery( $query, $gDbControl );
					$user = mysql_fetch_assoc( $gResult );
					
					$acts = array();
					
					$tag = "u_${uid}_first";
					if( strcmp( $_POST[$tag], $user['first'] ) ) $acts[] = "first = '" . addslashes( $_POST[$tag] ) . "'";
					
					$tag = "u_${uid}_last";
					if( strcmp( $_POST[$tag], $user['last'] ) ) $acts[] = "last = '" . addslashes( $_POST[$tag] ) . "'";
					
					$tag = "u_${uid}_username";
					if( strcmp( $_POST[$tag], $user['username'] ) ) $acts[] = "username = '" . addslashes( $_POST[$tag] ) . "'";
					
					$tag = "u_${uid}_email";
					if( strcmp( $_POST[$tag], $user['email'] ) ) $acts[] = "email = '" . addslashes( $_POST[$tag] ) . "'";
					
					$tag = "u_${uid}_active";
					$val = isset( $_POST[$tag] ) ? 1 : 0;
					if( $val != $user['active'] ) $acts[] = "active = '${val}'";
			
					if( count( $acts ) ) {
						$query = "update users set " . join( ',', $acts ) . " where id = '$uid'";
						DoQuery( $query, $gDbControl );
						if( ! $gNumRows ) {
							$acts = array();
							foreach( array( 'first','last','email','username') as $fld ) {
								$tag = sprintf( "u_%d_%s", $uid, $fld );
								$acts[] = sprintf( "%s = '%s'", $fld, addslashes( $_POST[$tag] ) );
							}
							$query = "insert into users set " . join( ',', $acts );
							DoQuery($query, $gDbControl );
						}
						
						$text = array();
						$text[] = "insert event_log set time=now()";
						$text[] = "type = 'user'";
						$text[] = "userid = '$userid'";
						$text[] = sprintf( "item = 'update %s(%d), set %s'", $user['username'], $uid, addslashes( join(',', $acts ) ) );
						$query = join( ',', $text );
						DoQuery( $query, $gDbControl );
					}
				}
			}
		}
	}
	
	if( $gTrace ) array_pop( $GLOBALS[ 'gFunction' ] );
}

function UserManagerVerify() {
	include( "globals.php" );
	if( $gTrace ) {
		if( func_num_args() > 0 ) {
			$args = func_get_args();
			$vargs = join( ',', $args );
		} else {
			$vargs = "";
		}
		$gFunction[] = "UserManagerVerify($vargs)";
		Logger();
	}
	$ok = 0;
	if( ! $gUserVerified )
	{
		$_SESSION['authenticated'] = 0;
		$gAction = "Start";
		if( empty( $_POST[ 'username' ] ) && $_POST['bypass'] != 1 )
		{
			$gMessage1 = "&nbsp;** Please enter your username";
			if( $gTrace ) array_pop( $gFunction );
			return;
		}
		
		if( !isset( $_POST[ 'password' ] ) || $_POST['password'] == "empty" )
		{
			$gMessage2 = "&nbsp;** Please enter your password";
			if( $gTrace ) array_pop( $gFunction );
			return;
		}
		
		$query = "select challenge from challenge_record";
		$query .= " where sess_id = '" . session_id() . "' and timestamp > " . time();
		DoQuery( $query, $gDbControl );
		$c_array = mysql_fetch_assoc( $gResult );
		if( empty( $_POST['username'] ) ) {
			$query = "select id, username, password from users where password = '" . $_POST['response'] . "'";
			DoQuery( $query, $gDbControl );
			$ok = $gNumRows > 0;
			if( $ok )
			{
				$user = mysql_fetch_assoc( $gResult );
				UserManager( 'load', $user['id'] );
				UserManager( 'newpassword' );
			}
			
		} else {
			$uname = trim($_POST['username']);
			$query = "select id, username, password, pwdexpires from users where username = '$uname'";
			DoQuery( $query, $gDbControl );
			if( $gNumRows > 0 ) {
				$now = date( 'Y-m-d H:i:s' );
				$user = mysql_fetch_assoc( $gResult );
				$pass = ( strlen( $user['password'] ) == 64 ) ? $user['password'] : SHA256::hash($user['password'] );
				$response_string = strtolower($user['username']) . ':' . $pass . ':' . $c_array['challenge'];
				$expected_response = SHA256::hash($response_string);
				$ok = ( $_POST['response'] == $expected_response ) ? 1 : 0;
				if( $now > $user['pwdexpires'] ) {
					$gMessage2 = "Your password has expired!  Click on:  Reset Password";
					$ok = 0;
				} else {
					Logger("good, ok: $ok");
				}
				if( ! $ok ) {
					$gMessage2 = "&nbsp;** Invalid password.  Please try again or press Reset Password";
				}
			} else {
				$ok = false;
#				$gMessage1 = "&nbsp;** Invalid username";
				$gMessage2 = "&nbsp;** Invalid password.  Please try again or press Reset Password";
			}
		}
		if( $ok > 0 )
		{
			$_SESSION['authenticated'] = 1;
			$_SESSION['userid'] = $user['id'];
			
			UserManager( 'load', $user['id'] );
			$ts = time();
			$expires = date( 'Y-m-d H:i:s', $ts + 60*60*24*60 ); # two months
			DoQuery( "update users set lastlogin = now(), pwdexpires='$expires' where id = '" . $user['id'] . "'", $gDbControl );
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'login'";
			$text[] = sprintf( "userid = '%d'", $user['id'] );
			$text[] = sprintf( "item = '%s'", $_SERVER[ 'HTTP_USER_AGENT' ] );
			$query = join( ",", $text );
			DoQuery( $query, $gDbControl );
			$gAction = ( empty( $GLOBALS['gEnabled'] ) || empty( $GLOBALS['gActive'] ) ) ? "Inactive" : "Welcome";
		}
		else
		{
			$local_numrows = 0;
			if( ! empty( $_POST[ 'username' ] ) )
			{
				$query = "select id from users where username = '$uname'";
				DoQuery( $query, $gDbControl );
				if( $gNumRows == 0 ) {
#					$gMessage1 = "&nbsp;** Invalid username: " . $_POST['username'];  Don't give away information
					$gMessage2 = "&nbsp;** Invalid password.  Please try again or press Reset Password";
				}
			} else {
				$gMessage2 = "&nbsp;** Invalid password.  Please try again or press Reset Password";
			}
			$gAction = "Start";
		}
	} else {
		$gAction = empty( $gActive ) ? "Inactive" : "Welcome";
	}
	if( $gTrace ) array_pop( $gFunction );
}

?>
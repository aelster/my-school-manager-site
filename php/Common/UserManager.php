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
			return UserManagerAuthorized( func_get_arg( 1 ) );
			break;
		
		case( 'control' ):
			UserManagerControl();
			break;

		case( 'inactive' );
			UserManagerInactive();
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

function UserManagerAuthorized( $privilege )
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerAuthorized($privilege)";
		Logger();
	}
	$level = $GLOBALS['gAccessLevel'];
	$ok = ( $level >= $gAccessNameToLevel[ $privilege ] ) ? 1 : 0;
	$ok = $ok && $gAccessLevelEnabled[ $level ];
	
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
		$gFunction[] = "CommonV2:UserManagerDisplay()";
		Logger();
	}
	
	echo "<h2>User Control</h2>";
	echo "<div class=CommonV2>";
	echo "<input type=hidden name=from value=Users>";
	echo sprintf( "<input type=hidden name=userid value='%d'>", $GLOBALS['gUserId'] );

	$acts = array();
	$acts[] = "MyAddAction('Back')";
	echo sprintf( "<input type=button onClick=\"%s\" value=Back>", join(';',$acts ) );

	$acts = array();
	$acts[] = "MySetValue('area','update')";
	$acts[] = "MySetValue('id', '" . $GLOBALS['gUserId'] . "')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<input type=button onClick=\"%s\" id=update value=Update>", join(';',$acts ) );
	
	$vprivs = array();
	$vlevels = array();
	DoQuery( "select name, id, level from privileges order by level desc" );
	while( list( $name, $id, $level ) = mysql_fetch_array( $local_result ) )
	{
		$vprivs[ $name ] = $id;
		$vlevels[ $name ] = $level;
	}
	
	foreach( $gAccessLevels as $level )
	{
		if( ! UserManagerAuthorized($level) ) continue;
		$i = 0;
	
		$pid = $gAccessNameToId[ $level ];
		$query = "select * from users, access where";
		$query .= " users.userid = access.userid and access.privid = '$pid' order by users.username ASC";
		DoQuery( $query );
		
		if( $local_numrows ) {
			echo "<h3>" . $level. "</h3>";

			echo "<table class=sortable>";
			echo "<tr>";
			echo "<th>#</th>";
			echo "<th>Username</th>";
			echo "<th>First</th>";
			echo "<th>Last</th>";
			echo "<th>E-Mail</th>";
			echo "<th>Access</th>";
			echo "<th>Last Login</th>";
			echo "<th>Active</th>";
			echo "<th>Actions</th>";
			echo "</tr>";
		}
		
		$j = 1;
		while( $user = mysql_fetch_assoc( $local_result ) )
		{
			$id = $user['userid'];
			$jscript = "onChange=\"addField('$id');toggleBgRed('update');\"";
			
			echo "<tr>";
			printf( "<td class=sorttable_nosort>$j</td>" );
			echo sprintf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_username value=\"%s\" $jscript size=10></td>\n", $user['username'], $id, $user['username']);
			echo sprintf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_first value=\"%s\" $jscript size=10></td>\n", $user['first'], $id, $user['first']);
			echo sprintf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_last value=\"%s\" $jscript size=10></td>\n", $user['last'], $id, $user['last']);
			echo sprintf( "<td sorttable_customkey=\"%s\"><input type=text name=u_%d_email value=\"%s\" $jscript size=20></td>\n", $user['email'], $id, $user['email']);

			echo sprintf( "<td><select name=u_%d_privid $jscript>", $id );
			foreach( $vprivs as $name => $privid )
			{
				if( $vlevels[$name] > $GLOBALS['gAccessLevel'] ) continue;
				$opt = ( $user['PrivId'] == $privid ) ? 'selected' : '';
				echo "<option value=$privid $opt>$name</option>";
			}
			echo "</select></td>";

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

			echo "<td>";
			$acts = array();
			$acts[] = "MySetValue('area','delete')";
			$acts[] = "MySetValue('id', '$id')";
			$name = sprintf( "%s %s", $user['first'], $user['last'] );
			$acts[] = "myConfirm('Are you sure you want to delete $name')";
			echo sprintf( "<input type=button onClick=\"%s\" id=update value=Del>", join(';',$acts ) );
	
			echo "</tr>";
			$j++;
		}
		if( $local_numrows ) echo "</table>";
	}
	echo "<h3>New Member</h3>";

	echo "<table>";
	
	echo "<tr>";
	echo "<th>Username</th>";
	echo "<th>First</th>";
	echo "<th>Last</th>";
	echo "<th>E-Mail</th>";
	echo "<th>Access</th>";
	echo "<th>Actions</th>";
	echo "</tr>";

	echo "<tr>";
	echo "<td><input type=text name=username size=20></td>";
	echo "<td><input type=text name=first size=20></td>";
	echo "<td><input type=text name=last size=20></td>";
	echo "<td><input type=text name=email size=20></td>";
	echo "<td><select name=privid>";
	DoQuery( "select name, id from privileges order by level asc" );
	while( list( $name, $level ) = mysql_fetch_array( $local_result ) )
	{
		if( $vlevels[$name] > $GLOBALS['gAccessLevel'] ) continue;
		echo "<option value=$level>$name</option>";
	}
	echo "</select></td>";
	echo "<td>";
	$acts = array();
	$acts[] = "MySetValue('area','add')";
	$acts[] = "MySetValue('id', '" . $GLOBALS['gUserId'] . "')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<input type=button onClick=\"%s\" id=update value=Add>", join(';',$acts ) );
	echo "</td>";
	
	echo "</tr>";
	
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
	
	$gAccessNameToId = array();
	$gAccessNameToLevel = array();
	$gAccessNameEnabled = array();
	$gAccessLevelEnabled = array();
	$gAccessLevels = array();
	
	$query = "select * from access_levels order by level desc";
	DoQuery( $query, $gDbControl );
	while( $row = mysql_fetch_assoc( $gResult ) ) {
		$name = $row['name'];
		$gAccessNameToId[$name] = $row[ 'id' ];
		$gAccessNameToLevel[$name] = $row[ 'level' ];
		$gAccessNameEnabled[$name] = $row[ 'enabled' ];
		$gAccessLevelEnabled[ $row[ 'level' ] ] = $row[ 'enabled' ];
		$gAccessLevels[] = $name;
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
/*	
	$query = "select privileges.level, privileges.enabled from privileges, access ";
	$query .= " where access.privid = privileges.id and access.userid = '$userid'";
	DoQuery( $query, $gDbControl );
	list( $priv, $enabled ) = mysql_fetch_array( $local_result );
	$GLOBALS['gAccessLevel'] = $priv;
	$GLOBALS['gEnabled'] = $enabled;
	$_SESSION['username'] = $row['username'];
*/
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
	$acts[] = "MySetValue('area','new_pass')";
	$acts[] = "MyAddAction('Update')";
	$click = "onClick=\"" . join(';',$acts ) . "\"";
?>
<input type=button id=userSettingsUpdate name=userSettingsUpdate disabled <?php echo $click ?> value=Update></th>
<?php
	if( $gTrace ) array_pop( $gFunction );
	exit;
}

function UserManagerPrivileges()
{
	include( "globals.php" );
	if( $gTrace ) {
		$gFunction[] = "UserManagerPrivileges()";
		Logger();
	}
?>
<h2>Privilege Control</h2>
<input type=hidden name=from value=UserManagerPrivileges>
<input type=hidden name=userid id=userid>
<?php
		  $acts = array();
		  $acts[] = "MySetValue('func','display')";
		  $acts[] = "MyAddAction('Back')";
		  echo sprintf( "<input type=button onClick=\"%s\" value=Back>", join(';',$acts ) );

		  $acts = array();
		  $acts[] = "MySetValue('area','privileges')";
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

	DoQuery( "select * from privileges order by level desc" );	
	while( $row = mysql_fetch_assoc( $local_result ) )
	{
		$id = $row['id'];
		$jscript = "onChange=\"addField('$id');toggleBgRed('update');\"";
		echo "<tr>";
		echo "<td><input type=text size=8 name=p_${id}_name $jscript value=\"" . $row['name'] . "\"></td>";
		echo "<td><input type=text size=8 name=p_${id}_level $jscript value=\"" . $row['level'] . "\"></td>";
		$checked = empty( $row['enabled'] ) ? "" : "checked";
		echo "<td class=c><input type=checkbox name=p_${id}_enabled $jscript value=1 $checked></td>";

		$acts = array();
		$acts[] = "MySetValue('area','privileges')";
		$acts[] = "MySetValue('func','delete')";
		$acts[] = "MySetValue('id','$id')";
		$acts[] = "myConfirm('Update')";
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
	$acts[] = "MySetValue('area','privileges')";
	$acts[] = "MySetValue('func','add')";
	$acts[] = "MySetValue('id','$id')";
	$acts[] = "MyAddAction('Update')";
	echo sprintf( "<td><input type=button onClick=\"%s\" value=Add></td>", join(';',$acts ) );

	echo "</tr>";
	echo "</table>";
	echo "</div>";
	if( $gTrace ) array_pop( $gFunction );
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
		$message = Swift_Message::newInstance( $subject );
		$message->setTo( $to );
		$message->setFrom( $from );
		$message->setBody( join( "\n", $body ), 'text/plain' );
		MyMail( $message );

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
		DoQuery( $query );
		$gPasswdChanged = date( "Y-m-d H:i:s" );
		unset( $text );
		$text[] = "insert event_log set time=now()";
		$text[] = "type = 'pwd change'";
		$text[] = "id = '$userid'";
		$text[] = "item = 'n/a'";
		$query = join( ",", $text );
		DoQuery( $query, $gDbControl );
		
		$GLOBALS['gAction'] = 'Main';
	}
	
	$area = $_POST['area'];
	$func = $_POST['func'];
	
	if( $area == "add" ) {
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
		DoQuery( $query );
		
		$acc = $_POST['privid'];
		$acts = array();
		$acts[] = "userid = '$uid'";
		$acts[] = "privid = '$acc'";
		$query = "insert into access set " . join( ',', $acts );
		DoQuery( $query );

		$text = array();
		$text[] = "insert event_log set time=now()";
		$text[] = "type = 'access'";
		$text[] = "userid = '$id'";
		$text[] = sprintf( "item = 'update %s(%d), set %s'", $uname, $uid, addslashes( join(',', $acts ) ) );
		$query = join( ',', $text );
		DoQuery( $query );
	}
	
	if( $area == "delete" ) {
		$id = $_POST['id'];
		$query = "delete from users where userid = '$id'";
		DoQuery( $query );
		
		$text = array();
		$text[] = "insert event_log set time=now()";
		$text[] = "type = 'user'";
		$text[] = "userid = '$userid'";
		$text[] = sprintf( "item = 'delete %s(%d)'", $_POST["u_${id}_username"], $id );
		$query = join( ',', $text );
		DoQuery( $query );
		
		DoQuery( "delete from access where userid = '$id'" );
		
		DoQuery( "show tables like 'grades'" );
		if( $local_numrows ) {
			DoQuery( "delete from grades where userid = '$id'" );
		}
	}
	
	if( $area == "privileges" ) {
		if( $func == "add" ) {
			$acts = array();
			$acts[] = sprintf( "name = '%s'", addslashes( $_POST['p_0_name'] ) );
			$acts[] = sprintf( "level = '%d'", $_POST['p_0_level'] );
			$val = isset( $_POST['p_0_enabled'] ) ? 1 : 0;
			$acts[] = "enabled = '$val'";
			$query = "insert into privileges set " . join(',',$acts );
			DoQuery( $query );
			$id = mysql_insert_id();
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'privilege'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = 'add %s'", $_POST['p_0_name'], $id );
			$query = join( ',', $text );
			DoQuery( $query );
		}
		
		if( $func == "delete" ) {
			$id = $_POST['id'];
			DoQuery( "select * from privileges where id = '$id'");
			$row = mysql_fetch_assoc( $local_result );
			
			$query = "delete from privileges where id = '$id'";
			DoQuery( $query );
			
			$text = array();
			$text[] = "insert event_log set time=now()";
			$text[] = "type = 'privilege'";
			$text[] = "userid = '$userid'";
			$text[] = sprintf( "item = 'delete %s'", $row['name'], $id );
			$query = join( ',', $text );
			DoQuery( $query );
		}
		
		if( $func == "modify" ) {
			$done = array();
			$pids = preg_split('/,/', $_POST['fields']);
			foreach( $pids as $pid ) {
				if( ! empty( $pid ) ) {
					if( array_key_exists( $pid, $done ) ) continue;
					$done[$pid] = 1;
					$query = "select * from privileges where id = '$pid'";
					DoQuery( $query );
					$row = mysql_fetch_assoc( $local_result );
					$acts = array();
					
					$tag = "p_${pid}_name";
					if( strcmp( $_POST[$tag], $row['name'] ) ) $acts[] = "name = '" . addslashes( $_POST[$tag] ) . "'";
					
					$tag = "p_${pid}_level";
					if( $_POST[$tag] !== $row['level'] ) $acts[] = "level = '" . $_POST[$tag] . "'";
					
					$tag = "p_${pid}_enabled";
					$val = isset( $_POST[$tag] ) ? 1 : 0;
					if( $val !== $row['enabled'] ) $acts[] = "enabled = '$val'";
					
					if( count( $acts ) ) {
						$query = "update privileges set " . join( ',', $acts ) . " where id = '$pid'";
						DoQuery( $query );
						
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


	if( $area == "update" ) {
		$done = array();
		$uids = preg_split( '/,/', $_POST['fields'] );
		foreach( $uids as $uid ) {
			if( ! empty( $uid ) ) {
				if( array_key_exists( $uid, $done ) ) continue;
				$done[ $uid ] = 1;
				$query = "select * from users where userid = '$uid'";
				DoQuery( $query );
				$user = mysql_fetch_assoc( $local_result );
				
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
					$query = "update users set " . join( ',', $acts ) . " where userid = '$uid'";
					DoQuery( $query );
					if( $local_numrows == 0 ) {
						$acts = array();
						foreach( array( 'first','last','email','username') as $fld ) {
							$tag = sprintf( "u_%d_%s", $uid, $fld );
							$acts[] = sprintf( "%s = '%s'", $fld, addslashes( $_POST[$tag] ) );
						}
						$query = "insert into users set " . join( ',', $acts );
						DoQuery($query );

						$tag = sprintf( "u_%d_%s", $uid, 'privid');
						$acc = $_POST[$tag];
						$acts = array();
						$acts[] = "userid = '$uid'";
						$acts[] = "privid = '$acc'";
						$query = "insert into access set " . join( ',', $acts );
						DoQuery( $query );

					}
					
					$text = array();
					$text[] = "insert event_log set time=now()";
					$text[] = "type = 'user'";
					$text[] = "userid = '$userid'";
					$text[] = sprintf( "item = 'update %s(%d), set %s'", $user['username'], $uid, addslashes( join(',', $acts ) ) );
					$query = join( ',', $text );
					DoQuery( $query );
				}
				
				$query = "select * from access where userid = '$uid'";
				DoQuery( $query );
				$access = mysql_fetch_assoc( $local_result );
				
				$tag = "u_${uid}_privid";
				if( $access[ 'PrivId' ] !== $_POST[$tag] )
				{
					$query = sprintf( "update access set privid = '%s' where userid = '%s'", $_POST[$tag], $uid );
					DoQuery( $query );
					
					$text = array();
					$text[] = "insert event_log set time=now()";
					$text[] = "type = 'user'";
					$text[] = "userid = '$userid'";
					$text[] = sprintf( "item = 'update %s(%d), set privid = %s'", $user['username'], $uid, $_POST[$tag] );
					$query = join( ',', $text );
					DoQuery( $query );
				}
			}
		}
	}
	
	if( $gTrace ) array_pop( $GLOBALS[ 'gFunction' ] );
	return;
	
	DoQuery( "select * from users where userid= '$id'" );
	$user = mysql_fetch_assoc( $local_result );
	
	$settings = array( 'active', 'home', 'work', 'cell', 'street', 'city', 'zip' );

	$fld_name = 'username';
	if( isset( $_POST[ $fld_name ] ) )
	{
		if( $_POST[ $fld_name ] != $user[ $fld_name ] )
		{
			
			$query = sprintf( "select userid from users where username = '%s'", $_POST[ $fld_name ] );
			DoQuery( $query );
			if( $local_numrows > 0 ) {
				echo sprintf( "** Username [%s] already taken **<br>", $_POST[ $fld_name ] );
			} else {
				array_push( $settings, 'username' );
			}
		}
	}

	if( UserManager( 'authorized', 'control' ) ) {
		array_push( $settings, 'access', 'last', 'first', 'email' );
	}
	
	foreach( $settings as $fld_name )
	{
		if( ! isset ( $_POST[ $fld_name ] ) )		// The field isn't part of the update
			continue;
		
		if( $_POST[ $fld_name ] == $user[ $fld_name ] )		// The field hasn't changed value
			continue;
		
		$query = sprintf( "update users set %s = '%s' where userid = '%s'", $fld_name, $_POST[ $fld_name ], $id );
		DoQuery( $query );

		unset( $text );
		$text[] = "insert event_log set time=now()";
		$text[] = "type = 'user'";
		$text[] = sprintf( "userid = '%d'", $GLOBALS['gUserId'] );
		$text[] = sprintf( "item = 'update $fld_name from [%s] to [%s]'", $user[ $fld_name ], $_POST[ $fld_name ] );
		$query = join( ",", $text );
		DoQuery( $query );
		echo "Updated field: $fld_name<br>";
	}
	if( $GLOBALS['gFrom'] == "UserSettings" ) {
		UserManagerSettings( $id );
	} elseif( $GLOBALS['gFrom'] == "UserSettingsControl" ) {
		$GLOBALS['gAction'] = 'User_Control';
	} elseif( $GLOBALS['gFrom'] == 'UserEdit' ) {
		$_POST['btn_action'] = NULL;
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
	if( $gUserVerified == 0 )
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
			$text[] = sprintf( "id = '%d'", $user['id'] );
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
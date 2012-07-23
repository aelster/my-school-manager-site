function MyAddAction(cmd) {
   var e = document.getElementById('action');
   if (!e) alert("can't find element[ action ]");
   e.value = cmd;
   document.fMain.submit();
}

function MyAddField(id) {
   var e = document.getElementById('fields');
   if (!e) alert("Can't find id: fields");
   if (!e.value) {
      e.value = id;
   } else {
      e.value += ',' + id;
   }
}
function MyChallengeResponse() {
   var up = document.getElementById("password");
   if (up.value.length == 64) {
      document.getElementById("bypass").value = "1";
      document.fMain.response.value = up.value;
   } else {
      str = document.fMain.username.value.toLowerCase() + ":" + sha256_digest(up.value) + ":" + document.fMain.challenge.value;
      document.fMain.response.value = sha256_digest(str);
   }
   up.value = "";
   document.fMain.challenge.value = "";
   MyAddAction('Login');
   return false;
}

function MyConfirm(prompt) {
   var response = confirm(prompt);
   if (response) {
      MyAddAction('Update');
   }
}

function MyCreateDebugWindow() {
   window.top.debugWindow =
   window.open("",
      "Debug",
      "left=0,top=0,width=900,height=700,scrollbars=yes,status=yes,resizable=yes");

   window.top.debugWindow.opener = self;

// open the document for writing                                                                            
    window.top.debugWindow.document.open();
    window.top.debugWindow.document.write(
        "<HTML><HEAD><TITLE>Debug Window</TITLE></HEAD><BODY><PRE>\n");
}

function MyDebug(text) {
   if (window.top.debugWindow && ! window.top.debugWindow.closed) {
      var str='';
      for( var i=0; i < arguments.length; i++ ) {
         str += arguments[i];
      }
      window.top.debugWindow.document.write(str+"\n");
   }
}

function MyGetPassword(e) {
   var keynum, keychar, numcheck;
   if (window.event) // IE
   {
      keynum = e.keyCode;
   } else if (e.which) // Netscape/Firefox/Opera
   {
      keynum = e.which;
   }
   if (keynum == 13) {
      var f = document.getElementById('password');
      f.focus();
   }
}

function MyKeyDown(e) {
   var keynum, keychar, numcheck;
   if (window.event) // IE
   {
      keynum = e.keyCode;
   } else if (e.which) // Netscape/Firefox/Opera
   {
      keynum = e.which;
   }
   if (keynum == 13) {
      MyChallengeResponse();
   }
}

function MyMungePwd() {
   var p1 = document.getElementById("newpassword1");
   var p2 = document.getElementById("newpassword2");
   p1.value = sha256_digest(p1.value);
   p2.value = "";
}

function MySetFocus(id) {
   var e = document.getElementById(id);
   if (!e) alert("Can't find id " + id);
   e.focus();
}
function MySetValue(id, val) {
   e = document.getElementById(id);
   if (!e) alert("Can't find id " + id);
   e.value = val;
}

function MyToggleBgRed(id) {
   var e = document.getElementById(id);
   if (!e) alert("Can't find id: " + id);
   if (e.style.backgroundColor == '#ff0000') {
      e.style.backgroundColor = '#ffffff';
   } else {
      e.style.backgroundColor = '#ff0000';
   }
}

function MyVerifyPwd() {
   var p1 = document.getElementById("newpassword1");
   var p2 = document.getElementById("newpassword2");
   var btn = document.getElementById("userSettingsUpdate");
   var txt = document.getElementById("pwdval");
   if (p1.value != p2.value) {
      btn.disabled = true;
      btn.style.backgroundColor = '#ff0000';
      if (txt) txt.innerHTML = "** Passwords Don't Match **";
   } else {
      btn.disabled = false;
      btn.style.backgroundColor = '#90EE90';
      if (txt) txt.innerHTML = "";
   }
}
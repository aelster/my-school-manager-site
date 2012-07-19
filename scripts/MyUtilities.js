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

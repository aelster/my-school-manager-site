<?php
function MakeTag() {
   $tmp = func_get_args();
   $str = join( '_', $tmp );
   return "name=$str id=$str";
}
?>
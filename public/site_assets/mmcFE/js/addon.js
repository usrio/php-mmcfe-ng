$(document).ready(function() {
$("#acs").click(function() {
var text = $('#donatePercent').val();
  if (text == 0) {
alert("Please help out with a small donation! 0% Fee pool, but still have server expenses.");
  }
});
});

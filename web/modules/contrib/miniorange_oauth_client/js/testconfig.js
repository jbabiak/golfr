jQuery(document).ready(function() {
document.getElementById('testConfigButton').onclick = function () {
  var baseurl = document.getElementById('base_Url');
  var url = baseurl.getAttribute("data");
  var finalUrl = url + '/testSSO';
  var myWindow = window.open(finalUrl, "TEST OAuth Client", "scrollbars=1 width=1050, height=900");
}
});
